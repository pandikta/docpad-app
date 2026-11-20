<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LoginActivityService
{
    /**
     * Daftar sesi aktif milik user saat ini (hanya yang belum melewati SESSION_LIFETIME).
     *
     * @return list<array{is_current: bool, device_label: string, device_type: string, ip_address: string, last_active_at: string}>
     */
    public function listActiveSessions(Request $request): array
    {
        $user = $request->user();
        if (! $user || config('session.driver') !== 'database') {
            return [];
        }

        $table = config('session.table', 'sessions');
        if (! Schema::hasTable($table)) {
            return [];
        }

        $currentSessionId = $request->session()->getId();
        $cutoff = now()->subMinutes((int) config('session.lifetime', 120))->getTimestamp();

        return DB::table($table)
            ->where('user_id', $user->id)
            ->where('last_activity', '>=', $cutoff)
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(function ($row) use ($currentSessionId) {
                $parsed = $this->parseUserAgent($row->user_agent);

                return [
                    'session_key' => hash_hmac('sha256', (string) $row->id, (string) config('app.key')),
                    'is_current' => (string) $row->id === (string) $currentSessionId,
                    'device_label' => $parsed['label'],
                    'device_type' => $parsed['device_type'],
                    'ip_address' => (string) ($row->ip_address ?: '—'),
                    'last_active_at' => Carbon::createFromTimestamp((int) $row->last_activity)->toIso8601String(),
                ];
            })
            ->sortByDesc('is_current')
            ->values()
            ->all();
    }

    /**
     * Hapus seluruh sesi aktif milik user kecuali sesi saat ini.
     */
    public function logoutOtherDevices(Request $request): int
    {
        $user = $request->user();
        if (! $user || config('session.driver') !== 'database') {
            return 0;
        }

        $table = config('session.table', 'sessions');
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $currentSessionId = $request->session()->getId();

        return DB::table($table)
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }

    /**
     * Hapus satu sesi aktif tertentu berdasarkan HMAC session_key (bukan raw session ID).
     */
    public function logoutSessionByKey(Request $request, string $sessionKey): bool
    {
        $user = $request->user();
        if (! $user || config('session.driver') !== 'database') {
            return false;
        }

        $table = config('session.table', 'sessions');
        if (! Schema::hasTable($table)) {
            return false;
        }

        $currentSessionId = $request->session()->getId();
        $appKey = (string) config('app.key');

        $targetId = DB::table($table)
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->pluck('id')
            ->first(fn ($id) => hash_equals($sessionKey, hash_hmac('sha256', (string) $id, $appKey)));

        if (! $targetId) {
            return false;
        }

        return DB::table($table)
            ->where('user_id', $user->id)
            ->where('id', $targetId)
            ->delete() > 0;
    }

    /**
     * @return array{label: string, device_type: 'desktop'|'mobile'}
     */
    private function parseUserAgent(?string $ua): array
    {
        $ua = (string) $ua;

        $platform = match (true) {
            str_contains($ua, 'iPhone') => 'iPhone',
            str_contains($ua, 'iPad') => 'iPad',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Macintosh') || str_contains($ua, 'Mac OS X') => 'Mac',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'Unknown OS',
        };

        $browser = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Chrome/') && ! str_contains($ua, 'Edg/') => 'Chrome',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Safari/') && ! str_contains($ua, 'Chrome/') => 'Safari',
            default => 'Browser',
        };

        $isMobile = str_contains($ua, 'Mobile')
            || str_contains($ua, 'Android')
            || str_contains($ua, 'iPhone')
            || str_contains($ua, 'iPad');

        return [
            'label' => "{$browser} - {$platform}",
            'device_type' => $isMobile ? 'mobile' : 'desktop',
        ];
    }
}
