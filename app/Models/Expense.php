<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model {
    protected $guarded = [];
    protected $casts = ['expense_date' => 'datetime'];
    use \App\Traits\Auditable;
    
    public function category() {
        return $this->belongsTo(ExpenseCategory::class);
    }
}