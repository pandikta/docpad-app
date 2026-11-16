<?php

namespace App\Http\Middleware;

use App\Services\DocumentService;
use App\Services\FunctionAuthService;
use App\Services\MenuAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                // Hanya field yang aman & dibutuhkan FE (jangan kirim model User utuh).
                'user' => $request->user() ? [
                    ...$request->user()->only('id', 'name', 'email', 'team_id', 'email_verified_at'),
                    'is_superadmin' => $request->user()->isSuperAdmin(),
                    'is_team_leader' => $request->user()->isTeamLeader(),
                    'is_reset' => (bool) ($request->user()->is_reset ?? false),
                    'last_reset' => $request->user()->last_reset?->toISOString(),
                ] : null,
                'permissions' => fn () => $request->user()
                    ? app(FunctionAuthService::class)->getPermissionsMap($request->user())
                    : [],
                'menus' => fn () => $request->user()
                    ? app(MenuAuthService::class)->getPermissionsMap($request->user())
                    : [],
                'visibleMenus' => fn () => $request->user()
                    ? app(MenuAuthService::class)->getVisibleMenusForUser($request->user())
                    : ['main' => [], 'settings' => []],
            ],
            // Flash message (mis. ->with('success', '...')) yang ditampilkan sebagai toast di FE.
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
            'notifications' => fn () => $request->user() && Schema::hasTable('notifications') ? [
                'unread_count' => $request->user()->unreadNotifications()->limit(100)->toBase()->get(['id'])->count(),
                'items' => $request->user()->notifications()->latest()->limit(15)->get()->map(fn ($n) => [
                    'id' => $n->id,
                    'data' => $n->data,
                    'read_at' => $n->read_at?->toISOString(),
                    'created_at' => $n->created_at?->toISOString(),
                ]),
            ] : ['unread_count' => 0, 'items' => []],
            'storageQuota' => fn () => $request->user()
                ? app(DocumentService::class)->getStorageQuotaSummary($request->user())
                : null,
            'sessionLifetime' => (int) config('session.lifetime', 120),
        ];
    }
}
