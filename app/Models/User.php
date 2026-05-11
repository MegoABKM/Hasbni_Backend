<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // صلاحية دخول لوحة التحكم
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === 'super_admin';
    }

    // العلاقات (SaaS)
    public function subscription() { return $this->hasOne(Subscription::class); }

    // علاقات المتجر الخاصة بك
    public function profile() { return $this->hasOne(Profile::class); }
    public function products() { return $this->hasMany(Product::class); }
    public function employees() { return $this->hasMany(Employee::class); }
    public function expenses() { return $this->hasMany(Expense::class); }
    public function expenseCategories() { return $this->hasMany(ExpenseCategory::class); }
    public function withdrawals() { return $this->hasMany(OwnerWithdrawal::class); }
    public function exchangeRates() { return $this->hasMany(ExchangeRate::class); }
    public function sales() { return $this->hasMany(Sale::class); }
    public function customers() { return $this->hasMany(Customer::class); }
    public function cashDrawers() { return $this->hasMany(CashDrawer::class); }
    public function cashTransactions() { return $this->hasMany(CashTransaction::class); }
    public function inventoryMovements() { return $this->hasMany(InventoryMovement::class); }
    public function partners() { return $this->hasMany(Partner::class); }
    public function partnershipRecords() { return $this->hasMany(PartnershipRecord::class); }
    public function suppliers() { return $this->hasMany(Supplier::class); }
    public function supplierPayments() { return $this->hasMany(SupplierPayment::class); }
}