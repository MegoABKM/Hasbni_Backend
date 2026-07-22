<?php

namespace App\Saas\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
