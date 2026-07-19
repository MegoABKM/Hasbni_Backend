<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
          $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
          
            // 👈 أضف هذا السطر لتفعيل الميدلوير الخاص باللغة
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);
        // 1. تسجيل الأسماء المختصرة للـ Middlewares
        $middleware->alias([
            'manager' => \App\Http\Middleware\EnsureManagerAccess::class,
            'plan' => \App\Http\Middleware\EnforcePlanLimits::class, 
            'check.banned' => \App\Http\Middleware\CheckBannedUser::class, 
        ]);
        
        // 2. تطبيق فحص الحظر على كافة مسارات الـ API المحمية
        $middleware->appendToGroup('api', [
            \App\Http\Middleware\CheckBannedUser::class,
        ]);
         $middleware->api(append: [
    \App\Http\Middleware\SanitizeInput::class,
]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();