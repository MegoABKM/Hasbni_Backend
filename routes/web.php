<?php

use App\Saas\Models\Payment;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

Route::get('/get-cert', function () {
    abort_unless(auth()->check() && auth()->user()->role === 'super_admin', 403);

    $file = public_path('download/hasbni.pfx');

    if (! file_exists($file)) {
        abort(404, 'Certificate file is not available.');
    }

    return Response::download($file, 'hasbni.pfx', [
        'Content-Type' => 'application/x-pkcs12',
    ]);
})->middleware('auth');

Route::get('/get-app', function () {
    $file = public_path('download/hasbni.msix');

    if (! file_exists($file)) {
        abort(404, 'Application installer is not available.');
    }

    return Response::download($file, 'hasbni.msix', [
        'Content-Type' => 'application/vnc.ms-appx',
    ]);
});

Route::get('/hasbni-setup', function () {
    return view('download');
});

Route::get('/clear-cache', function () {
    abort_unless(auth()->check() && auth()->user()->role === 'super_admin', 403);

    Artisan::call('optimize:clear');

    if (function_exists('opcache_reset')) {
        opcache_reset();
    }

    return 'Cache cleared successfully.';
})->middleware('auth');

Route::get('switch-language/{locale}', function (string $locale) {
    if (in_array($locale, ['ar', 'en'], true)) {
        session()->put('locale', $locale);
    }

    return redirect()->back();
})->name('switch-language');

Route::get('/payments/{payment}/receipt', function (Payment $payment) {
    $payment->load(['user', 'subscription.plan']);

    return view('receipt', compact('payment'));
})
    ->middleware(['auth', 'manager'])
    ->name('payment.receipt');
