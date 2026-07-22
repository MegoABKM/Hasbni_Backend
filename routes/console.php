<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('model:prune')->daily();

Schedule::command('saas:metrics:snapshot')
    ->dailyAt('00:10')
    ->withoutOverlapping();

Schedule::call(function (): void {
    User::query()
        ->whereNull('email_verified_at')
        ->where('created_at', '<', now()->subDay())
        ->delete();
})->daily();
