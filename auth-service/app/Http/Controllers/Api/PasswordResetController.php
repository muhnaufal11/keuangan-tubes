<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class PasswordResetController extends Controller
{
    // Retrieve security question for user
    public function getQuestion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::where('email', $request->identifier)
            ->orWhere('username', $request->identifier)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Username atau email tidak ditemukan.'], 404);
        }

        if (!$user->security_question) {
            return response()->json(['message' => 'Akun ini belum mengatur pertanyaan keamanan.'], 400);
        }

        return response()->json([
            'security_question' => $user->security_question
        ]);
    }

    // Reset password using security answer (stateless)
    public function resetWithQuestion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|string',
            'security_answer' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::where('email', $request->identifier)
            ->orWhere('username', $request->identifier)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Username atau email tidak ditemukan.'], 404);
        }

        if (!$user->security_answer) {
            return response()->json(['message' => 'Akun ini belum mengatur pertanyaan keamanan.'], 400);
        }

        if (!Hash::check(strtolower(trim($request->security_answer)), $user->security_answer)) {
            return response()->json(['message' => 'Jawaban salah.'], 400);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'message' => 'Password berhasil diubah. Silakan login.'
        ]);
    }
}
