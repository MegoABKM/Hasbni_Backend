<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Builder;

class CashTransaction extends Model
{
      use \App\Traits\Auditable, Prunable;
    
    // 👈 تم إضافة employee_id هنا
    protected $fillable = [
        'user_id', 
        'employee_id', 
        'transaction_type', 
        'amount', 
        'currency_code', 
        'reference_id', 
        'transaction_date'
    ];



      public function prunable(): Builder {
        // يحذف حركات الدرج الأقدم من 6 أشهر
        return static::where('created_at', '<=', now()->subMonths(6));
    }
}