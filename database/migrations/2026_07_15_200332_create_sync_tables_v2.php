<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // إنشاء جدول التوكنات للهواتف
        if (!Schema::hasTable('fcm_tokens')) {
            Schema::create('fcm_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('device_id')->unique(); 
                $table->text('token');
                $table->timestamps();
                
                $table->index('user_id');
            });
        }

        // إنشاء جدول تتبع السجلات المحذوفة للـ Delta Sync
        if (!Schema::hasTable('deleted_records')) {
            Schema::create('deleted_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('table_name');
                $table->unsignedBigInteger('record_id');
                $table->timestamps();
                
                $table->index(['user_id', 'updated_at']);
            });
        }

        // إضافة Soft Deletes للجداول الرئيسية التي تحتاج مزامنة
        $tables = [
            'products', 'sales', 'customers', 'suppliers', 
            'expenses', 'withdrawals', 'product_categories', 'expense_categories'
        ];
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fcm_tokens');
        Schema::dropIfExists('deleted_records');
    }
};