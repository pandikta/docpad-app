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

    /**
     * Daftar seluruh team beserta jumlah anggota untuk halaman Master Teams.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function listAllForManagement(): Collection
    {
        return Team::withCount('users')
            ->orderBy('team_name')
            ->get()
            ->map(fn (Team $team) => [
                'id' => $team->id,
                'team_code' => $team->team_code,
                'team_name' => $team->team_name,
                'max_member' => $team->max_member,
                'users_count' => $team->users_count,
                'description' => $team->description,
                'is_active' => (bool) $team->is_active,
                'allow_multiple_leaders' => (bool) $team->allow_multiple_leaders,
            ]);
    }

    /**
     * Buat team baru.
     *
     * @param  array{team_code: string, team_name: string, max_member: int, description?: ?string, is_active?: bool, allow_multiple_leaders?: bool}  $data
     */
    public function create(array $data): Team
    {
        return Team::create([
            'team_code' => trim($data['team_code']),
            'team_name' => trim($data['team_name']),
            'max_member' => (int) $data['max_member'],
            'description' => isset($data['description']) ? trim($data['description']) : null,
            'is_active' => ($data['is_active'] ?? true) ? 1 : 0,
            'allow_multiple_leaders' => (bool) ($data['allow_multiple_leaders'] ?? false),
        ]);
    }

    /**
     * Perbarui data team.
     *
     * @param  array{team_code: string, team_name: string, max_member: int, description?: ?string, is_active?: bool, allow_multiple_leaders?: bool}  $data
     */
    public function update(Team $team, array $data): Team
    {
        $team->update([
            'team_code' => trim($data['team_code']),
            'team_name' => trim($data['team_name']),
            'max_member' => (int) $data['max_member'],
            'description' => isset($data['description']) ? trim($data['description']) : null,
            'is_active' => ($data['is_active'] ?? true) ? 1 : 0,
            'allow_multiple_leaders' => (bool) ($data['allow_multiple_leaders'] ?? $team->allow_multiple_leaders ?? false),
        ]);

        return $team;
    }

    /**
     * Hapus team.
     */
    public function delete(Team $team): void
    {
        $team->delete();
    }
}
