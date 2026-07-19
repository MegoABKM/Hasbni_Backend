<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model {
    protected $fillable = [
        'full_name', 'pin_code', 'user_id', 
        'can_add_expenses', 'can_receive_payments', 
        'can_make_withdrawals', 'can_pay_suppliers'
    ];
    
    protected $casts = [
        'can_add_expenses' => 'boolean',
        'can_receive_payments' => 'boolean',
        'can_make_withdrawals' => 'boolean',
        'can_pay_suppliers' => 'boolean',
    ];
}