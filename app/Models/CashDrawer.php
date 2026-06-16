<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashDrawer extends Model
{
      protected $fillable = ['user_id', 'currency_code', 'balance'];

}
