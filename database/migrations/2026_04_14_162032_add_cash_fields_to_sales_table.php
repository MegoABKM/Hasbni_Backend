<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // نستخدم decimal للتعامل مع المبالغ المالية بدقة (15 رقم صحيح ورقمين عشريين)
            $table->decimal('tendered_amount', 15, 2)->default(0)->after('tax_amount');
            $table->string('tendered_currency', 3)->nullable()->after('tendered_amount'); // مثل USD, LYD
            $table->decimal('change_amount', 15, 2)->default(0)->after('tendered_currency');
            $table->string('change_currency', 3)->nullable()->after('change_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // حذف الأعمدة في حال التراجع عن عملية الـ Migration
            $table->dropColumn([
                'tendered_amount',
                'tendered_currency',
                'change_amount',
                'change_currency',
            ]);
        });
    }
};