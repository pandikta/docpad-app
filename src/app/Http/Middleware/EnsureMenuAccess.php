<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMenuAccess
{
    /**
     * Pastikan user berhak mengakses menu tertentu (Superadmin selalu lolos).
     */
    public function handle(Request $request, Closure $next, string $menuCode): Response
    {
        abort_unless(can_access_menu($menuCode, $request->user()), 403);

        return $next($request);
    }
}
