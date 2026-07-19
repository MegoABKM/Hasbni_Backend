<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model {
    use SoftDeletes, \App\Traits\Auditable;
    
    protected $guarded = [];
    protected $casts = ['expense_date' => 'datetime'];
    
    public function category() { return $this->belongsTo(ExpenseCategory::class); }
}