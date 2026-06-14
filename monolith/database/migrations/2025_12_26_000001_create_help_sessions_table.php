<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        // ensure any partial table is removed (fix for interrupted migration runs)
        Schema::dropIfExists('help_sessions');

        Schema::create('help_sessions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            // use unsignedBigInteger to match users.id; avoid creating FK constraint to prevent errno 150
            $table->unsignedBigInteger('user_id')->index();
            $table->string('status')->default('open');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('help_sessions');
    }
};
