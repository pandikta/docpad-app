<?php

namespace App\Services;

use App\Models\Team;

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
}
