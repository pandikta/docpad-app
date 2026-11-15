<?php

namespace App\Services;

use App\Models\DocumentExtension;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ExtensionService
{
    /**
     * Daftar kode ekstensi aktif (mis. ['txt', 'md']).
     * Aman dipanggil meski tabel belum dimigrasi atau kosong.
     *
     * @return list<string>
     */
    public function listActiveCodes(): array
    {
        try {
            if (! Schema::hasTable('mst_extensions')) {
                return ['txt', 'md'];
            }

            $codes = DocumentExtension::where('is_active', true)
                ->orderBy('id')
                ->pluck('extension')
                ->map(fn (string $ext) => strtolower(ltrim($ext, '.')))
                ->values()
                ->all();

            return count($codes) > 0 ? $codes : ['txt', 'md'];
        } catch (\Throwable) {
            return ['txt', 'md'];
        }
    }

    /**
     * Daftar seluruh ekstensi untuk halaman Master Extensions.
     *
     * @return Collection<int, DocumentExtension>
     */
    public function listAll(): Collection
    {
        return DocumentExtension::orderBy('id')->get();
    }

    /**
     * Tambah ekstensi baru.
     *
     * @param  array{extension: string, label: string, description?: ?string, is_active?: bool}  $data
     */
    public function create(array $data): DocumentExtension
    {
        return DocumentExtension::create([
            'extension' => strtolower(ltrim(trim($data['extension']), '.')),
            'label' => trim($data['label']),
            'description' => isset($data['description']) ? trim($data['description']) : null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Perbarui data ekstensi.
     *
     * @param  array{extension: string, label: string, description?: ?string, is_active?: bool}  $data
     */
    public function update(DocumentExtension $extension, array $data): DocumentExtension
    {
        $extension->update([
            'extension' => strtolower(ltrim(trim($data['extension']), '.')),
            'label' => trim($data['label']),
            'description' => isset($data['description']) ? trim($data['description']) : null,
            'is_active' => $data['is_active'] ?? $extension->is_active,
        ]);

        return $extension;
    }

    /**
     * Hapus ekstensi.
     */
    public function delete(DocumentExtension $extension): void
    {
        $extension->delete();
    }
}
