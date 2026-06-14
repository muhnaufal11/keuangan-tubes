<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Notification::query();

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->status === 'unread') {
            $query->whereNull('read_at');
        } elseif ($request->status === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->latest()->paginate(25);
        return view('admin.notifications.index', compact('notifications'));
    }

    public function create()
    {
        $users = User::orderBy('username')->get();
        return view('admin.notifications.create', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:191',
            'message' => 'required|string',
            'type' => 'required|in:info,warning,success,danger',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer',
            'send_all' => 'nullable|boolean',
        ]);

        $userIds = [];
        if ($request->boolean('send_all')) {
            $userIds = User::pluck('id')->toArray();
        } elseif ($request->filled('user_ids')) {
            $userIds = $request->user_ids;
        }

        foreach ($userIds as $userId) {
            Notification::create([
                'user_id' => $userId,
                'title' => $request->title,
                'message' => $request->message,
                'type' => $request->type,
            ]);
        }

        if (class_exists(\App\Helpers\ActivityLogger::class)) {
            \App\Helpers\ActivityLogger::log(auth()->id() ?? null, 'notification.create', null, 'Admin sent notification to ' . count($userIds) . ' users');
        }

        return redirect()->route('admin.notifications.index')->with('success', 'Notifikasi berhasil dikirim ke ' . count($userIds) . ' user.');
    }

    public function destroy(Notification $notification)
    {
        if (class_exists(\App\Helpers\ActivityLogger::class)) {
            \App\Helpers\ActivityLogger::log(auth()->id() ?? null, 'notification.delete', $notification, 'Admin deleted notification');
        }

        $notification->delete();
        return back()->with('success', 'Notifikasi dihapus.');
    }
}
