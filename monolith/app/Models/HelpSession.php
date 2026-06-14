<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(HelpMessage::class)->orderBy('created_at');
    }

    public function addMessage($userId, $message)
    {
        return $this->messages()->create([
            'user_id' => $userId,
            'message' => $message,
        ]);
    }
}
