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
        if (!Schema::hasTable('riwayat_utang')) {
            Schema::create('riwayat_utang', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('utang_id');
                $table->decimal('jumlah', 15, 2);
                $table->date('tanggal');
                $table->text('keterangan')->nullable();
                $table->timestamps();

                $table->foreign('utang_id')->references('id')->on('utang')->onDelete('cascade');
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
        Schema::dropIfExists('riwayat_utang');
    }
};
