<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    protected $table = 'transfer';
    protected $guarded = ['id'];
    public $timestamps = false;

    protected $casts = [
        'jumlah' => 'float',
        'user_id' => 'integer',
        'rekening_sumber_id' => 'integer',
        'rekening_tujuan_id' => 'integer',
    ];

    public function rekeningSumber()
    {
        return $this->belongsTo(Rekening::class, 'rekening_sumber_id');
    }

    public function rekeningTujuan()
    {
        return $this->belongsTo(Rekening::class, 'rekening_tujuan_id');
    }
}
