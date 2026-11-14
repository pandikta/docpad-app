<?php

namespace App\Services;

use App\Models\AppFunction;
use App\Models\FunctionAuth;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;

class FunctionAuthService
{
    /**
     * Cek apakah user berhak menjalankan fungsi tertentu.
     * Prioritas:
     * 1. Superadmin -> selalu true
     * 2. Override khusus user (user_id)
     * 3. Aturan team (team_id)
     * 4. Nilai default_allowed pada mst_functions
     */
    public function can(User $user, string $functionCode): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $permissions = $this->getPermissionsMap($user);

        return $permissions[$functionCode] ?? false;
    }

    /**
     * Peta seluruh function code => boolean untuk user yang sedang login.
     *
     * @return array<string, bool>
     */
    public function getPermissionsMap(User $user): array
    {
        $functions = AppFunction::all(['id', 'code', 'default_allowed']);

        if ($user->isSuperAdmin()) {
            return $functions->mapWithKeys(fn (AppFunction $fn) => [$fn->code => true])->all();
        }

        $teamRules = $user->team_id
            ? FunctionAuth::where('team_id', $user->team_id)->pluck('is_allowed', 'function_id')
            : collect();

        $userRules = FunctionAuth::where('user_id', $user->id)->pluck('is_allowed', 'function_id');

        return $functions->mapWithKeys(function (AppFunction $fn) use ($userRules, $teamRules) {
            if ($userRules->has($fn->id)) {
                return [$fn->code => (bool) $userRules->get($fn->id)];
            }

            if ($teamRules->has($fn->id)) {
                return [$fn->code => (bool) $teamRules->get($fn->id)];
            }

            return [$fn->code => (bool) $fn->default_allowed];
        })->all();
    }

    /**
     * Data lengkap untuk halaman manajemen Function Authorization (khusus Superadmin).
     *
     * @return array<string, mixed>
     */
    public function getManagementData(): array
    {
        $functions = AppFunction::orderBy('group')->orderBy('id')->get([
            'id', 'code', 'name', 'group', 'description', 'default_allowed',
        ]);

        $teams = Team::where('is_active', 1)
            ->orderBy('team_name')
            ->get(['id', 'team_name', 'team_code'])
            ->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->team_name,
                'code' => $team->team_code,
            ]);

        $users = User::where('is_superadmin', false)
            ->with('team:id,team_name')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'team_id'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'team_id' => $user->team_id,
                'team_name' => $user->team?->team_name,
            ]);

        $authRules = FunctionAuth::all(['id', 'function_id', 'team_id', 'user_id', 'is_allowed']);

        return [
            'functions' => $functions,
            'teams' => $teams,
            'users' => $users,
            'authRules' => $authRules,
        ];
    }

    /**
     * Simpan atau perbarui aturan izin untuk Team atau User tertentu.
     */
    public function updatePermission(int $functionId, bool $isAllowed, ?int $teamId = null, ?int $userId = null): FunctionAuth
    {
        $match = $teamId !== null
            ? ['function_id' => $functionId, 'team_id' => $teamId]
            : ['function_id' => $functionId, 'user_id' => $userId];

        return FunctionAuth::updateOrCreate($match, [
            'team_id' => $teamId,
            'user_id' => $userId,
            'is_allowed' => $isAllowed,
        ]);
    }

    /**
     * Hapus override user tertentu agar kembali mengikuti aturan timnya.
     */
    public function resetUserOverride(int $functionId, int $userId): void
    {
        FunctionAuth::where('function_id', $functionId)
            ->where('user_id', $userId)
            ->delete();
    }
}
