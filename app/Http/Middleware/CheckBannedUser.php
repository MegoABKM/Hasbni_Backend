<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBannedUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->is_banned) {
            // سحب التوكنات لطرده من التطبيق
            $request->user()->tokens()->delete();
            return response()->json(['message' => 'Your account has been banned by the administrator.'], 403);
        }

        return $next($request);
    }
}