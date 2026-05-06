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
            // 👈 إضافة العمود الجديد بقيمة افتراضية (false)
            if (!Schema::hasColumn('sales', 'has_returns')) {
                $table->boolean('has_returns')->default(false)->after('payment_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'has_returns')) {
                $table->dropColumn('has_returns');
            }
        });
    }
};