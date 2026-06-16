<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnershipRecordItem extends Model
{
    // 🚨 تعديل أسماء الأعمدة لتتطابق مع قاعدة البيانات
    protected $fillable = [
        'partnership_record_id',
        'partner_good_id',
        'quantity',
        'selling_price',
        'cost_price_at_sale'
    ];

    public function record()
    {
        // 🚨 إضافة المفتاح الأجنبي الصحيح
        return $this->belongsTo(PartnershipRecord::class, 'partnership_record_id');
    }

    public function good()
    {
        // 🚨 إضافة المفتاح الأجنبي الصحيح
        return $this->belongsTo(PartnerGood::class, 'partner_good_id');
    }
}