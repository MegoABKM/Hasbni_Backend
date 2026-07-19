<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model {
    use SoftDeletes, \App\Traits\Auditable;

    protected $guarded = [];

    protected $casts = [
        'has_returns' => 'boolean',
        'total_price' => 'float',
        'total_profit' => 'float',
        'discount_amount' => 'float',
        'tax_amount' => 'float',
        'paid_amount' => 'float',
        'tendered_amount' => 'float',
        'change_amount' => 'float',
        'rate_to_usd_at_sale' => 'float',
        'invoice_number' => 'string', 
    ];
    
    public function items() { return $this->hasMany(SaleItem::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
}