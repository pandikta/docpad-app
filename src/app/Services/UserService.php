<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
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
}
