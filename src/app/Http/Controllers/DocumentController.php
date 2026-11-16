<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentFolder;
use App\Services\DocumentService;
use App\Services\ExtensionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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

        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file'],
            'folder_id' => ['nullable', 'integer', 'exists:doc_folders,id'],
            'notify_members' => ['nullable', 'boolean'],
            'is_encrypted' => ['nullable', 'boolean'],
            'encrypted_files' => ['nullable', 'array'],
            'encrypted_files.*' => ['boolean'],
        ]);

        $folderId = isset($validated['folder_id']) ? (int) $validated['folder_id'] : null;
        $isEncrypted = $request->boolean('is_encrypted');
        $encryptedFiles = $request->input('encrypted_files', []);
        $allowedExtensions = allowed_document_extensions();
        $uploaded = [];
        $failed = [];

        foreach ($request->file('files', []) as $index => $file) {
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

            if ($this->documents->nameExistsForUser($request->user(), $originalName)) {
                $failed[] = "\"{$originalName}\": a document with this name already exists.";
                continue;
            }

            if ($this->documents->wouldExceedStorageQuota($request->user(), (int) $file->getSize())) {
                $failed[] = "\"{$originalName}\": storage quota exceeded.";
                continue;
            }

            $fileEncrypted = is_array($encryptedFiles) && array_key_exists($index, $encryptedFiles)
                ? filter_var($encryptedFiles[$index], FILTER_VALIDATE_BOOLEAN)
                : $isEncrypted;

            $uploaded[] = $this->documents->createFromUpload($request->user(), $file, $folderId, $fileEncrypted);
        }

        $uploadedCount = count($uploaded);
        if ($uploadedCount === 0) {
            return back()->withErrors(['files' => implode(' ', $failed)]);
        }

        if ($request->boolean('notify_members')) {
            $this->documents->notifyTeamMembers($request->user(), 'uploaded', $uploaded);
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
            'folder_id' => ['nullable', 'integer', 'exists:doc_folders,id'],
            'content' => ['nullable', 'string', 'max:2000000'],
            'notify_members' => ['nullable', 'boolean'],
            'is_encrypted' => ['nullable', 'boolean'],
        ]);

        $finalName = $this->documents->buildDocumentName($validated['name'], $validated['extension']);
        if ($this->documents->nameExistsForUser($request->user(), $finalName)) {
            return back()->withErrors([
                'name' => "A document named \"{$finalName}\" already exists.",
            ]);
        }

        $contentSize = strlen(trim(html_to_text($validated['content'] ?? '')));
        if ($this->documents->wouldExceedStorageQuota($request->user(), $contentSize)) {
            return back()->withErrors([
                'storage' => 'Storage quota exceeded. Delete unused files or ask an admin to increase your storage limit.',
            ]);
        }

        $document = $this->documents->createBlank(
            $request->user(),
            $validated['name'],
            $validated['extension'],
            $validated['content'] ?? '',
            $validated['visibility'] ?? 'publish',
            isset($validated['folder_id']) ? (int) $validated['folder_id'] : null,
            $request->boolean('is_encrypted'),
        );

        if ($request->boolean('notify_members') && $document->visibility === 'publish') {
            $this->documents->notifyTeamMembers($request->user(), 'created', [$document]);
        }

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

        $newContent = $validated['content'] ?? '';
        $newSize = strlen(html_to_text($newContent));
        $deltaBytes = max(0, $newSize - (int) $document->size);
        $owner = $document->user ?? $request->user();

        if ($this->documents->wouldExceedStorageQuota($owner, $deltaBytes)) {
            return back()->withErrors([
                'storage' => 'Storage quota exceeded. Unable to save changes.',
            ]);
        }

        $this->documents->updateContent($document, $newContent, $request->user());

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

        if ($this->documents->nameExistsForUser($request->user(), $validated['name'], $document->id)) {
            return back()->withErrors([
                'name' => "A document named \"{$validated['name']}\" already exists.",
            ]);
        }

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
     * Aktifkan atau nonaktifkan enkripsi konten dokumen di database.
     */
    public function toggleEncryption(Request $request, Document $document): RedirectResponse
    {
        $this->documents->authorizeAccess($request->user(), $document);
        $updated = $this->documents->toggleEncryption($document, $request->user());

        $message = $updated->is_encrypted
            ? "{$document->name} content is now encrypted."
            : "Encryption removed from {$document->name}.";

        return back()->with('success', $message);
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

        $headers = [
            'Content-Type' => $document->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$document->name.'"',
        ];

        if ($document->is_encrypted) {
            $bytes = $this->documents->readStoredFileBytes($document);

            return response()->stream(fn () => print($bytes), 200, $headers);
        }

        return Storage::disk('local')->response($document->file_path, $document->name, $headers);
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

            if ($document->is_encrypted) {
                $bytes = $this->documents->readStoredFileBytes($document);

                return response()->streamDownload(
                    fn () => print($bytes),
                    $document->name,
                    ['Content-Type' => $document->mime_type ?: 'application/octet-stream'],
                );
            }

            return Storage::disk('local')->download($document->file_path, $document->name);
        }

        $text = $this->documents->toPlainText($document);

        return response()->streamDownload(
            fn () => print($text),
            $document->name,
            ['Content-Type' => 'text/plain; charset=UTF-8'],
        );
    }

    /**
     * Toggle favorite/pin dokumen untuk user yang login.
     */
    public function toggleFavorite(Request $request, Document $document): RedirectResponse
    {
        $this->documents->authorizeAccess($request->user(), $document);
        $isFav = $this->documents->toggleFavorite($document, $request->user());

        return back()->with('success', $isFav ? "{$document->name} pinned to favorites." : "{$document->name} removed from favorites.");
    }

    /**
     * Pindahkan satu dokumen ke folder tertentu.
     */
    public function moveFolder(Request $request, Document $document): RedirectResponse
    {
        $this->documents->authorizeAccess($request->user(), $document);

        $validated = $request->validate([
            'folder_id' => ['nullable', 'integer', 'exists:doc_folders,id'],
        ]);

        $folderId = isset($validated['folder_id']) ? (int) $validated['folder_id'] : null;
        $this->documents->moveFolder($document, $folderId, $request->user());

        return back()->with('success', "{$document->name} moved.");
    }

    /**
     * Download beberapa dokumen sekaligus dalam format .zip.
     */
    public function bulkDownload(Request $request): BinaryFileResponse
    {
        abort_unless(can_use_function('documents.download', $request->user()), 403);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $zipPath = $this->documents->createZipForDownload($request->user(), $validated['ids']);

        return response()
            ->download($zipPath, 'documents-'.now()->format('Ymd-His').'.zip')
            ->deleteFileAfterSend(true);
    }

    /**
     * Hapus beberapa dokumen sekaligus.
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        abort_unless(can_use_function('documents.delete', $request->user()), 403);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $count = $this->documents->bulkDelete($request->user(), $validated['ids']);

        return back()->with('success', "{$count} document(s) deleted.");
    }

    /**
     * Pindahkan beberapa dokumen sekaligus ke folder tertentu.
     */
    public function bulkMoveFolder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'folder_id' => ['nullable', 'integer', 'exists:doc_folders,id'],
        ]);

        $folderId = isset($validated['folder_id']) ? (int) $validated['folder_id'] : null;
        $count = $this->documents->bulkMoveFolder($request->user(), $validated['ids'], $folderId);

        return back()->with('success', "{$count} document(s) moved.");
    }

    /**
     * Assign teams ke beberapa dokumen publish sekaligus (khusus superadmin).
     */
    public function bulkAssignTeams(Request $request): RedirectResponse
    {
        abort_unless(is_superadmin($request->user()), 403);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'team_ids' => ['present', 'array'],
            'team_ids.*' => ['integer', 'exists:mst_teams,id'],
        ]);

        $count = $this->documents->bulkAssignTeams($request->user(), $validated['ids'], $validated['team_ids']);

        return back()->with('success', "Team assignment updated for {$count} publish document(s).");
    }

    /**
     * Buat folder/kategori baru.
     */
    public function storeFolder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:doc_folders,name'],
        ]);

        $folder = $this->documents->createFolder($request->user(), $validated['name']);

        return back()->with('success', "Folder \"{$folder->name}\" created.");
    }

    /**
     * Hapus folder/kategori (dokumen di dalamnya menjadi Uncategorized).
     */
    public function destroyFolder(DocumentFolder $folder): RedirectResponse
    {
        $name = $folder->name;
        $this->documents->deleteFolder($folder);

        return back()->with('success', "Folder \"{$name}\" deleted.");
    }

    /**
     * Buat atau perbarui shareable read-only link untuk dokumen.
     */
    public function createShareLink(Request $request, Document $document): RedirectResponse
    {
        $this->documents->authorizeAccess($request->user(), $document);

        $validated = $request->validate([
            'expiry' => ['required', 'string', 'in:1h,24h,7d,30d,never'],
            'password' => ['nullable', 'string', 'min:4', 'max:50'],
            'notify_members' => ['nullable', 'boolean'],
        ]);

        $this->documents->createShareLink(
            $document,
            $request->user(),
            $validated['expiry'],
            $validated['password'] ?? null,
        );

        if ($request->boolean('notify_members')) {
            $this->documents->notifyTeamMembers($request->user(), 'shared', [$document]);
        }

        return back()->with('success', "Shareable link created for {$document->name}.");
    }

    /**
     * Perbarui atau hapus password pada shareable link aktif milik dokumen.
     */
    public function updateSharePassword(Request $request, Document $document): RedirectResponse
    {
        $this->documents->authorizeAccess($request->user(), $document);

        $validated = $request->validate([
            'password' => ['nullable', 'string', 'min:4', 'max:50'],
            'notify_members' => ['nullable', 'boolean'],
        ]);

        $link = $this->documents->updateShareLinkPassword(
            $document,
            $request->user(),
            $validated['password'] ?? null,
        );

        abort_unless($link, 404, 'No active share link found.');

        if ($request->boolean('notify_members')) {
            $this->documents->notifyTeamMembers($request->user(), 'shared', [$document]);
        }

        $msg = filled($validated['password'] ?? null)
            ? "Share link password updated for {$document->name}."
            : "Share link password removed for {$document->name}.";

        return back()->with('success', $msg);
    }

    /**
     * Cabut (revoke) shareable link aktif milik dokumen.
     */
    public function revokeShareLink(Request $request, Document $document): RedirectResponse
    {
        $this->documents->authorizeAccess($request->user(), $document);

        $request->validate([
            'notify_members' => ['nullable', 'boolean'],
        ]);

        $this->documents->revokeShareLinks($document, $request->user());

        if ($request->boolean('notify_members')) {
            $this->documents->notifyTeamMembers($request->user(), 'revoked', [$document]);
        }

        return back()->with('success', "Shareable link revoked for {$document->name}.");
    }
}
