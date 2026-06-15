<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefaultCategory extends Model
{
    protected $table = 'default_categories';
    protected $fillable = ['name', 'type'];
}
