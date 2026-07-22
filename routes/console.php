<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 👈 إضافة أمر التنظيف ليتم تشغيله يومياً
Schedule::command('model:prune')->daily();

Schedule::call(function () {
    \App\Models\User::whereNull('email_verified_at')->where('created_at', '<', now()->subDay())->delete();
})->daily();
