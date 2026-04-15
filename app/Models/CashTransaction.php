<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashTransaction extends Model
{
        protected $fillable = ['user_id', 'transaction_type', 'amount', 'currency_code', 'reference_id', 'transaction_date'];
}
