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
     * Map ekstensi aktif ke batas maksimal ukuran file dalam KB (mis. ['txt' => 1024, 'pdf' => 10240]).
     *
     * @return array<string, int>
     */
    public function listActiveWithLimits(): array
    {
        try {
            if (! Schema::hasTable('mst_extensions')) {
                return ['txt' => 1024, 'md' => 1024];
            }

            $map = DocumentExtension::where('is_active', true)
                ->orderBy('id')
                ->get(['extension', 'max_size_kb'])
                ->mapWithKeys(fn (DocumentExtension $ext) => [
                    strtolower(ltrim($ext->extension, '.')) => (int) ($ext->max_size_kb ?? 1024),
                ])
                ->all();

            return count($map) > 0 ? $map : ['txt' => 1024, 'md' => 1024];
        } catch (\Throwable) {
            return ['txt' => 1024, 'md' => 1024];
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
     * Ambil batas maksimal ukuran file (KB) untuk ekstensi tertentu.
     */
    public function getMaxSizeKbForExtension(string $extension): int
    {
        $normalized = strtolower(ltrim(trim($extension), '.'));

        try {
            if (! Schema::hasTable('mst_extensions')) {
                return 1024;
            }

            $max = DocumentExtension::where('extension', $normalized)
                ->where('is_active', true)
                ->value('max_size_kb');

            return $max ? (int) $max : 1024;
        } catch (\Throwable) {
            return 1024;
        }
    }

    /**
     * Tambah ekstensi baru.
     *
     * @param  array{extension: string, label: string, description?: ?string, max_size_kb?: int, is_active?: bool}  $data
     */
    public function create(array $data): DocumentExtension
    {
        return DocumentExtension::create([
            'extension' => strtolower(ltrim(trim($data['extension']), '.')),
            'label' => trim($data['label']),
            'description' => isset($data['description']) ? trim($data['description']) : null,
            'max_size_kb' => max(1, (int) ($data['max_size_kb'] ?? 1024)),
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Perbarui data ekstensi.
     *
     * @param  array{extension: string, label: string, description?: ?string, max_size_kb?: int, is_active?: bool}  $data
     */
    public function update(DocumentExtension $extension, array $data): DocumentExtension
    {
        $extension->update([
            'extension' => strtolower(ltrim(trim($data['extension']), '.')),
            'label' => trim($data['label']),
            'description' => isset($data['description']) ? trim($data['description']) : null,
            'max_size_kb' => max(1, (int) ($data['max_size_kb'] ?? $extension->max_size_kb ?? 1024)),
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
