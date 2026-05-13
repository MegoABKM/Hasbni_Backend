<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;

// 1. مسار تحميل الشهادة
Route::get('/get-cert', function () {
    // 🚨 تم تصحيح اسم المجلد من downloads إلى download ليتطابق مع مجلدك
    $file = public_path('download/hasbni.pfx');
    
    // التحقق من وجود الملف لتجنب ظهور صفحة خطأ 500
    if (!file_exists($file)) {
        abort(404, 'عذراً، ملف الشهادة غير موجود في السيرفر.');
    }

    return Response::download($file, 'hasbni.pfx', [
        'Content-Type' => 'application/x-pkcs12'
    ]);
});

// 2. 🚨 مسار تحميل التطبيق (الذي كان مفقوداً) 🚨
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