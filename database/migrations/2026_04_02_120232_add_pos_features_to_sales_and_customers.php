<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // 1. جدول العملاء (الديون)
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->decimal('balance', 15, 3)->default(0); // الديون: الموجب يعني العميل مدين لنا
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. تحديث جدول المبيعات
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->decimal('discount_amount', 15, 3)->default(0); // قيمة الخصم
            $table->decimal('tax_amount', 15, 3)->default(0); // قيمة الضريبة
            $table->decimal('paid_amount', 15, 3)->default(0); // المبلغ المدفوع
            $table->string('payment_status')->default('paid'); // paid, partial, unpaid
        });
    }

    public function down(): void {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn(['customer_id', 'discount_amount', 'tax_amount', 'paid_amount', 'payment_status']);
        });
        Schema::dropIfExists('customers');
    }
};