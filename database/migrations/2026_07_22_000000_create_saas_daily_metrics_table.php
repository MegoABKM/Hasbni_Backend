<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saas_daily_metrics', function (Blueprint $table): void {
            $table->id();
            $table->date('date');
            $table->string('country', 100);
            $table->decimal('mrr', 14, 2)->default(0);
            $table->unsignedInteger('active_subscriptions')->default(0);
            $table->unsignedInteger('new_signups')->default(0);
            $table->unsignedInteger('churned_subscriptions')->default(0);
            $table->timestamps();

            $table->unique(['date', 'country']);
            $table->index(['country', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_daily_metrics');
    }
};
