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
        Schema::table('employees', function (Blueprint $table) {
            // إضافة عمود الرمز السري بعد عمود الاسم
            // وضعنا قيمة افتراضية '0000' حتى لا تحدث مشكلة مع الموظفين القدامى (إن وُجدوا)
            $table->string('pin_code')->default('0000')->after('full_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // حذف العمود في حال التراجع عن الـ Migration
            $table->dropColumn('pin_code');
        });
    }
};