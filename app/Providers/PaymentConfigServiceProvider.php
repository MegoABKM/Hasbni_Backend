<?php

declare(strict_types=1);

namespace App\Providers;

use App\Saas\Models\AppConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

final class PaymentConfigServiceProvider extends ServiceProvider
{
    public const CACHE_KEY = 'saas:payment-config:v1';

    public function boot(): void
    {
        if (! Schema::hasTable('app_configs')) {
            return;
        }

        $settings = Cache::rememberForever(
            self::CACHE_KEY,
            static fn (): array => AppConfig::query()
                ->whereIn('key', [
                    'stripe_secret_key',
                    'stripe_webhook_secret',
                    'myfatoorah_token',
                ])
                ->pluck('value', 'key')
                ->all(),
        );

        config([
            'services.stripe.secret' => $settings['stripe_secret_key'] ?? config('services.stripe.secret'),
            'services.stripe.webhook_secret' => $settings['stripe_webhook_secret'] ?? config('services.stripe.webhook_secret'),
            'services.myfatoorah.token' => $settings['myfatoorah_token'] ?? config('services.myfatoorah.token'),
        ]);
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
