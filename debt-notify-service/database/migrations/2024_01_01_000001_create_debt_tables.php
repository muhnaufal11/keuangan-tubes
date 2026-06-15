<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. UTANG TABLE
        Schema::create('utang', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('jenis', ['utang', 'piutang'])->default('utang');
            $table->string('pemberi')->nullable();
            $table->text('deskripsi');
            $table->decimal('jumlah', 15, 2);
            $table->decimal('sisa_jumlah', 15, 2);
            $table->text('keterangan')->nullable();
            $table->date('tanggal')->nullable();
            $table->string('status')->default('Belum Lunas');
            $table->date('jatuh_tempo')->nullable();
            
            $table->index('user_id');
        });

        // 2. RIWAYAT UTANG TABLE
        Schema::create('riwayat_utang', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('utang_id');
            $table->decimal('jumlah', 15, 2);
            $table->date('tanggal');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->foreign('utang_id')->references('id')->on('utang')->onDelete('cascade');
        });

        // 3. NOTIFICATIONS TABLE
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('info'); // info, warning, success, danger
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('read_at');
        });

        // 4. TIPS TABLE
        Schema::create('tips', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('publish_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tips');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('riwayat_utang');
        Schema::dropIfExists('utang');
    }
};
