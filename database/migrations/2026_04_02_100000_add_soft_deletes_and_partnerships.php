<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // 1. إضافة الحذف الآمن (Soft Deletes) لتجنب فقدان تاريخ المبيعات
        Schema::table('products', function (Blueprint $table) { $table->softDeletes(); });
        Schema::table('employees', function (Blueprint $table) { $table->softDeletes(); });
        Schema::table('expense_categories', function (Blueprint $table) { $table->softDeletes(); });

        // 2. إنشاء جداول الشراكة المفقودة في السيرفر
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->decimal('profit_share_percentage', 5, 2)->default(50.00);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('partner_goods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->decimal('cost_price', 15, 3);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('partnership_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('record_date');
            $table->timestamps();
        });

        Schema::create('partnership_record_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partnership_record_id')->constrained('partnership_records')->onDelete('cascade');
            $table->foreignId('partner_good_id')->constrained('partner_goods')->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('selling_price', 15, 3);
            $table->decimal('cost_price_at_sale', 15, 3);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('partnership_record_items');
        Schema::dropIfExists('partnership_records');
        Schema::dropIfExists('partner_goods');
        Schema::dropIfExists('partners');
        Schema::table('products', function (Blueprint $table) { $table->dropSoftDeletes(); });
        Schema::table('employees', function (Blueprint $table) { $table->dropSoftDeletes(); });
        Schema::table('expense_categories', function (Blueprint $table) { $table->dropSoftDeletes(); });
    }
};