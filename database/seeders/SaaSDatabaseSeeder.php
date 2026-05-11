<?php
namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
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

       Plan::updateOrCreate(['name' => 'Free'], [
            'monthly_price' => 0.00,
            'yearly_price' => 0.00,
            'max_users' => 1,
            'max_products' => 5,
            // 👈 إضافة الشراكة والموردين في الخطة المجانية (معطلة)
            'features' => json_encode(['can_sync' => false, 'reports' => 'basic', 'partnership' => false, 'suppliers' => false]),
            'is_active' => true,
        ]);

        Plan::updateOrCreate(['name' => 'Pro'], [
            'monthly_price' => 9.99,
            'yearly_price' => 99.90,
            'max_users' => 5,
            'max_products' => 5000,
            // 👈 تفعيل الميزات في الخطة المدفوعة
            'features' => json_encode(['can_sync' => true, 'reports' => 'advanced', 'support' => 'priority', 'partnership' => true, 'suppliers' => true]),
            'is_active' => true,
        ]);
        
        Plan::updateOrCreate(['name' => 'Enterprise'], [
            'monthly_price' => 29.99,
            'yearly_price' => 299.90,
            'max_users' => 999,
            'max_products' => 999999,
            // 👈 تفعيل الميزات للشركات
            'features' => json_encode(['can_sync' => true, 'reports' => 'advanced', 'support' => '24/7', 'partnership' => true, 'suppliers' => true]),
            'is_active' => true,
        ]);
    }
}