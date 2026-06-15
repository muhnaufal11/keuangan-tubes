<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rekening extends Model
{
    protected $table = 'rekening';
    protected $guarded = ['id'];

    protected $casts = [
        'saldo' => 'float',
        'minimum_saldo' => 'float',
        'user_id' => 'integer',
    ];
}
