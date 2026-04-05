<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model {
    use SoftDeletes;
    
    protected $guarded = [];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function sales() {
        return $this->hasMany(Sale::class);
    }
    // أضف هذه الدالة داخل الكلاس
    public function payments() {
        return $this->hasMany(CustomerPayment::class);
    }
}