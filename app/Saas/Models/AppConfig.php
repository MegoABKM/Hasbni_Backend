<?php

namespace App\Saas\Models;

use Illuminate\Database\Eloquent\Model;

class AppConfig extends Model
{
    protected $fillable = ['key', 'value'];
}
