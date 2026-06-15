<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * account_db. Catatan: TIDAK ada foreign key lintas-DB.
 * user_id & rekening_id disimpan sebagai integer ber-index; integritas dijaga via validasi REST.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('rekening', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('nama_rekening');
            $table->string('no_rekening')->nullable();
            $table->string('tipe'); // BANK, E-WALLET, TUNAI
            $table->decimal('saldo', 15, 2)->default(0);
            $table->decimal('minimum_saldo', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('kategori', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('nama_kategori');
        });

        Schema::create('kategori_pengeluaran', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('nama_kategori');
            $table->timestamps();
        });

        Schema::create('default_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['pemasukan', 'pengeluaran'])->default('pengeluaran');
            $table->timestamps();
        });

        Schema::create('transfer', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('rekening_sumber_id')->index();
            $table->unsignedBigInteger('rekening_tujuan_id')->index();
            $table->decimal('jumlah', 15, 2);
            $table->text('deskripsi')->nullable();
            $table->date('tanggal');
        });

        // Tabel idempotensi untuk endpoint internal adjust-balance.
        Schema::create('balance_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique();
            $table->unsignedBigInteger('rekening_id')->index();
            $table->decimal('amount', 15, 2);
            $table->string('direction'); // credit | debit
            $table->decimal('saldo_after', 15, 2);
            $table->timestamps();
        });

        DB::table('default_categories')->insert([
            ['name' => 'Makan', 'type' => 'pengeluaran', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Transport', 'type' => 'pengeluaran', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Gaji', 'type' => 'pemasukan', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Hadiah', 'type' => 'pemasukan', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('balance_adjustments');
        Schema::dropIfExists('transfer');
        Schema::dropIfExists('default_categories');
        Schema::dropIfExists('kategori_pengeluaran');
        Schema::dropIfExists('kategori');
        Schema::dropIfExists('rekening');
    }
};
