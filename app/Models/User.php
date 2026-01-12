<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // <--- IMPORT THIS

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable; // <--- ADD THIS TRAIT

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relationships
    public function profile() { return $this->hasOne(Profile::class); }
    public function products() { return $this->hasMany(Product::class); }
    public function employees() { return $this->hasMany(Employee::class); }
    public function expenses() { return $this->hasMany(Expense::class); }
    public function expenseCategories() { return $this->hasMany(ExpenseCategory::class); }
    public function withdrawals() { return $this->hasMany(OwnerWithdrawal::class); }
    public function exchangeRates() { return $this->hasMany(ExchangeRate::class); }
    public function sales() { return $this->hasMany(Sale::class); }
}