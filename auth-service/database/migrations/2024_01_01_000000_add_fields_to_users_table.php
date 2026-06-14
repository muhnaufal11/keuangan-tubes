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
        Schema::table('users', function (Blueprint $table) {
            // Adding columns if they don't exist (using logic to avoid errors if partially exist)
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->unique()->after('id');
            }
            if (!Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id')->nullable()->after('password');
            }
            if (!Schema::hasColumn('users', 'security_question')) {
                $table->string('security_question')->nullable();
            }
            if (!Schema::hasColumn('users', 'security_answer')) {
                $table->string('security_answer')->nullable();
            }
            if (!Schema::hasColumn('users', 'tipe_akun')) {
                $table->string('tipe_akun')->default('gratis');
            }
            if (!Schema::hasColumn('users', 'fcm_token')) {
                $table->string('fcm_token')->nullable();
            }
            if (!Schema::hasColumn('users', 'reset_token')) {
                $table->string('reset_token')->nullable();
            }
            if (!Schema::hasColumn('users', 'reset_token_expiry')) {
                $table->dateTime('reset_token_expiry')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username',
                'google_id', 
                'security_question', 
                'security_answer', 
                'tipe_akun', 
                'fcm_token', 
                'reset_token', 
                'reset_token_expiry'
            ]);
        });
    }
};
