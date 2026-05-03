<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Builder;

class PartnershipRecord extends Model
{
    use Prunable;
    protected $fillable = ['user_id', 'record_date'];

    public function items()
    {
        return $this->hasMany(PartnershipRecordItem::class, 'partnership_record_id');
    }

    public function prunable(): Builder {
        return static::where('record_date', '<=', now()->subMonths(6));
    }

    // 👈 السطر المضاف ليقوم بعمل Cascade Deletion للتفاصيل
    protected function pruning()
    {
        $this->items()->delete();
    }
}