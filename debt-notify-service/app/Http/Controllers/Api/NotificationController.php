<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Utang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->attributes->get('user_id');

        $notifications = Notification::where('user_id', $userId)
            ->latest()
            ->paginate(20);

        return response()->json($notifications);
    }

    public function markAsRead(Request $request, $id)
    {
        $userId = $request->attributes->get('user_id');
        $notification = Notification::where('user_id', $userId)->findOrFail($id);
        $notification->update(['read_at' => now()]);

        return response()->json(['message' => 'Notifikasi ditandai sudah dibaca']);
    }

    public function destroy(Request $request, $id)
    {
        $userId = $request->attributes->get('user_id');
        $notification = Notification::where('user_id', $userId)->findOrFail($id);
        $notification->delete();

        return response()->json(['message' => 'Notifikasi dihapus']);
    }

    // Admin broadcast endpoint
    public function broadcast(Request $request)
    {
        // Verify admin role from auth_user attribute set by remote.auth
        $authUser = $request->attributes->get('auth_user');
        if (!$authUser || ($authUser['tipe_akun'] ?? '') !== 'admin') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:191',
            'message' => 'required|string',
            'type' => 'required|in:info,warning,success,danger',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer',
            'send_all' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $userIds = [];
        if ($request->boolean('send_all')) {
            try {
                $base = rtrim(env('AUTH_SERVICE_URL', 'http://auth-service:8000'), '/');
                $token = $request->bearerToken();
                // Get page 1 of user list from auth-service
                $resp = Http::acceptJson()->withToken($token)->get($base . '/api/auth/admin/users');
                if ($resp->successful()) {
                    $data = $resp->json();
                    $lastPage = $data['last_page'] ?? 1;
                    foreach ($data['data'] as $u) {
                        if (!empty($u['id'])) {
                            $userIds[] = $u['id'];
                        }
                    }
                    // Fetch other pages if any
                    for ($page = 2; $page <= $lastPage; $page++) {
                        $respPage = Http::acceptJson()->withToken($token)->get($base . '/api/auth/admin/users?page=' . $page);
                        if ($respPage->successful()) {
                            $dataPage = $respPage->json();
                            foreach ($dataPage['data'] as $u) {
                                if (!empty($u['id'])) {
                                    $userIds[] = $u['id'];
                                }
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // fallback to unique user_ids from local utang table if auth-service fails
                $userIds = Utang::pluck('user_id')->unique()->toArray();
            }

            // If still empty (e.g. fresh installation), we cannot broadcast to anyone
            if (empty($userIds)) {
                return response()->json(['message' => 'Tidak ada user untuk dikirimi notifikasi.'], 400);
            }
        } elseif ($request->filled('user_ids')) {
            $userIds = $request->user_ids;
        }

        $userIds = array_unique($userIds);
        $createdCount = 0;

        foreach ($userIds as $userId) {
            Notification::create([
                'user_id' => $userId,
                'title' => $request->title,
                'message' => $request->message,
                'type' => $request->type,
            ]);
            $createdCount++;
        }

        return response()->json([
            'message' => "Notifikasi berhasil dikirim ke {$createdCount} user."
        ]);
    }
}
