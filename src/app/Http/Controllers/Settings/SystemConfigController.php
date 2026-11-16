<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SystemConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SystemConfigController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings/SystemConfig', [
            'configs' => [
                'password_history_limit' => SystemConfig::getInt('password_history_limit', 2),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password_history_limit' => ['required', 'integer', 'min:0', 'max:20'],
        ]);

        SystemConfig::setValue('password_history_limit', (string) $validated['password_history_limit']);

        return back()->with('success', 'System configuration updated.');
    }
}
