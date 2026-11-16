<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Seed akun superadmin. Aman dijalankan berulang (key: email).
     * Kredensial dibaca dari config/app.php (SUPERADMIN_EMAIL & SUPERADMIN_PASSWORD di .env).
     */
    public function run(): void
    {
        $email = (string) config('app.superadmin_email', '');
        $password = (string) config('app.superadmin_password', '');

        // Superadmin dikecualikan dari ikatan team (team_id = null).
        // forceFill karena is_superadmin tidak boleh ikut mass-assignment ($fillable).
        // Password otomatis di-hash oleh cast 'hashed' pada model User.
        User::firstOrNew(['email' => $email])
            ->forceFill([
                'team_id' => null,
                'name' => 'Super Admin',
                'password' => $password,
                'is_superadmin' => true,
                'email_verified_at' => now(),
            ])
            ->save();
    }
}
