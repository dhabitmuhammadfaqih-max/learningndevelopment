<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

/**
 * Halaman & endpoint inbox notifikasi in-app.
 *
 * Semua route di sini WAJIB lewat middleware 'auth' (didaftarkan di
 * routes/web.php) dan selalu discope ke user yang sedang login - user
 * tidak pernah bisa lihat/ubah notifikasi milik user lain.
 */
class NotificationController extends Controller
{
    /**
     * Halaman daftar notifikasi (dipaginasi terbaru dulu).
     */
    public function index(Request $request)
    {
        $notifications = Notification::forUser($request->user()->id)
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Dipanggil lewat AJAX oleh dropdown lonceng di topbar - jumlah belum
     * dibaca + beberapa notifikasi terbaru untuk preview cepat tanpa
     * pindah halaman.
     */
    public function preview(Request $request)
    {
        $userId = $request->user()->id;

        return response()->json([
            'unread_count' => Notification::forUser($userId)->unread()->count(),
            'items' => Notification::forUser($userId)
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn (Notification $n) => [
                    'id'       => $n->id,
                    'title'    => $n->title,
                    'body'     => \Illuminate\Support\Str::limit($n->body, 90),
                    'url'      => $n->url,
                    'is_read'  => $n->isRead(),
                    'time_ago' => $n->created_at->diffForHumans(),
                ]),
        ]);
    }

    /**
     * Tandai satu notifikasi sudah dibaca (dipanggil saat notifikasi
     * di-klik, sebelum redirect ke $notification->url).
     */
    public function markRead(Request $request, Notification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->markAsRead();

        return response()->json(['message' => 'Ditandai sudah dibaca.']);
    }

    /**
     * Tandai SEMUA notifikasi milik user ini sudah dibaca.
     */
    public function markAllRead(Request $request)
    {
        Notification::forUser($request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);

        return back()->with('success', true);
    }
}
