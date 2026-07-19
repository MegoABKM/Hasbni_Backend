<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model {
    protected $guarded = [];

    // 👈 تم تغيير profile_id إلى user_id
    protected $fillable = ['user_id', 'currency_code', 'rate_to_usd'];

    // 👈 تغيير العلاقة لتكون مع User بدلاً من Profile
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}