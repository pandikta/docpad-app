<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Ambil daftar notifikasi terbaru user (dipakai untuk auto-check/polling ringan).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->limit(100)->toBase()->get(['id'])->count(),
            'items' => $user->notifications()->latest()->limit(15)->get()->map(fn ($n) => [
                'id' => $n->id,
                'data' => $n->data,
                'read_at' => $n->read_at?->toISOString(),
                'created_at' => $n->created_at?->toISOString(),
            ]),
        ]);
    }

    /**
     * Tandai satu notifikasi milik user sebagai sudah dibaca.
     */
    public function markAsRead(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if ($notification && $notification->read_at === null) {
            $notification->markAsRead();
        }

        return back();
    }

    /**
     * Tandai semua notifikasi belum dibaca milik user sebagai sudah dibaca.
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
