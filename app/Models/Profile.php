<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model {
    protected $guarded = [];
    protected $hidden = ['manager_password'];
    
    protected $appends = ['has_manager_password'];
    public function getHasManagerPasswordAttribute() {
        return !empty($this->manager_password);
    }

    // 👈 تم حذف دالة exchangeRates() من هنا نهائياً لمنع الخطأ
}