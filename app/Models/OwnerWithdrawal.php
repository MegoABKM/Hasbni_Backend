<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class OwnerWithdrawal extends Model {
    protected $guarded = [];
    protected $casts = ['withdrawal_date' => 'datetime'];
}