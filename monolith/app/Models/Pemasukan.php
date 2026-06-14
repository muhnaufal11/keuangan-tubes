<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Pemasukan extends Model
{
    protected $table = 'pemasukan';
    protected $guarded = ['id'];
    public $timestamps = false; // Tabel pemasukan di SQL Anda tidak punya created_at/updated_at otomatis

    public function rekening() { return $this->belongsTo(Rekening::class); }
}
