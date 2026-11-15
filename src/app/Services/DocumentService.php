<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class DocumentService
{
    /**
     * Daftar dokumen untuk user yang login (superadmin melihat semua dokumen lintas tim).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function listForUser(User $user, ?string $search = null): Collection
    {
        return Document::forUser($user)
            ->search($search)
            ->with(['user:id,name', 'teams:id,team_name', 'logs.user:id,name'])
            ->latest()
            ->get()
            ->map(fn (Document $document) => $document->toListItem());
    }

    /**
     * Pastikan user berhak mengakses dokumen (superadmin otomatis lolos).
     */
    public function authorizeAccess(User $user, Document $document): void
    {
        abort_unless(can_access_document($document, $user), 403);
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
     * Buat dokumen baru dari file teks yang di-upload.
     */
    public function createFromUpload(User $user, UploadedFile $file): Document
    {
        $raw = (string) file_get_contents($file->getRealPath());

        $document = Document::create([
            'user_id' => $user->id,
            'name' => $file->getClientOriginalName(),
            'visibility' => 'publish',
            'content' => text_to_html($raw),
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
    public function createBlank(User $user, string $name, string $extension, string $content = '', string $visibility = 'publish'): Document
    {
        $extension = ltrim(strtolower(trim($extension)), '.');
        $baseName = trim($name);

        // Jika user mengetik nama sekaligus ekstensinya (mis. "catatan.txt"), hapus akhiran ganda.
        if (str_ends_with(strtolower($baseName), '.'.$extension)) {
            $baseName = substr($baseName, 0, -strlen('.'.$extension));
        }

        $finalName = trim($baseName).'.'.$extension;
        $htmlContent = $content !== '' ? $content : '<p><br></p>';
        $visibility = $visibility === 'private' ? 'private' : 'publish';

        $document = Document::create([
            'user_id' => $user->id,
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
     * Hapus dokumen.
     */
    public function delete(Document $document): void
    {
        $document->delete();
    }

    /**
     * Isi dokumen sebagai teks biasa (untuk download).
     */
    public function toPlainText(Document $document): string
    {
        return html_to_text((string) $document->content);
    }
}
