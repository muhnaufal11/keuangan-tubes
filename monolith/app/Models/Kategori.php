<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    // Sesuaikan dengan nama tabel di 'keuangan.sql' Anda
    protected $table = 'kategori'; 
    protected $guarded = ['id'];
    public $timestamps = false; // Karena di SQL lama biasanya tidak ada created_at otomatis di tabel kategori
}