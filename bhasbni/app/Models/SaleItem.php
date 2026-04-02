<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model {
    protected $guarded = [];

    // --- FIX: Added Relationship ---
    public function sale() {
        return $this->belongsTo(Sale::class);
    }
}