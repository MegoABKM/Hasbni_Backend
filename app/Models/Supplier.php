<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\SupplierPayment; // 👈 تمت إضافة هذا السطر لحل مشكلة Intelephense

class Supplier extends Model {
    use SoftDeletes, \App\Traits\Auditable;
    
    protected $guarded = [];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function payments() {
        return $this->hasMany(SupplierPayment::class);
    }
}