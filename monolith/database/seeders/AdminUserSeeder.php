<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $email = env('ADMIN_EMAIL', 'admin@example.com');
        $username = env('ADMIN_USERNAME', 'admin');
        $password = env('ADMIN_PASSWORD', 'Admin1234');

        $user = User::where('email', $email)->orWhere('username', $username)->first();
        if (!$user) {
            User::create([
                'username' => $username,
                'email' => $email,
                'password' => Hash::make($password),
                'tipe_akun' => 'admin',
            ]);
        } else {
            $user->update(['tipe_akun' => 'admin']);
        }
    }
}
