<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PartnerGood extends Model {
    protected $guarded = [];

    public function partner() {
        return $this->belongsTo(Partner::class);
    }
}
