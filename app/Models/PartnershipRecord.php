<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PartnershipRecord extends Model {
    protected $guarded = [];
    public function items() { return $this->hasMany(PartnershipRecordItem::class, 'record_id'); }
}