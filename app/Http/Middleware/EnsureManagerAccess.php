<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EnsureManagerAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $profile = $user->profile;

        // إذا لم يتم تعيين باسورد للمدير، نسمح بالمرور
        if (!$profile || !$profile->manager_password) {
            return $next($request);
        }

        $headerPin = $request->header('X-Manager-Password');
        
        // التحقق من أن الباسورد المُرسل يطابق باسورد المدير
        if (!$headerPin || !Hash::check($headerPin, $profile->manager_password)) {
            return response()->json(['message' => 'عذراً، هذا الإجراء يتطلب صلاحيات المدير.'], 403);
        }

        return $next($request);
    }
}