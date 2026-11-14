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
     * Cek apakah jumlah anggota team sudah mencapai batas max_member.
     */
    public function hasReachedMaxMembers(Team $team): bool
    {
        if ($team->max_member <= 0) {
            return false;
        }

        return $team->users()->count() >= $team->max_member;
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
