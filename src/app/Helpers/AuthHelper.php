<?php

namespace App\Helpers;

use App\Models\User;
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
}
