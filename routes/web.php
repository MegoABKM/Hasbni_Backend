<?php
// c:\Users\LEGION\bhasbni\routes\web.php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;

// 1. مسار تحميل الشهادة
Route::get('/get-cert', function () {
    $file = public_path('download/hasbni.pfx');
    
    if (!file_exists($file)) {
        abort(404, 'عذراً، ملف الشهادة غير موجود في السيرفر.');
    }

    return Response::download($file, 'hasbni.pfx', [
        'Content-Type' => 'application/x-pkcs12'
    ]);
});

// 2. مسار تحميل التطبيق
Route::get('/get-app', function () {
    $file = public_path('download/hasbni.msix');
    
    if (!file_exists($file)) {
        abort(404, 'عذراً، ملف التطبيق غير موجود في السيرفر.');
    }

    return Response::download($file, 'hasbni.msix', [
        'Content-Type' => 'application/vnc.ms-appx'
    ]);
});

// 3. مسار صفحة التحميل (الواجهة)
Route::get('/hasbni-setup', function () {
    return view('download');
});

Route::get('/clear-cache', function() {
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
    return '✅ تم مسح الكاش بنجاح! السيرفر الآن سيقرأ الأكواد الجديدة.';
});

// 🚀 4. المسار المسؤول عن تبديل اللغة (هذا الذي كان مفقوداً) 🚀
Route::get('switch-language/{locale}', function ($locale) {
    if (in_array($locale, ['ar', 'en'])) {
        session()->put('locale', $locale);
    }
    return redirect()->back();
})->name('switch-language');