<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model {
    protected $guarded = [];
    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}