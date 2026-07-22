<?php

use App\Providers\AppServiceProvider;
use App\Providers\PaymentConfigServiceProvider;
use App\Saas\Filament\AdminPanelProvider;

return [
    AppServiceProvider::class,
    PaymentConfigServiceProvider::class,
    AdminPanelProvider::class,
];
