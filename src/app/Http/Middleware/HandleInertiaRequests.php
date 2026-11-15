<?php

namespace App\Http\Middleware;

use App\Services\FunctionAuthService;
use App\Services\MenuAuthService;
use Illuminate\Http\Request;
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
                    ...$request->user()->only('id', 'name', 'email', 'email_verified_at'),
                    'is_superadmin' => $request->user()->isSuperAdmin(),
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
            ],
        ];
    }
}
