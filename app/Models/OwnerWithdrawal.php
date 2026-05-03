<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class OwnerWithdrawal extends Model {
    use \App\Traits\Auditable;
    
    protected $guarded = [];
    protected $casts = ['withdrawal_date' => 'datetime'];
}