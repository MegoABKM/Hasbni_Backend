<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::create('cash_transactions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('transaction_type'); // (sale_in, expense_out, cash_injection...)
        $table->decimal('amount', 15, 2);
        $table->string('currency_code', 10);
        $table->unsignedBigInteger('reference_id')->nullable(); // رقم الفاتورة أو المصروف
        $table->timestamp('transaction_date');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};
