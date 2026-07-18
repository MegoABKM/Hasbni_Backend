<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OwnerWithdrawal extends Model {
    use SoftDeletes, \App\Traits\Auditable;
    
    // ملاحظة: قاعدة البيانات مسماها withdrawals في بعض الأماكن، إذا كان الجدول الخاص بك هو withdrawals
    // يرجى إضافة: protected $table = 'withdrawals'; إذا لزم الأمر.
  protected $table = 'owner_withdrawals';
    protected $guarded = [];
    protected $casts = ['withdrawal_date' => 'datetime'];
}