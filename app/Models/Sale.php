<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model {
    use \App\Traits\Auditable;

    // السماح بإدخال جميع الحقول بما فيها invoice_number
    protected $guarded = [];

    // إجبار Laravel على تحويل الأنواع بدقة عند إرسالها للتطبيق (مهم جداً للمزامنة)
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
        'invoice_number' => 'string', // 👈 التأكيد على أنه نص
    ];
    
    public function items() {
        return $this->hasMany(SaleItem::class);
    }

    public function customer() {
        return $this->belongsTo(Customer::class);
    }
}