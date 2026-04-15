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
    Schema::create('cash_drawers', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('currency_code', 10);
        $table->decimal('balance', 15, 2)->default(0);
        $table->timestamps();

        // لا يمكن للمستخدم أن يمتلك أكثر من درج لنفس العملة
        $table->unique(['user_id', 'currency_code']); 
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_drawers');
    }
};
