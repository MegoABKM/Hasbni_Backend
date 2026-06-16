<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Builder;

class AuditLog extends Model
{
    use Prunable; // 👈 تفعيل الحذف التلقائي الذكي

    protected $guarded = [];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function auditable()
    {
        return $this->morphTo();
    }

    /**
     * تحديد البيانات التي يجب حذفها تلقائياً (التي أقدم من 6 أشهر)
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subMonths(6));
    }
}