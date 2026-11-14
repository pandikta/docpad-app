<?php

use App\Helpers\AuthHelper;
use App\Models\Document;
use App\Models\DocumentLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

if (! function_exists('is_superadmin')) {
    /**
     * Cek apakah user yang diberikan (atau yang sedang login) adalah superadmin.
     */
    function is_superadmin(?User $user = null): bool
    {
        return AuthHelper::isSuperAdmin($user);
    }
}

if (! function_exists('can_access_team')) {
    /**
     * Cek apakah user berhak mengakses data milik team_id tertentu (superadmin selalu true).
     */
    function can_access_team(?int $teamId, ?User $user = null): bool
    {
        return AuthHelper::canAccessTeam($teamId, $user);
    }
}

if (! function_exists('text_to_html')) {
    /**
     * Konversi teks biasa menjadi HTML (satu <p> per baris) agar siap dibuka di Editor.
     */
    function text_to_html(string $text): string
    {
        $lines = preg_split('/\R/', $text) ?: [];

        return collect($lines)
            ->map(fn ($line) => '<p>'.($line === '' ? '<br>' : e($line)).'</p>')
            ->implode('');
    }
}

if (! function_exists('html_to_text')) {
    /**
     * Konversi HTML dari Editor menjadi teks biasa (satu baris per paragraf).
     */
    function html_to_text(string $html): string
    {
        $text = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $text = preg_replace('/<\/(p|div|h[1-6]|li)>/i', "\n", $text);

        return rtrim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'))."\n";
    }
}

if (! function_exists('record_document_log')) {
    /**
     * Catat aktivitas/perubahan pada dokumen ke tabel doc_logs.
     */
    function record_document_log(Document $document, string $action, ?User $user = null): DocumentLog
    {
        $user ??= Auth::user();

        return DocumentLog::create([
            'document_id' => $document->id,
            'user_id' => $user?->id ?? $document->user_id,
            'action' => $action,
        ]);
    }
}

if (! function_exists('allowed_document_extensions')) {
    /**
     * Daftar ekstensi file dokumen yang diperbolehkan di aplikasi.
     *
     * @return list<string>
     */
    function allowed_document_extensions(): array
    {
        return ['txt', 'md'];
    }
}

if (! function_exists('has_allowed_document_extension')) {
    /**
     * Cek apakah nama file memiliki nama dasar dan diakhiri ekstensi yang diperbolehkan.
     */
    function has_allowed_document_extension(string $filename): bool
    {
        $filename = trim($filename);
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));

        return $baseName !== '' && in_array($extension, allowed_document_extensions(), true);
    }
}
