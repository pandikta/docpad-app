<?php

namespace App\Services;

use App\Models\SystemConfig;
use App\Models\User;
use App\Models\UserPasswordHistory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PasswordHistoryService
{
    public function getRestrictLastCount(): int
    {
        return max(0, SystemConfig::getInt('password_history_limit', 2));
    }

    /**
     * Pastikan password baru tidak sama dengan password aktif maupun N riwayat password terakhir.
     *
     * @throws ValidationException
     */
    public function ensureNotRecentlyUsed(User $user, string $plainPassword): void
    {
        $limit = $this->getRestrictLastCount();
        if ($limit === 0) {
            return;
        }

        if ($user->password && Hash::check($plainPassword, $user->password)) {
            throw ValidationException::withMessages([
                'password' => "You cannot reuse your current or last {$limit} password(s).",
            ]);
        }

        if (! Schema::hasTable('mst_user_password_histories')) {
            return;
        }

        $recentHashes = UserPasswordHistory::where('user_id', $user->id)
            ->latest('id')
            ->limit($limit)
            ->pluck('password_hash');

        foreach ($recentHashes as $hash) {
            if ($hash && Hash::check($plainPassword, $hash)) {
                throw ValidationException::withMessages([
                    'password' => "You cannot reuse your last {$limit} password(s).",
                ]);
            }
        }
    }

    /**
     * Catat hash password ke riwayat password user.
     */
    public function record(User $user, string $hashedPassword): void
    {
        if (! Schema::hasTable('mst_user_password_histories') || $hashedPassword === '') {
            return;
        }

        UserPasswordHistory::create([
            'user_id' => $user->id,
            'password_hash' => $hashedPassword,
            'created_at' => now(),
        ]);
    }
}
