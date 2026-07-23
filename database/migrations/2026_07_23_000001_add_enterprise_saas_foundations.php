<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->timestamp('grace_period_ends_at')->nullable()->after('ends_at')->index();
            $table->timestamp('payment_failed_at')->nullable()->after('grace_period_ends_at');
            $table->unsignedTinyInteger('dunning_last_notified_day')->nullable()->after('payment_failed_at');
            $table->boolean('is_locked')->default(false)->after('dunning_last_notified_day')->index();
            $table->string('provider', 32)->nullable()->after('stripe_customer_id')->index();
            $table->string('provider_purchase_token', 1024)->nullable()->after('provider');
            $table->string('provider_original_transaction_id')->nullable()->after('provider_purchase_token')->index();
            $table->boolean('auto_renews')->nullable()->after('provider_original_transaction_id');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->string('refund_id')->nullable()->after('transaction_id')->index();
            $table->timestamp('refunded_at')->nullable()->after('paid_at');
        });

        Schema::create('webhook_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 32)->index();
            $table->string('event_type')->nullable()->index();
            $table->json('payload');
            $table->string('status', 16)->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tenant_feature_flags', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('feature_key');
            $table->boolean('is_enabled');
            $table->timestamps();
            $table->unique(['user_id', 'feature_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_feature_flags');
        Schema::dropIfExists('webhook_logs');

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn(['refund_id', 'refunded_at']);
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropColumn([
                'grace_period_ends_at',
                'payment_failed_at',
                'dunning_last_notified_day',
                'is_locked',
                'provider',
                'provider_purchase_token',
                'provider_original_transaction_id',
                'auto_renews',
            ]);
        });
    }
};
