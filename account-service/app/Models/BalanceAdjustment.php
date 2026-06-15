<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceAdjustment extends Model
{
    protected $table = 'balance_adjustments';
    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'float',
        'saldo_after' => 'float',
    ];
}
