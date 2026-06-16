<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable; // 👈
use Illuminate\Database\Eloquent\Builder; // 👈

class SaleItem extends Model {
    use Prunable; // 👈

    protected $guarded = [];

    public function sale() {
        return $this->belongsTo(Sale::class);
    }

    public function prunable(): Builder {
        // يحذف تفاصيل الفاتورة إذا كان تاريخ إنشاء الفاتورة الأساسية أقدم من 6 أشهر
        return static::whereHas('sale', function ($query) {
            $query->where('created_at', '<=', now()->subMonths(6));
        });
    }
}