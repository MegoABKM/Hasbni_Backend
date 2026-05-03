<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model {
    protected $guarded = [];
    use \App\Traits\Auditable;
    
    public function items() {
        return $this->hasMany(SaleItem::class);
    }

    // 👈 أضف هذه العلاقة
    public function customer() {
        return $this->belongsTo(Customer::class);
    }
}