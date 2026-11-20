<?php

namespace App\Http\Controllers;

use App\Services\LoginActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LoginActivityController extends Controller
{
    public function logoutOtherDevices(Request $request, LoginActivityService $loginActivity): RedirectResponse
    {
        $count = $loginActivity->logoutOtherDevices($request);

        $message = $count > 0
            ? "Logged out {$count} other active session(s)."
            : 'No other active sessions found.';

        return back()->with('success', $message);
    }

    public function logoutSession(Request $request, string $sessionKey, LoginActivityService $loginActivity): RedirectResponse
    {
        $removed = $loginActivity->logoutSessionByKey($request, $sessionKey);

        return back()->with(
            $removed ? 'success' : 'warning',
            $removed ? 'Device session logged out.' : 'Session was already ended.',
        );
    }
}
