<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date')->index();
            $table->string('country', 10)->nullable()->index();
            $table->decimal('platform_revenue', 12, 2)->default(0.00); // SaaS Subscription Payments Collected
            $table->decimal('mrr', 12, 2)->default(0.00);             // Monthly Recurring Revenue
            $table->decimal('arr', 12, 2)->default(0.00);             // Annual Recurring Revenue
            $table->integer('active_subscriptions')->default(0);      // Active Paid Accounts
            $table->integer('new_subscriptions')->default(0);         // New SaaS Subscribers
            $table->integer('churned_subscriptions')->default(0);     // Cancelled/Expired Accounts
            $table->timestamps();

            $table->unique(['snapshot_date', 'country']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_snapshots');
    }
};