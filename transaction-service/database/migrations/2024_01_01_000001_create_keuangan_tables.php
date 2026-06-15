<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. PEMASUKAN
        if (!Schema::hasTable('pemasukan')) {
            Schema::create('pemasukan', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('rekening_id')->index();
                $table->string('kategori'); // Disimpan sebagai string
                $table->text('deskripsi')->nullable();
                $table->decimal('jumlah', 15, 2);
                $table->date('tanggal');
            });
        }

        // 2. PENGELUARAN
        if (!Schema::hasTable('pengeluaran')) {
            Schema::create('pengeluaran', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('rekening_id')->index();
                $table->string('kategori'); // Disimpan sebagai string
                $table->text('deskripsi')->nullable();
                $table->decimal('jumlah', 15, 2);
                $table->date('tanggal');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pengeluaran');
        Schema::dropIfExists('pemasukan');
    }
};
