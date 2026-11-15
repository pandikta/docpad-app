<?php

namespace App\Http\Controllers;

use App\Models\DocumentShareLink;
use App\Services\DocumentService;
use Illuminate\Support\Facades\Storage;
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
    public function show(string $token): Response
    {
        $link = DocumentShareLink::with('document')
            ->where('token', $token)
            ->first();

        if (! $link || ! $link->isValid() || ! $link->document) {
            return Inertia::render('Share/Show', [
                'expired' => true,
                'document' => null,
            ]);
        }

        $doc = $link->document;

        return Inertia::render('Share/Show', [
            'expired' => false,
            'expiresAt' => $link->expires_at?->toIso8601String(),
            'document' => [
                'name' => $doc->name,
                'size' => $doc->size,
                'updated_at' => $doc->updated_at?->toIso8601String(),
                'is_stored_file' => $doc->isStoredFile(),
                'mime_type' => $doc->mime_type,
                'content' => $doc->isStoredFile() ? null : $doc->content,
                'preview_url' => $doc->isStoredFile() ? route('share.preview', $token) : null,
                'download_url' => route('share.download', $token),
            ],
        ]);
    }

    /**
     * Stream file biner inline untuk preview (PDF / DOCX) via token share publik.
     */
    public function preview(string $token): StreamedResponse
    {
        $link = $this->resolveValidLink($token);
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
    public function download(string $token): StreamedResponse
    {
        $link = $this->resolveValidLink($token);
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
}
