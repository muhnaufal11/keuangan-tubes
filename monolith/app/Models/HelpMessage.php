<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'help_session_id',
        'user_id',
        'message',
    ];

    public function session()
    {
        return $this->belongsTo(HelpSession::class, 'help_session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
