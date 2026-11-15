<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentService;
use App\Services\ExtensionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(private DocumentService $documents)
    {
    }

    /**
     * Upload satu atau banyak file sekaligus milik team user (mendukung partial success saat bulk upload).
     */
    public function store(Request $request, ExtensionService $extensions): RedirectResponse
    {
        abort_unless(can_use_function('documents.upload', $request->user()), 403);

        $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file'],
        ]);

        $allowedExtensions = allowed_document_extensions();
        $uploaded = [];
        $failed = [];

        foreach ($request->file('files', []) as $file) {
            $ext = strtolower($file->getClientOriginalExtension());
            $originalName = $file->getClientOriginalName();

            if (! in_array($ext, $allowedExtensions, true)) {
                $failed[] = "\"{$originalName}\": extension .{$ext} is not allowed.";
                continue;
            }

            $maxKb = $extensions->getMaxSizeKbForExtension($ext);
            if ($file->getSize() > ($maxKb * 1024)) {
                $maxLabel = $maxKb >= 1024 ? round($maxKb / 1024, 2).' MB' : "{$maxKb} KB";
                $failed[] = "\"{$originalName}\": exceeds max size of {$maxLabel} for .{$ext}.";
                continue;
            }

            $uploaded[] = $this->documents->createFromUpload($request->user(), $file);
        }

        $uploadedCount = count($uploaded);
        if ($uploadedCount === 0) {
            return back()->withErrors(['files' => implode(' ', $failed)]);
        }

        $message = $uploadedCount === 1
            ? "{$uploaded[0]->name} uploaded successfully."
            : "{$uploadedCount} documents uploaded successfully.";

        $redirect = back()->with('success', $message);

        if (count($failed) > 0) {
            $redirect->with('warning', count($failed).' file(s) skipped: '.implode(' ', $failed));
        }

        return $redirect;
    }

    /**
     * Buat file dokumen baru langsung dari form (pilih ekstensi dari daftar yang diizinkan).
     */
    public function create(Request $request): RedirectResponse
    {
        abort_unless(can_use_function('documents.create', $request->user()), 403);

        $allowed = implode(',', allowed_document_extensions());

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'extension' => ['required', 'string', "in:{$allowed}"],
            'visibility' => ['nullable', 'string', 'in:publish,private'],
            'content' => ['nullable', 'string', 'max:2000000'],
        ]);

        $document = $this->documents->createBlank(
            $request->user(),
            $validated['name'],
            $validated['extension'],
            $validated['content'] ?? '',
            $validated['visibility'] ?? 'publish',
        );

        return back()->with('success', "{$document->name} created successfully.");
    }

    /**
     * Simpan hasil edit dari text editor.
     */
    public function update(Request $request, Document $document): RedirectResponse
    {
        abort_unless(can_use_function('documents.edit', $request->user()), 403);
        $this->documents->authorizeAccess($request->user(), $document);

        $validated = $request->validate([
            'content' => ['nullable', 'string', 'max:2000000'],
        ]);

        $this->documents->updateContent($document, $validated['content'] ?? '', $request->user());

        return back()->with('success', "{$document->name} saved.");
    }

    /**
     * Ubah nama dokumen.
     */
    public function rename(Request $request, Document $document): RedirectResponse
    {
        abort_unless(can_use_function('documents.rename', $request->user()), 403);
        $this->documents->authorizeAccess($request->user(), $document);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! has_allowed_document_extension((string) $value)) {
                        $exts = '.'.implode(', .', allowed_document_extensions());
                        $fail("Document name must end with an allowed extension ({$exts}).");
                    }
                },
            ],
        ]);

        $this->documents->rename($document, $validated['name'], $request->user());

        return back()->with('success', "Document renamed to {$document->name}.");
    }

    /**
     * Assign dokumen ke satu atau banyak tim (khusus superadmin).
     */
    public function assignTeams(Request $request, Document $document): RedirectResponse
    {
        abort_unless(is_superadmin($request->user()), 403);
        $this->documents->authorizeAccess($request->user(), $document);
        abort_if($document->visibility === 'private', 403, 'Private documents cannot be assigned to teams.');

        $validated = $request->validate([
            'team_ids' => ['present', 'array'],
            'team_ids.*' => ['integer', 'exists:mst_teams,id'],
        ]);

        $this->documents->assignTeams($document, $validated['team_ids'], $request->user());

        return back()->with('success', "Team assignment updated for {$document->name}.");
    }

    /**
     * Ubah status visibility dokumen (publish / private) oleh pemilik dokumen.
     */
    public function updateVisibility(Request $request, Document $document): RedirectResponse
    {
        $this->documents->authorizeAccess($request->user(), $document);
        abort_unless((int) $document->user_id === (int) $request->user()->id, 403, 'Only the document owner can change visibility.');

        $validated = $request->validate([
            'visibility' => ['required', 'string', 'in:publish,private'],
        ]);

        $this->documents->updateVisibility($document, $validated['visibility'], $request->user());

        return back()->with('success', "{$document->name} is now {$validated['visibility']}.");
    }

    /**
     * Hapus dokumen.
     */
    public function destroy(Request $request, Document $document): RedirectResponse
    {
        abort_unless(can_use_function('documents.delete', $request->user()), 403);
        $this->documents->authorizeAccess($request->user(), $document);

        $name = $document->name;
        $this->documents->delete($document);

        return back()->with('success', "{$name} deleted.");
    }

    /**
     * Preview dokumen biner (mis. PDF) secara inline di browser.
     */
    public function preview(Request $request, Document $document): StreamedResponse
    {
        $this->documents->authorizeAccess($request->user(), $document);
        abort_unless($document->isStoredFile() && Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->response($document->file_path, $document->name, [
            'Content-Type' => $document->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$document->name.'"',
        ]);
    }

    /**
     * Download dokumen (dari storage server jika file biner, atau sebagai teks biasa).
     */
    public function download(Request $request, Document $document): StreamedResponse
    {
        abort_unless(can_use_function('documents.download', $request->user()), 403);
        $this->documents->authorizeAccess($request->user(), $document);

        if ($document->isStoredFile()) {
            abort_unless(Storage::disk('local')->exists($document->file_path), 404);

            return Storage::disk('local')->download($document->file_path, $document->name);
        }

        $text = $this->documents->toPlainText($document);

        return response()->streamDownload(
            fn () => print($text),
            $document->name,
            ['Content-Type' => 'text/plain; charset=UTF-8'],
        );
    }
}
