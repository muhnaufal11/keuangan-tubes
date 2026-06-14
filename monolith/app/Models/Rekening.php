<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rekening extends Model
{
    protected $table = 'rekening';
    
    // --- TAMBAHKAN INI AGAR TIDAK ERROR 'updated_at' ---
    public $timestamps = false;
    // ---------------------------------------------------

    protected $guarded = ['id']; 

    public function user() 
    { 
        return $this->belongsTo(User::class); 
    }
}