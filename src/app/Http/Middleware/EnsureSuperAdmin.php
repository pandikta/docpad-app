<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Pastikan hanya user superadmin yang dapat mengakses route ini.
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(is_superadmin($request->user()), 403);

        return $next($request);
    }
}
