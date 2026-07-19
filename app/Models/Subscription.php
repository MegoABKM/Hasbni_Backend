<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    // السماح بالتعديل على كافة الحقول
    protected $guarded = [];

    // تعريف حقول التواريخ
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    // ربط الاشتراك بخطة
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    // ربط الاشتراك بمستخدم
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}