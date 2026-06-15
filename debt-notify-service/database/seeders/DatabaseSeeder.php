<?php

namespace Database\Seeders;

use App\Models\Tip;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        Tip::create([
            'title' => 'Cara Hemat Belanja Bulanan',
            'body' => 'Buat daftar belanja sebelum pergi ke supermarket dan hindari belanja saat lapar agar tidak lapar mata.',
            'is_active' => true,
            'publish_at' => now(),
        ]);

        Tip::create([
            'title' => 'Pentingnya Dana Darurat',
            'body' => 'Sisihkan minimal 3-6 kali pengeluaran bulanan Anda di rekening terpisah untuk keperluan darurat.',
            'is_active' => true,
            'publish_at' => now(),
        ]);
    }
}
