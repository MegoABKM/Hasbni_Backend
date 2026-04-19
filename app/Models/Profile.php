<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Profile extends Model {
    protected $guarded = [];
    protected $hidden = ['manager_password'];
    
    // 👈 إضافة هذا السطر لتحويل JSON تلقائياً لمصفوفة
    protected $casts = [
        'taxes' => 'array',
        'discounts' => 'array',
    ];
    
    protected $appends = ['has_manager_password'];
    public function getHasManagerPasswordAttribute() {
        return !empty($this->manager_password);
    }
}