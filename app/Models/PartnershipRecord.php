<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnershipRecord extends Model
{
    protected $fillable = ['user_id', 'record_date'];

    public function items()
    {
        // 🚨 إضافة المفتاح الأجنبي الصحيح
        return $this->hasMany(PartnershipRecordItem::class, 'partnership_record_id');
    }
}