<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. جدول توكنات الهاتف للإشعارات الصامتة
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

        // 2. إضافة Soft Deletes لتعقب السجلات المحذوفة للمزامنة
        $tables = [
            'products', 'sales', 'customers', 'suppliers', 
            'expenses', 'withdrawals', 'product_categories', 'expense_categories'
        ];
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->softDeletes();
                    $table->index('updated_at');
                    $table->index('deleted_at');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fcm_tokens');

        $tables = [
            'products', 'sales', 'customers', 'suppliers', 
            'expenses', 'withdrawals', 'product_categories', 'expense_categories'
        ];
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                    $table->dropIndex(['updated_at']);
                    $table->dropIndex(['deleted_at']);
                });
            }
        }
    }
};