<?php

namespace Database\Seeders;

use App\Models\User;
use App\Saas\Models\AppConfig;
use App\Saas\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SaaSDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminPassword = env('INITIAL_ADMIN_PASSWORD');

        if (! $adminPassword && app()->isLocal()) {
            $adminPassword = 'password';
        }

        if (! $adminPassword) {
            throw new \RuntimeException('INITIAL_ADMIN_PASSWORD must be set before seeding the super admin account.');
        }
        // 1. إنشاء حساب المدير العام
        User::updateOrCreate(
            ['email' => 'admin@bhasbni.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make($adminPassword),
                'role' => 'super_admin',
                'account_type' => User::ACCOUNT_TYPE_STAFF,
            ]
        );

        // 2. 🚀 إعدادات التطبيق الافتراضية (هنا أضفنا رقم الواتساب) 🚀
        AppConfig::updateOrCreate(['key' => 'min_version'], ['value' => '1.0.0']);
        AppConfig::updateOrCreate(['key' => 'is_disabled'], ['value' => 'false']);
        AppConfig::updateOrCreate(['key' => 'update_url'], ['value' => 'https://play.google.com/store/apps/details?id=com.yourapp']);

        // 👇 السطر الجديد: ضع رقم صديقك في ألمانيا أو رقمك في لبنان كقيمة افتراضية
        AppConfig::updateOrCreate(['key' => 'whatsapp_number'], ['value' => '96170123456']);

        // 3. خطط الاشتراك
        Plan::updateOrCreate(['name' => 'Free'], [
            'monthly_price' => 0.00, 'yearly_price' => 0.00, 'max_users' => 1, 'max_products' => 50,
            // 🚨 أضفنا support كـ false لكي تظهر مقفلة في التطبيق
            'features' => json_encode(['can_sync' => false, 'reports' => 'basic', 'partnership' => false, 'suppliers' => false, 'support' => false]),
            'is_active' => true,
        ]);

        Plan::updateOrCreate(['name' => 'Pro'], [
            'monthly_price' => 9.99,
            'yearly_price' => 99.90,
            'max_users' => 2, // 👈 1 Manager + 1 Cashier = 2 Users
            'max_products' => 5000,
            'features' => json_encode([
                'can_sync' => true,
                'reports' => 'advanced',
                'support' => 'priority',
                'partnership' => true,
                'suppliers' => true,
                'full_inventory_sync' => false, // 👈 غير متاحة في البرو
            ]),
            'is_active' => true,
        ]);

        Plan::updateOrCreate(['name' => 'Enterprise'], [
            'monthly_price' => 29.99,
            'yearly_price' => 299.90,
            'max_users' => 999, // 👈 كاشير لا محدود
            'max_products' => 999999,
            'features' => json_encode([
                'can_sync' => true,
                'reports' => 'advanced',
                'support' => '24/7',
                'partnership' => true,
                'suppliers' => true,
                'full_inventory_sync' => true, // 👈 مزامنة مخزون فورية وتتبع النواقص
            ]),
            'is_active' => true,
        ]);
    }
}
