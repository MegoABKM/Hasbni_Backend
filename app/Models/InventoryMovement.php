<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Builder;
class InventoryMovement extends Model
{
    use Prunable; // 👈 تفعيل الحذف التلقائي الذكي

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

        public function prunable(): Builder {
        // يحذف حركات المخزون الأقدم من 6 أشهر
        return static::where('created_at', '<=', now()->subMonths(6));
    }
}