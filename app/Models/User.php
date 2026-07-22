<?php

declare(strict_types=1);

namespace App\Models;

use App\Saas\Models\Payment;
use App\Saas\Models\Subscription;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Roles allowed to authenticate into the Filament administration panel.
     * Resource-level authorization remains more restrictive where required.
     *
     * @var array<int, string>
     */
    public const FILAMENT_ADMIN_ROLES = [
        'super_admin',
        'support_admin',
        'finance_admin',
    ];

    protected $fillable = [
        'name', 'email', 'password', 'role', 'is_banned',
        'phone', 'country', 'business_type', 'fcm_token',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'is_banned' => 'boolean'];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array((string) $this->role, self::FILAMENT_ADMIN_ROLES, true);
    }

    /**
     * @param  array<int, string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array((string) $this->role, $roles, true);
    }

    public function scopeTenants(Builder $query): Builder
    {
        return $query->where('role', 'tenant');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class)->latest();
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function productCategories()
    {
        return $this->hasMany(ProductCategory::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function expenseCategories()
    {
        return $this->hasMany(ExpenseCategory::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(OwnerWithdrawal::class);
    }

    public function exchangeRates()
    {
        return $this->hasMany(ExchangeRate::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function cashDrawers()
    {
        return $this->hasMany(CashDrawer::class);
    }

    public function cashTransactions()
    {
        return $this->hasMany(CashTransaction::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function partners()
    {
        return $this->hasMany(Partner::class);
    }

    public function partnershipRecords()
    {
        return $this->hasMany(PartnershipRecord::class);
    }

    public function suppliers()
    {
        return $this->hasMany(Supplier::class);
    }

    public function supplierPayments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class)->latest();
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class)->latest();
    }

    // 🚀 الدالة الجديدة لفرز أصحاب باقة الإنتربرايز 🚀
    public function hasRealtimeSyncFeature(): bool
    {
        if (! $this->subscription || $this->subscription->status !== 'active') {
            return false;
        }

        $plan = $this->subscription->plan;
        if (! $plan) {
            return false;
        }

        $features = is_string($plan->features) ? json_decode($plan->features, true) : $plan->features;

        return isset($features['full_inventory_sync']) && ($features['full_inventory_sync'] === true || $features['full_inventory_sync'] === 'true');
    }
}
