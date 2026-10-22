<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->studentUser($request);
        $filterUnread = $request->boolean('unread');
        $notifications = $user->notifications()
            ->when($filterUnread, fn ($query) => $query->whereNull('read_at'))
            ->paginate(15)
            ->withQueryString();
        $unreadCount = $user->unreadNotifications()->count();

        return view('students.notifications.index', compact('notifications', 'unreadCount', 'filterUnread'));
    }

    public function read(Request $request, Notification $notification): RedirectResponse
    {
        $user = $this->studentUser($request);
        $ownedNotification = $user->notifications()->findOrFail($notification->id);
        $ownedNotification->markAsRead();

        if ($ownedNotification->link) {
            // Hanya jalur di situs ini yang dipakai, agar tautan lama tetap benar saat domain berganti.
            $path = parse_url($ownedNotification->link, PHP_URL_PATH) ?: '/';
            $query = parse_url($ownedNotification->link, PHP_URL_QUERY);

            return redirect()->to($path.($query ? '?'.$query : ''));
        }

        return redirect()->route('student.notifications.index')->with('success', 'Notifikasi telah ditandai dibaca.');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $user = $this->studentUser($request);
        $user->unreadNotifications()->update(['read_at' => now()]);

        return back(fallback: route('student.notifications.index'))->with('success', 'Semua notifikasi telah ditandai sebagai dibaca.');
    }

    private function studentUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->role === 'siswa', 403);

        return $user;
    }
}
