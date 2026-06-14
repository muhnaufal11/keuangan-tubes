<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::select('id', 'name', 'username', 'email', 'created_at', 'tipe_akun', 'is_banned', 'last_login_at')
            ->latest()
            ->paginate(20);

        return response()->json($users);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    // Fitur Ban / Unban User
    public function ban(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Jangan ban diri sendiri
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Tidak bisa memblokir akun sendiri'], 403);
        }

        $user->is_banned = !$user->is_banned;
        $user->save();

        $status = $user->is_banned ? 'Diblokir' : 'Aktif';

        return response()->json([
            'message' => "User berhasil diubah statusnya menjadi: $status",
            'data' => $user
        ]);
    }
}
