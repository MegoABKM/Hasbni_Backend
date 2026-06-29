<?php
// app/Http/Middleware/SetLocale.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        // إذا كان هناك لغة محفوظة في الجلسة، استخدمها. وإلا استخدم 'ar'
        $locale = session()->get('locale', 'ar');
        
        App::setLocale($locale);

        return $next($request);
    }
}