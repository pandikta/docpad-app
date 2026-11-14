<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    // Kredensial superadmin (sengaja ditulis di sini supaya mudah diketahui).
    // Ganti password ini sebelum dipakai di production!
    private const EMAIL = 'superadmin@docpad.local';
    private const PASSWORD = 'SuperAdmin@123';

    /**
     * Seed akun superadmin. Aman dijalankan berulang (key: email).
     */
    public function run(): void
    {
        // Superadmin dikecualikan dari ikatan team (team_id = null).
        // forceFill karena is_superadmin tidak boleh ikut mass-assignment ($fillable).
        // Password otomatis di-hash oleh cast 'hashed' pada model User.
        User::firstOrNew(['email' => self::EMAIL])
            ->forceFill([
                'team_id' => null,
                'name' => 'Super Admin',
                'password' => self::PASSWORD,
                'is_superadmin' => true,
                'email_verified_at' => now(),
            ])
            ->save();
    }
}
