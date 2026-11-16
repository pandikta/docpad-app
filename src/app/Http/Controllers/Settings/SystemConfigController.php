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
                'guest_language_switcher' => SystemConfig::getBool('guest_language_switcher', true),
                'email_notif_team_request' => SystemConfig::getBool('email_notif_team_request', true),
                'email_notif_team_leader_request' => SystemConfig::getBool('email_notif_team_leader_request', true),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password_history_limit' => ['required', 'integer', 'min:0', 'max:20'],
            'guest_language_switcher' => ['required', 'boolean'],
            'email_notif_team_request' => ['required', 'boolean'],
            'email_notif_team_leader_request' => ['required', 'boolean'],
        ]);

        SystemConfig::setValue('password_history_limit', (string) $validated['password_history_limit']);
        SystemConfig::setValue('guest_language_switcher', $validated['guest_language_switcher'] ? '1' : '0');
        SystemConfig::setValue('email_notif_team_request', $validated['email_notif_team_request'] ? '1' : '0');
        SystemConfig::setValue('email_notif_team_leader_request', $validated['email_notif_team_leader_request'] ? '1' : '0');

        return back()->with('success', 'System configuration updated.');
    }
}
