<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // 1. جدول الكوبونات
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // الكود مثل RAMADAN50
            $table->decimal('discount_percentage', 5, 2); // 50.00
            $table->integer('max_uses')->nullable(); // عدد مرات الاستخدام المسموحة
            $table->integer('current_uses')->default(0); // عدد مرات الاستخدام الحالية
            $table->timestamp('expires_at')->nullable(); // تاريخ الانتهاء
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. جدول الإعلانات
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('info'); // info, warning, danger
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('promo_codes');
    }
};