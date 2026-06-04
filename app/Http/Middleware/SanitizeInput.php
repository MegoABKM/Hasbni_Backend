<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH')) {
            $input = $request->all();
            array_walk_recursive($input, function (&$value) {
                if (is_string($value)) {
                    // إزالة أي وسوم HTML أو PHP لمنع XSS
                    $value = strip_tags($value);
                }
            });
            $request->merge($input);
        }

        return $next($request);
    }
}
