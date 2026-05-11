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
            // إضافة رقم الفاتورة المميز
            $table->string('invoice_number')->nullable()->after('id');
            
            // إضافة حالة المرتجع (الافتراضي: 0 أي لا يوجد مرتجع)
            $table->boolean('has_returns')->default(false)->after('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // مسح الأعمدة في حال عمل التراجع (Rollback)
            $table->dropColumn(['invoice_number', 'has_returns']);
        });
    }
};