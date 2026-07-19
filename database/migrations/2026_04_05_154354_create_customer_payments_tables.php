<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 3);
            $table->timestamp('payment_date');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('customer_payments');
    }
};