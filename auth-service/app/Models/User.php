<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // =================================================================
    // PENTING: Baris ini WAJIB ADA karena tabel 'users' Anda
    // tidak memiliki kolom 'updated_at'.
    // =================================================================
    public $timestamps = false;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'google_id',
        'security_question',
        'security_answer',
        'tipe_akun',
        'reset_token',
        'reset_token_expiry',
        // 'fcm_token' juga ada di database Anda, bisa ditambahkan jika perlu
        'fcm_token',
        'is_banned',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'security_answer',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'reset_token_expiry' => 'datetime',
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected $appends = [
        'is_admin',
    ];

    // Convenience helper to check if user is admin
    public function isAdmin()
    {
        return ($this->tipe_akun === 'admin') || (isset($this->attributes['is_admin']) && $this->attributes['is_admin'] == 1);
    }

    // Accessor for dynamic is_admin attribute
    public function getIsAdminAttribute()
    {
        return $this->isAdmin();
    }
}

