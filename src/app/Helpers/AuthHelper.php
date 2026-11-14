<?php

namespace App\Helpers;

use App\Models\Document;
use App\Models\User;
use App\Services\FunctionAuthService;
use Illuminate\Support\Facades\Auth;

class AuthHelper
{
    /**
     * Cek apakah user yang diberikan (atau yang sedang login) adalah superadmin.
     */
    public static function isSuperAdmin(?User $user = null): bool
    {
        $user ??= Auth::user();

        return (bool) $user?->isSuperAdmin();
    }

    /**
     * Cek apakah user berhak mengakses data milik team_id tertentu.
     * Superadmin selalu dikecualikan (return true).
     */
    public static function canAccessTeam(?int $teamId, ?User $user = null): bool
    {
        $user ??= Auth::user();

        if (! $user) {
            return false;
        }

        return $user->canAccessTeam($teamId);
    }

    /**
     * Cek apakah user berhak mengakses dokumen (superadmin selalu true, user biasa cek pivot doc_file_teams).
     */
    public static function canAccessDocument(Document $document, ?User $user = null): bool
    {
        $user ??= Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->team_id) {
            return false;
        }

        return $document->teams()->where('mst_teams.id', $user->team_id)->exists();
    }

    /**
     * Cek apakah user berhak menjalankan fungsi/aksi tertentu berdasarkan Function Authorization.
     */
    public static function canUseFunction(string $functionCode, ?User $user = null): bool
    {
        $user ??= Auth::user();

        if (! $user) {
            return false;
        }

        return app(FunctionAuthService::class)->can($user, $functionCode);
    }
}
