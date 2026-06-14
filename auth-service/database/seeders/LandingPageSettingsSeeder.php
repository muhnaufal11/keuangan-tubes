<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LandingPageSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $settings = [
            // Hero Section
            ['key' => 'landing_hero_title', 'value' => 'Kelola Keuangan Anda dengan Lebih Cerdas'],
            ['key' => 'landing_hero_subtitle', 'value' => 'Catat pemasukan, atur pengeluaran, dan pantau arus kas Anda dalam satu aplikasi yang mudah digunakan.'],
            ['key' => 'landing_hero_cta_text', 'value' => 'Mulai Sekarang Gratis'],
            ['key' => 'landing_hero_image', 'value' => 'https://placehold.co/800x400/2962ff/ffffff?text=Dashboard+Preview'],

            // Features Section
            ['key' => 'landing_features_title', 'value' => 'Fitur Unggulan'],
            ['key' => 'landing_features_subtitle', 'value' => 'Semua yang Anda butuhkan untuk mengatur dompet Anda.'],

            // Feature 1
            ['key' => 'landing_feature_1_icon', 'value' => '💸'],
            ['key' => 'landing_feature_1_title', 'value' => 'Pencatatan Mudah'],
            ['key' => 'landing_feature_1_description', 'value' => 'Catat pemasukan dan pengeluaran harian Anda hanya dalam beberapa klik. Simpel dan cepat.'],

            // Feature 2
            ['key' => 'landing_feature_2_icon', 'value' => '🏦'],
            ['key' => 'landing_feature_2_title', 'value' => 'Multi Rekening'],
            ['key' => 'landing_feature_2_description', 'value' => 'Kelola saldo dari berbagai sumber: Tunai, Bank, atau E-Wallet dalam satu tempat.'],

            // Feature 3
            ['key' => 'landing_feature_3_icon', 'value' => '📊'],
            ['key' => 'landing_feature_3_title', 'value' => 'Laporan Ringkas'],
            ['key' => 'landing_feature_3_description', 'value' => 'Pantau kesehatan finansial Anda melalui dashboard yang informatif dan mudah dipahami.'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
