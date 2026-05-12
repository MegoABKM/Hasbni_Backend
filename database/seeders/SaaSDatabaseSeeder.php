<?php
namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
use App\Models\AppConfig;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SaaSDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@bhasbni.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
            ]
        );

        // 🚀 إضافة إعدادات التطبيق الافتراضية 🚀
        AppConfig::updateOrCreate(['key' => 'min_version'], ['value' => '1.0.0']);
        AppConfig::updateOrCreate(['key' => 'is_disabled'], ['value' => 'false']);
        AppConfig::updateOrCreate(['key' => 'update_url'], ['value' => 'https://play.google.com/store/apps/details?id=com.yourapp']);

        Plan::updateOrCreate(['name' => 'Free'], [
            'monthly_price' => 0.00, 'yearly_price' => 0.00, 'max_users' => 1, 'max_products' => 5,
            'features' => json_encode(['can_sync' => false, 'reports' => 'basic', 'partnership' => false, 'suppliers' => false]),
            'is_active' => true,
        ]);

        Plan::updateOrCreate(['name' => 'Pro'], [
            'monthly_price' => 9.99, 'yearly_price' => 99.90, 'max_users' => 5, 'max_products' => 5000,
            'features' => json_encode(['can_sync' => true, 'reports' => 'advanced', 'support' => 'priority', 'partnership' => true, 'suppliers' => true]),
            'is_active' => true,
        ]);
        
        Plan::updateOrCreate(['name' => 'Enterprise'], [
            'monthly_price' => 29.99, 'yearly_price' => 299.90, 'max_users' => 999, 'max_products' => 999999,
            'features' => json_encode(['can_sync' => true, 'reports' => 'advanced', 'support' => '24/7', 'partnership' => true, 'suppliers' => true]),
            'is_active' => true,
        ]);
    }
}