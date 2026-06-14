<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('default_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['pemasukan','pengeluaran'])->default('pengeluaran');
            $table->timestamps();
        });

        // optional: seed some sensible defaults
        if (Schema::hasTable('default_categories')) {
            \Illuminate\Support\Facades\DB::table('default_categories')->insert([
                ['name' => 'Makan', 'type' => 'pengeluaran', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Transport', 'type' => 'pengeluaran', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Gaji', 'type' => 'pemasukan', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Hadiah', 'type' => 'pemasukan', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('default_categories');
    }
};