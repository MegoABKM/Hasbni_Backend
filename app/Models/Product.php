<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model {
    use SoftDeletes ,  \App\Traits\Auditable; 
    
   protected $fillable = [
        'name', 'barcode', 'quantity', 'alert_threshold', 'cost_price', 'selling_price', 'last_purchase_price', 'partner_id', 'product_category_id', 'supplier_id', 'user_id'
    ];

    public function movements() { return $this->hasMany(InventoryMovement::class); }
    
    public function category() { return $this->belongsTo(ProductCategory::class, 'product_category_id'); } // 👈 Added
}
