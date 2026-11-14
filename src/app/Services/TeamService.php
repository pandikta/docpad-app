<?php

namespace App\Services;

use App\Models\Team;
use Illuminate\Support\Collection;

class TeamService
{
    /**
     * Cari team aktif berdasarkan team_code. Mengembalikan null jika tidak ada.
     */
    public function findActiveByCode(string $teamCode): ?Team
    {
        return Team::where('team_code', trim($teamCode))
            ->where('is_active', 1)
            ->first();
    }

    /**
     * Daftar seluruh team aktif untuk pilihan assign dokumen.
     *
     * @return Collection<int, array{id: int, name: string, code: string}>
     */
    public function listActiveTeams(): Collection
    {
        return Team::where('is_active', 1)
            ->orderBy('team_name')
            ->get(['id', 'team_name', 'team_code'])
            ->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->team_name,
                'code' => $team->team_code,
            ]);
    }
}
