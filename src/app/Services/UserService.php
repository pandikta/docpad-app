<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Buat user baru yang bergabung ke team tertentu.
     *
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function registerForTeam(Team $team, array $data): User
    {
        return User::create([
            'team_id' => $team->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
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
        $user->update([
            'password' => Hash::make($password),
        ]);
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
            ->orderByDesc('is_superadmin')
            ->orderBy('name')
            ->get(['id', 'team_id', 'name', 'email', 'is_superadmin', 'created_at'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'team_id' => $user->team_id,
                'team_name' => $user->team?->team_name,
                'name' => $user->name,
                'email' => $user->email,
                'is_superadmin' => (bool) $user->is_superadmin,
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

        return User::make([
            'team_id' => $isSuperAdmin ? null : ($data['team_id'] ?? null),
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'password' => Hash::make($data['password']),
        ])->forceFill([
            'is_superadmin' => $isSuperAdmin,
            'email_verified_at' => now(),
        ])->tap(fn (User $user) => $user->save());
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
        ])->forceFill([
            'is_superadmin' => $isSuperAdmin,
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return $user;
    }
}
