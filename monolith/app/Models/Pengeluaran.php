<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengeluaran extends Model
{
    protected $table = 'pengeluaran';
    
    // --- MATIKAN TIMESTAMP AGAR TIDAK ERROR ---
    public $timestamps = false; 
    // ------------------------------------------

    protected $guarded = ['id'];

    public function rekening()
    {
        return $this->belongsTo(Rekening::class);
    }
}