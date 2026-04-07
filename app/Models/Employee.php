<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model {
    protected $guarded = [];
    // لا تقم بإرجاع كلمة المرور في الـ API الافتراضي للأمان، ولكننا نحتاجها للتطبيق
    // لذا سنتركها بدون hidden حالياً لأن التطبيق يحتاجها للتحقق أوفلاين
}