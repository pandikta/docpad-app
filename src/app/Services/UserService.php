<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        private readonly PasswordHistoryService $passwordHistory,
    ) {}

    /**
     * Buat user baru yang bergabung ke team tertentu.
     *
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function registerForTeam(Team $team, array $data): User
    {
        $user = User::create([
            'team_id' => $team->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $this->passwordHistory->record($user, $user->password);

        return $user;
    }

    /**
     * Perbarui nama dan email profil user.
     *
     * @param  array{name: string, email: string}  $data
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return $user;
    }

    /**
     * Perbarui password user.
     */
    public function updatePassword(User $user, string $password): void
    {
        $this->passwordHistory->ensureNotRecentlyUsed($user, $password);

        $hash = Hash::make($password);
        $user->update([
            'password' => $hash,
        ]);

        $this->passwordHistory->record($user, $hash);
    }

    /**
     * Hapus akun user.
     */
    public function deleteAccount(User $user): void
    {
        $user->delete();
    }

    /**
     * Daftar seluruh user untuk halaman Master Users.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function listAllForManagement(): Collection
    {
        return User::with('team:id,team_name')
            ->withSum('documents as storage_used_bytes', 'size')
            ->orderByDesc('is_superadmin')
            ->orderBy('name')
            ->get(['id', 'team_id', 'name', 'email', 'is_superadmin', 'is_team_leader', 'storage_limit_mb', 'last_login', 'created_at'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'team_id' => $user->team_id,
                'team_name' => $user->team?->team_name,
                'name' => $user->name,
                'email' => $user->email,
                'is_superadmin' => (bool) $user->is_superadmin,
                'is_team_leader' => (bool) $user->is_team_leader,
                'storage_limit_mb' => $user->storage_limit_mb !== null ? (int) $user->storage_limit_mb : null,
                'storage_used_bytes' => (int) ($user->storage_used_bytes ?? 0),
                'last_login' => $user->last_login?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
            ]);
    }

    /**
     * Buat user baru dari menu Master Users.
     *
     * @param  array{name: string, email: string, password: string, team_id?: ?int, is_superadmin?: bool}  $data
     */
    public function createUser(array $data): User
    {
        $isSuperAdmin = (bool) ($data['is_superadmin'] ?? false);

        $user = User::make([
            'team_id' => $isSuperAdmin ? null : ($data['team_id'] ?? null),
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'password' => Hash::make($data['password']),
            'storage_limit_mb' => array_key_exists('storage_limit_mb', $data)
                ? ($data['storage_limit_mb'] !== null ? (int) $data['storage_limit_mb'] : null)
                : 100,
        ])->forceFill([
            'is_superadmin' => $isSuperAdmin,
            'is_team_leader' => $isSuperAdmin ? false : (bool) ($data['is_team_leader'] ?? false),
            'email_verified_at' => now(),
        ]);

        $user->save();
        $this->passwordHistory->record($user, $user->password);

        return $user;
    }

    /**
     * Perbarui data user dari menu Master Users.
     *
     * @param  array{name: string, email: string, password?: ?string, team_id?: ?int, is_superadmin?: bool}  $data
     */
    public function updateUser(User $user, array $data): User
    {
        $isSuperAdmin = (bool) ($data['is_superadmin'] ?? false);

        $user->fill([
            'team_id' => $isSuperAdmin ? null : ($data['team_id'] ?? null),
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'storage_limit_mb' => array_key_exists('storage_limit_mb', $data)
                ? ($data['storage_limit_mb'] !== null ? (int) $data['storage_limit_mb'] : null)
                : $user->storage_limit_mb,
        ])->forceFill([
            'is_superadmin' => $isSuperAdmin,
            'is_team_leader' => $isSuperAdmin ? false : (bool) ($data['is_team_leader'] ?? false),
        ]);

        if (! empty($data['password'])) {
            $this->passwordHistory->ensureNotRecentlyUsed($user, $data['password']);
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        if (! empty($data['password'])) {
            $this->passwordHistory->record($user, $user->password);
        }

        return $user;
    }
}
