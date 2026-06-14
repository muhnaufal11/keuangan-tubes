<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key','value'];

    public static function get($key, $default = null)
    {
        // Guard: if migrations haven't run or table missing, return default to avoid fatal errors
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                return $default;
            }
            $s = self::where('key', $key)->first();
            return $s ? $s->value : $default;
        } catch (\Throwable $e) {
            // In any case of DB errors (e.g., during initial setup), return default
            return $default;
        }
    }

    public static function set($key, $value)
    {
        return self::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
