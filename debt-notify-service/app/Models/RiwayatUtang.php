<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiwayatUtang extends Model
{
    use HasFactory;

    protected $table = 'riwayat_utang';
    protected $guarded = ['id'];
    protected $fillable = [
        'utang_id',
        'jumlah',
        'tanggal',
        'keterangan'
    ];
    
    // Relasi ke Utang
    public function utang()
    {
        return $this->belongsTo(Utang::class, 'utang_id');
    }
}
