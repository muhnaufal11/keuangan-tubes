<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    // Menampilkan Halaman Login
    public function showLogin() { 
        return view('auth.login'); 
    }

    // Menampilkan Halaman Register
    public function showRegister() { 
        return view('auth.register'); 
    }

    // Proses Register (DIPERBAIKI: Menghapus 'name', menggunakan 'username')
    public function register(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'username' => 'required|unique:users', // Cek unik username
            'password' => 'required|min:6',
            'security_question' => 'required',
            'security_answer' => 'required',
        ]);

        // 2. Simpan ke Database
        User::create([
            'name' => $request->username, // Set name sama dengan username
            'username' => $request->username, // Pakai username
            // 'name' => $request->name, <--- BARIS INI SUDAH DIHAPUS AGAR TIDAK ERROR
            'password' => Hash::make($request->password),
            'security_question' => $request->security_question,
            // Hash jawaban keamanan agar aman
            'security_answer' => Hash::make(strtolower(trim($request->security_answer))),
            'tipe_akun' => 'gratis', // Set default tipe akun
        ]);

        return redirect()->route('login')->with('success', 'Registrasi berhasil! Silakan login.');
    }

    // Proses Login
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('dashboard');
        }

        return back()->with('error', 'Username atau password salah.');
    }

    // Proses Logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    // --- GOOGLE LOGIN ---
    public function redirectToGoogle() { 
        return Socialite::driver('google')->redirect(); 
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // Cek user berdasarkan Google ID atau Email
            $user = User::where('google_id', $googleUser->getId())
                        ->orWhere('email', $googleUser->getEmail())
                        ->first();

            if (!$user) {
                // Register User Baru dari Google
                $user = User::create([
                    'name' => $googleUser->getName(), // Set name dari Google
                    'username' => $googleUser->getName(), // Gunakan nama Google sebagai username awal
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'tipe_akun' => 'gratis',
                    'password' => null, // User Google tidak butuh password
                ]);
            } else {
                // Jika user ada tapi belum link Google ID
                if (!$user->google_id) {
                    $user->update(['google_id' => $googleUser->getId()]);
                }
            }

            Auth::login($user);
            return redirect()->route('dashboard');
            
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Login Google Gagal: ' . $e->getMessage());
        }
    }

    // --- LUPA PASSWORD DENGAN PERTANYAAN KEAMANAN ---
    public function showForgotPassword()
    {
        return view('auth.forgot');
    }

    public function sendSecurityQuestion(Request $request)
    {
        $request->validate([
            'identifier' => 'required',
        ]);

        $user = User::where('username', $request->identifier)
                    ->orWhere('email', $request->identifier)
                    ->first();

        if (!$user) {
            return back()->with('error', 'Username atau email tidak ditemukan.');
        }

        if (!$user->security_question) {
            return back()->with('error', 'Akun ini belum mengatur pertanyaan keamanan. Silakan hubungi admin.');
        }

        // Simpan user id di session untuk verifikasi jawaban
        session(['reset_user_id' => $user->id]);

        return view('auth.forgot', [
            'show_question' => true,
            'question' => $user->security_question,
        ]);
    }

    public function verifySecurityAnswer(Request $request)
    {
        $request->validate([
            'answer' => 'required',
        ]);

        $userId = session('reset_user_id');
        if (!$userId) {
            return redirect()->route('password.request')->with('error', 'Sesi expired. Silakan ulangi.');
        }

        $user = User::find($userId);
        if (!$user) {
            return redirect()->route('password.request')->with('error', 'User tidak ditemukan.');
        }

        if (!Hash::check(strtolower(trim($request->answer)), $user->security_answer)) {
            return back()->with('error', 'Jawaban salah.');
        }

        // Jawaban benar, redirect ke reset password
        session(['verified_user_id' => $user->id]);

        return redirect()->route('password.reset.form');
    }

    public function showResetForm()
    {
        if (!session('verified_user_id')) {
            return redirect()->route('password.request')->with('error', 'Akses tidak sah.');
        }

        return view('auth.reset');
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        $userId = session('verified_user_id');
        if (!$userId) {
            return redirect()->route('password.request')->with('error', 'Sesi expired.');
        }

        $user = User::find($userId);
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Clear session
        session()->forget(['reset_user_id', 'verified_user_id']);

        return redirect()->route('login')->with('success', 'Password berhasil direset. Silakan login.');
    }
}