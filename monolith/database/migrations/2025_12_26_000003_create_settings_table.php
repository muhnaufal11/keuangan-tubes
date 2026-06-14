<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // seed default settings
        DB::table('settings')->insert([
            ['key' => 'maintenance_mode', 'value' => '0', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'maintenance_message', 'value' => 'Sistem sedang dalam perbaikan. Silakan cek kembali nanti.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'backup_recipients', 'value' => env('ADMIN_BACKUP_EMAILS', ''), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'backup_retention', 'value' => env('BACKUP_RETENTION', '8'), 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('settings');
    }
};
