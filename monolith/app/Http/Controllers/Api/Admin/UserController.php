<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::select('id', 'name', 'email', 'created_at', 'is_admin')
            ->latest()
            ->paginate(20);

        return response()->json($users);
    }

    public function show($id)
    {
        $user = User::with(['rekenings', 'pemasukans', 'pengeluarans'])->findOrFail($id);
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

        // Toggle status ban (asumsi ada kolom 'is_active' atau implementasi ban logic)
        // Jika kolom 'is_active' belum ada, kita bisa gunakan logika lain atau menambahkan kolom tsb.
        // Di sini saya asumsikan kita punya kolom 'is_banned' atau 'is_active'

        // Cek migrasi Anda, jika tidak ada, Anda perlu menambahkannya.
        // Sebagai contoh saya gunakan field dummy 'is_active'
        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'Aktif' : 'Diblokir';

        return response()->json(['message' => "User berhasil diubah statusnya menjadi: $status", 'data' => $user]);
    }
}
