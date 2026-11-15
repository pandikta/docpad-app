<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Ambil CSRF token baru saat session sebelumnya sudah kedaluwarsa.
     */
    public function reloginCsrf(Request $request): JsonResponse
    {
        return response()->json([
            'csrf_token' => $request->session()->token(),
        ]);
    }

    /**
     * Re-autentikasi user dari modal Access Relogin tanpa kehilangan halaman aktif.
     */
    public function relogin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']])) {
            return response()->json([
                'message' => 'Incorrect password. Please try again.',
            ], 422);
        }

        $request->session()->regenerate();

        return response()->json([
            'ok' => true,
            'csrf_token' => $request->session()->token(),
        ]);
    }
}
