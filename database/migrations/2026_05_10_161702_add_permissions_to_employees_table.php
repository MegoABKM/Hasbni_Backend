<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('can_add_expenses')->default(false);
            $table->boolean('can_receive_payments')->default(false);
            $table->boolean('can_make_withdrawals')->default(false);
            $table->boolean('can_pay_suppliers')->default(false);
        });
    }
    public function down(): void {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['can_add_expenses', 'can_receive_payments', 'can_make_withdrawals', 'can_pay_suppliers']);
        });
    }
};