<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model {
    protected $guarded = [];
    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];
    
    
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}