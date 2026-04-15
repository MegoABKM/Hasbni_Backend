<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model {
    use SoftDeletes; 
    
    // 👈 تم التعديل: إضافة جميع الحقول المسموح تعديلها (Mass Assignment)
    protected $fillable = [
        'name',
        'barcode',
        'quantity',
        'alert_threshold', // 👈 العمود المفقود الذي كان يسبب الخطأ
        'cost_price',
        'selling_price',
        'user_id' , 
          'partner_id'
    ];
}