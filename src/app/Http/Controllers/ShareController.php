<?php

namespace App\Http\Controllers;

use App\Models\DocumentShareLink;
use App\Services\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShareController extends Controller
{
    public function __construct(
        private readonly DocumentService $documents,
    ) {}

    /**
     * Tampilkan halaman read-only publik untuk dokumen yang di-share via token.
     */
    public function show(Request $request, string $token): Response
    {
        $link = DocumentShareLink::with('document')
            ->where('token', $token)
            ->first();

        if (! $link || ! $link->isValid() || ! $link->document) {
            return Inertia::render('Share/Show', [
                'expired' => true,
                'requiresPassword' => false,
                'token' => $token,
                'document' => null,
            ]);
        }

        $doc = $link->document;
        $accessKey = $link->hasPassword() ? $request->session()->get('share_access_key') : null;

        if ($link->hasPassword() && ! $this->isAccessKeyValid($link, is_string($accessKey) ? $accessKey : null)) {
            return Inertia::render('Share/Show', [
                'expired' => false,
                'requiresPassword' => true,
                'token' => $token,
                'expiresAt' => $link->expires_at?->toIso8601String(),
                'document' => [
                    'name' => $doc->name,
                ],
            ]);
        }

        $queryParams = $accessKey ? ['token' => $token, 'k' => $accessKey] : ['token' => $token];

        return Inertia::render('Share/Show', [
            'expired' => false,
            'requiresPassword' => false,
            'token' => $token,
            'expiresAt' => $link->expires_at?->toIso8601String(),
            'document' => [
                'name' => $doc->name,
                'size' => $doc->size,
                'updated_at' => $doc->updated_at?->toIso8601String(),
                'is_stored_file' => $doc->isStoredFile(),
                'mime_type' => $doc->mime_type,
                'content' => $doc->isStoredFile() ? null : $doc->content,
                'preview_url' => $doc->isStoredFile() ? route('share.preview', $queryParams) : null,
                'download_url' => route('share.download', $queryParams),
            ],
        ]);
    }

    /**
     * Verifikasi password/PIN untuk membuka share link yang terproteksi.
     */
    public function verify(Request $request, string $token): RedirectResponse
    {
        $link = $this->resolveValidLink($token);

        if (! $link->hasPassword()) {
            return redirect()->route('share.show', $token);
        }

        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($validated['password'], $link->password_hash)) {
            return back()->withErrors([
                'password' => 'Incorrect password or PIN.',
            ]);
        }

        $accessKey = Str::random(40);
        Cache::put($this->cacheKey($link, $accessKey), true, now()->addMinutes(30));

        return redirect()
            ->route('share.show', $token)
            ->with('share_access_key', $accessKey);
    }

    /**
     * Stream file biner inline untuk preview (PDF / DOCX) via token share publik.
     */
    public function preview(Request $request, string $token): StreamedResponse
    {
        $link = $this->resolveValidLink($token);
        abort_unless($this->isAccessKeyValid($link, $request->query('k')), 403, 'Password required.');
        $doc = $link->document;

        abort_unless($doc->isStoredFile() && Storage::disk('local')->exists($doc->file_path), 404);

        return Storage::disk('local')->response($doc->file_path, $doc->name, [
            'Content-Type' => $doc->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$doc->name.'"',
        ]);
    }

    /**
     * Download dokumen via token share publik.
     */
    public function download(Request $request, string $token): StreamedResponse
    {
        $link = $this->resolveValidLink($token);
        abort_unless($this->isAccessKeyValid($link, $request->query('k')), 403, 'Password required.');
        $doc = $link->document;

        if ($doc->isStoredFile()) {
            abort_unless(Storage::disk('local')->exists($doc->file_path), 404);

            return Storage::disk('local')->download($doc->file_path, $doc->name);
        }

        $text = $this->documents->toPlainText($doc);

        return response()->streamDownload(
            fn () => print($text),
            $doc->name,
            ['Content-Type' => 'text/plain; charset=UTF-8'],
        );
    }

    private function resolveValidLink(string $token): DocumentShareLink
    {
        $link = DocumentShareLink::with('document')
            ->where('token', $token)
            ->first();

        abort_unless($link && $link->isValid() && $link->document, 404, 'Share link expired or invalid.');

        return $link;
    }

    private function isAccessKeyValid(DocumentShareLink $link, mixed $accessKey): bool
    {
        if (! $link->hasPassword()) {
            return true;
        }

        if (! is_string($accessKey) || $accessKey === '') {
            return false;
        }

        return Cache::has($this->cacheKey($link, $accessKey));
    }

    private function cacheKey(DocumentShareLink $link, string $accessKey): string
    {
        return "share_access:{$link->id}:{$accessKey}";
    }
}
