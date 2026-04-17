<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashTransaction extends Model
{
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
}