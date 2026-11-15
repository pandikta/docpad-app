<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\DocumentShareLink;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class DocumentService
{
    /**
     * Ekstensi file biner yang disimpan langsung ke storage server (bukan sebagai teks HTML di DB).
     *
     * @var list<string>
     */
    private const BINARY_EXTENSIONS = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'];
    /**
     * Daftar dokumen untuk user yang login (superadmin melihat semua dokumen lintas tim).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function listForUser(User $user, ?string $search = null): Collection
    {
        return Document::forUser($user)
            ->search($search)
            ->with(['user:id,name', 'folder:id,name', 'teams:id,team_name', 'logs.user:id,name', 'shareLinks'])
            ->withExists([
                'favoritedBy as is_favorite' => fn ($q) => $q->where('mst_users.id', $user->id),
            ])
            ->orderByDesc('is_favorite')
            ->latest()
            ->get()
            ->map(fn (Document $document) => $document->toListItem());
    }

    /**
     * Daftar folder dokumen yang tersedia.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function listFolders(): Collection
    {
        return DocumentFolder::orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (DocumentFolder $folder) => [
                'id' => $folder->id,
                'name' => $folder->name,
            ]);
    }

    public function createFolder(User $user, string $name): DocumentFolder
    {
        return DocumentFolder::create([
            'user_id' => $user->id,
            'name' => trim($name),
        ]);
    }

    public function deleteFolder(DocumentFolder $folder): void
    {
        $folder->delete();
    }

    /**
     * Pastikan user berhak mengakses dokumen (superadmin otomatis lolos).
     */
    public function authorizeAccess(User $user, Document $document): void
    {
        abort_unless(can_access_document($document, $user), 403);
    }

    /**
     * Normalisasi nama file + ekstensi (menghindari akhiran ekstensi ganda).
     */
    public function buildDocumentName(string $name, string $extension): string
    {
        $extension = ltrim(strtolower(trim($extension)), '.');
        $baseName = trim($name);

        if (str_ends_with(strtolower($baseName), '.'.$extension)) {
            $baseName = substr($baseName, 0, -strlen('.'.$extension));
        }

        return trim($baseName).'.'.$extension;
    }

    /**
     * Cek apakah nama dokumen sudah ada di daftar dokumen yang terlihat oleh user (case-insensitive).
     */
    public function nameExistsForUser(User $user, string $name, ?int $ignoreDocumentId = null): bool
    {
        return Document::forUser($user)
            ->when($ignoreDocumentId, fn ($q) => $q->where('id', '!=', $ignoreDocumentId))
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($name))])
            ->exists();
    }

    /**
     * Buat banyak dokumen sekaligus (bulk upload) dari array file teks.
     *
     * @param  array<int, UploadedFile>  $files
     * @return Collection<int, Document>
     */
    public function createManyFromUpload(User $user, array $files): Collection
    {
        return collect($files)->map(fn (UploadedFile $file) => $this->createFromUpload($user, $file));
    }

    /**
     * Buat dokumen baru dari file yang di-upload (teks disimpan ke DB, file biner seperti PDF ke storage server).
     */
    public function createFromUpload(User $user, UploadedFile $file, ?int $folderId = null): Document
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $isBinary = in_array($extension, self::BINARY_EXTENSIONS, true);

        $filePath = null;
        $content = null;

        if ($isBinary) {
            $filePath = Storage::disk('local')->putFile('documents', $file);
        } else {
            $raw = (string) file_get_contents($file->getRealPath());
            $content = text_to_html($raw);
        }

        $document = Document::create([
            'user_id' => $user->id,
            'folder_id' => $folderId,
            'name' => $file->getClientOriginalName(),
            'visibility' => 'publish',
            'content' => $content,
            'file_path' => $filePath,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        if ($user->team_id) {
            $document->teams()->syncWithoutDetaching([$user->team_id]);
        }

        record_document_log($document, 'created a new document', $user);

        return $document;
    }

    /**
     * Buat dokumen baru langsung dari form Create File (nama + pilihan ekstensi + visibility + konten opsional).
     */
    public function createBlank(User $user, string $name, string $extension, string $content = '', string $visibility = 'publish', ?int $folderId = null): Document
    {
        $finalName = $this->buildDocumentName($name, $extension);
        $htmlContent = $content !== '' ? $content : '<p><br></p>';
        $visibility = $visibility === 'private' ? 'private' : 'publish';

        $document = Document::create([
            'user_id' => $user->id,
            'folder_id' => $folderId,
            'name' => $finalName,
            'visibility' => $visibility,
            'content' => $htmlContent,
            'size' => strlen(trim(html_to_text($htmlContent))),
        ]);

        if ($visibility === 'publish' && $user->team_id) {
            $document->teams()->syncWithoutDetaching([$user->team_id]);
        }

        record_document_log($document, "created a new {$visibility} document", $user);

        return $document;
    }

    /**
     * Assign dokumen ke satu atau banyak tim (khusus superadmin) dan catat log perubahannya.
     *
     * @param  array<int, int>  $teamIds
     */
    public function assignTeams(Document $document, array $teamIds, ?User $user = null): Document
    {
        $document->teams()->sync($teamIds);
        $document->touch();

        $teamNames = $document->teams()->pluck('team_name')->implode(', ');
        $action = $teamNames !== ''
            ? "assigned document to team: {$teamNames}"
            : 'removed all team assignments';

        record_document_log($document, $action, $user);

        return $document;
    }

    /**
     * Simpan isi dokumen hasil edit dari text editor.
     */
    public function updateContent(Document $document, string $content, ?User $user = null): Document
    {
        abort_if($document->isStoredFile(), 422, 'Stored binary files cannot be edited in the text editor.');

        $document->update([
            'content' => $content,
            'size' => strlen(html_to_text($content)),
        ]);

        // Pastikan updated_at selalu diperbarui saat disimpan, lalu catat log perubahan.
        $document->touch();
        record_document_log($document, 'updated this document', $user);

        return $document;
    }

    /**
     * Ubah nama dokumen dan catat log perubahannya.
     */
    public function rename(Document $document, string $newName, ?User $user = null): Document
    {
        $oldName = $document->name;
        $newName = trim($newName);

        $document->update(['name' => $newName]);
        $document->touch();

        if ($oldName !== $newName) {
            record_document_log($document, "renamed document from \"{$oldName}\" to \"{$newName}\"", $user);
        }

        return $document;
    }

    /**
     * Ubah status visibility dokumen (publish / private) dan catat log perubahannya.
     */
    public function updateVisibility(Document $document, string $visibility, ?User $user = null): Document
    {
        $visibility = $visibility === 'private' ? 'private' : 'publish';

        if ($document->visibility === $visibility) {
            return $document;
        }

        $document->update(['visibility' => $visibility]);

        if ($visibility === 'private') {
            $document->teams()->detach();
        } elseif ($user?->team_id && $document->teams()->count() === 0) {
            $document->teams()->syncWithoutDetaching([$user->team_id]);
        }

        $document->touch();
        record_document_log($document, "changed visibility to {$visibility}", $user);

        return $document;
    }

    /**
     * Hapus dokumen.
     */
    public function delete(Document $document): void
    {
        if ($document->file_path && Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        $document->delete();
    }

    /**
     * Toggle status favorite/pin dokumen untuk user tertentu.
     */
    public function toggleFavorite(Document $document, User $user): bool
    {
        $exists = $document->favoritedBy()->where('mst_users.id', $user->id)->exists();

        if ($exists) {
            $document->favoritedBy()->detach($user->id);
            return false;
        }

        $document->favoritedBy()->attach($user->id, ['created_at' => now()]);
        return true;
    }

    /**
     * Pindahkan dokumen ke folder tertentu (atau null untuk Uncategorized).
     */
    public function moveFolder(Document $document, ?int $folderId, ?User $user = null): Document
    {
        $document->update(['folder_id' => $folderId]);
        $document->touch();

        $folderName = $folderId ? (DocumentFolder::find($folderId)?->name ?? 'Unknown') : 'Uncategorized';
        record_document_log($document, "moved document to folder: {$folderName}", $user);

        return $document;
    }

    /**
     * @param  array<int, int>  $documentIds
     */
    public function bulkDelete(User $user, array $documentIds): int
    {
        $docs = Document::forUser($user)->whereIn('id', $documentIds)->get();
        foreach ($docs as $doc) {
            $this->delete($doc);
        }

        return $docs->count();
    }

    /**
     * @param  array<int, int>  $documentIds
     */
    public function bulkMoveFolder(User $user, array $documentIds, ?int $folderId): int
    {
        $docs = Document::forUser($user)->whereIn('id', $documentIds)->get();
        foreach ($docs as $doc) {
            $this->moveFolder($doc, $folderId, $user);
        }

        return $docs->count();
    }

    /**
     * @param  array<int, int>  $documentIds
     * @param  array<int, int>  $teamIds
     */
    public function bulkAssignTeams(User $user, array $documentIds, array $teamIds): int
    {
        $docs = Document::forUser($user)
            ->whereIn('id', $documentIds)
            ->where('visibility', '!=', 'private')
            ->get();

        foreach ($docs as $doc) {
            $this->assignTeams($doc, $teamIds, $user);
        }

        return $docs->count();
    }

    /**
     * Buat file ZIP sementara berisi dokumen terpilih yang berhak diakses user.
     *
     * @param  array<int, int>  $documentIds
     */
    public function createZipForDownload(User $user, array $documentIds): string
    {
        $docs = Document::forUser($user)->whereIn('id', $documentIds)->get();
        abort_if($docs->isEmpty(), 404, 'No accessible documents found.');

        $zipPath = tempnam(sys_get_temp_dir(), 'docpad_zip_').'.zip';
        $zip = new ZipArchive;
        abort_unless($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500, 'Could not create ZIP archive.');

        $usedNames = [];

        foreach ($docs as $doc) {
            $entryName = $doc->name;
            if (isset($usedNames[strtolower($entryName)])) {
                $usedNames[strtolower($entryName)]++;
                $ext = pathinfo($entryName, PATHINFO_EXTENSION);
                $base = pathinfo($entryName, PATHINFO_FILENAME);
                $entryName = $ext !== '' ? "{$base} ({$usedNames[strtolower($doc->name)]}).{$ext}" : "{$base} ({$usedNames[strtolower($doc->name)]})";
            } else {
                $usedNames[strtolower($entryName)] = 0;
            }

            if ($doc->isStoredFile() && Storage::disk('local')->exists($doc->file_path)) {
                $zip->addFile(Storage::disk('local')->path($doc->file_path), $entryName);
            } else {
                $zip->addFromString($entryName, $this->toPlainText($doc));
            }
        }

        $zip->close();

        return $zipPath;
    }

    /**
     * Isi dokumen sebagai teks biasa (untuk download).
     */
    public function toPlainText(Document $document): string
    {
        return html_to_text((string) $document->content);
    }

    /**
     * Buat shareable read-only link baru (menonaktifkan link aktif sebelumnya).
     */
    public function createShareLink(Document $document, User $user, string $expiry = '24h'): DocumentShareLink
    {
        $document->shareLinks()->where('is_active', true)->update(['is_active' => false]);

        $expiresAt = match ($expiry) {
            '1h' => now()->addHour(),
            '24h' => now()->addDay(),
            '7d' => now()->addDays(7),
            '30d' => now()->addDays(30),
            default => null,
        };

        $link = DocumentShareLink::create([
            'document_id' => $document->id,
            'created_by' => $user->id,
            'token' => Str::random(48),
            'expires_at' => $expiresAt,
            'is_active' => true,
        ]);

        record_document_log($document, 'created a shareable read-only link', $user);

        return $link;
    }

    /**
     * Nonaktifkan (revoke) seluruh shareable link aktif pada dokumen.
     */
    public function revokeShareLinks(Document $document, ?User $user = null): void
    {
        $document->shareLinks()->where('is_active', true)->update(['is_active' => false]);
        record_document_log($document, 'revoked shareable read-only link', $user);
    }
}
