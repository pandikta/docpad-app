<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeamSeeder extends Seeder
{
    /**
     * Seed the teams table.
     */
    public function run(): void
    {
        $teams = [
            [
                'team_code' => 'ta-team',
                'team_name' => 'ta team',
                'description' => 'tes team',
            ],
        ];

        foreach ($teams as $team) {
            // Idempotent: aman dijalankan berulang (key: team_code)
            DB::table('mst_teams')->updateOrInsert(
                ['team_code' => $team['team_code']],
                $team + ['updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
