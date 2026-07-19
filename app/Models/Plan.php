<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    // السماح بالتعديل على كافة الحقول
    protected $guarded = [];

    // إخبار لارافيل بنوع البيانات لتجنب أخطاء Filament
    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
    ];
}