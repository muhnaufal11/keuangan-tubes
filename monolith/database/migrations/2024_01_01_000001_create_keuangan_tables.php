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
        // 1. REKENING
        if (!Schema::hasTable('rekening')) {
            Schema::create('rekening', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('nama_rekening');
                $table->string('tipe'); // BANK, E-WALLET, TUNAI
                $table->decimal('saldo', 15, 2);
                $table->decimal('minimum_saldo', 15, 2)->default(0);
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // 2. KATEGORI (Untuk Pemasukan)
        if (!Schema::hasTable('kategori')) {
            Schema::create('kategori', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('nama_kategori');
                // No timestamps based on model
            });
        }

        // 3. KATEGORI PENGELUARAN
        if (!Schema::hasTable('kategori_pengeluaran')) {
            Schema::create('kategori_pengeluaran', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('nama_kategori');
                $table->timestamps(); // Model uses HasFactory, might have timestamps, staying safe.
            });
        }

        // 4. PEMASUKAN
        if (!Schema::hasTable('pemasukan')) {
            Schema::create('pemasukan', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('rekening_id');
                $table->string('kategori'); // Disimpan sebagai string
                $table->text('deskripsi')->nullable();
                $table->decimal('jumlah', 15, 2);
                $table->date('tanggal');
                // No timestamps

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('rekening_id')->references('id')->on('rekening')->onDelete('cascade');
            });
        }

        // 5. PENGELUARAN
        if (!Schema::hasTable('pengeluaran')) {
            Schema::create('pengeluaran', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('rekening_id');
                $table->string('kategori'); // Disimpan sebagai string
                $table->text('deskripsi')->nullable();
                $table->decimal('jumlah', 15, 2);
                $table->date('tanggal');
                // No timestamps

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('rekening_id')->references('id')->on('rekening')->onDelete('cascade');
            });
        }

        // 6. TRANSFER
        if (!Schema::hasTable('transfer')) {
            Schema::create('transfer', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('rekening_sumber_id');
                $table->unsignedBigInteger('rekening_tujuan_id');
                $table->decimal('jumlah', 15, 2);
                $table->text('deskripsi')->nullable();
                $table->date('tanggal');
                // No timestamps

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('rekening_sumber_id')->references('id')->on('rekening')->onDelete('cascade');
                $table->foreign('rekening_tujuan_id')->references('id')->on('rekening')->onDelete('cascade');
            });
        }

        // 7. UTANG (Dan Piutang jika ada, tapi fokus Utang sesuai controller)
        if (!Schema::hasTable('utang')) {
            Schema::create('utang', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->text('deskripsi');
                $table->decimal('jumlah', 15, 2);
                $table->date('jatuh_tempo')->nullable();
                $table->string('status')->default('Belum Lunas');
                // No timestamps

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('utang');
        Schema::dropIfExists('transfer');
        Schema::dropIfExists('pengeluaran');
        Schema::dropIfExists('pemasukan');
        Schema::dropIfExists('kategori_pengeluaran');
        Schema::dropIfExists('kategori');
        Schema::dropIfExists('rekening');
    }
};
