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
        'alert_threshold', 
        'cost_price',
        'selling_price',
        'last_purchase_price', // 👈 إضافة هنا
        'user_id' , 
        'partner_id'
    ];

    public function movements() { return $this->hasMany(InventoryMovement::class); }
    
}