<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'movement_type',
        'quantity_change',
        'current_balance',
        'cost_price_at_time',
        'reference_id',
        'created_at'
    ];

    public function product() {
        return $this->belongsTo(Product::class);
    }
    public function user() {
        return $this->belongsTo(User::class);
    }
}