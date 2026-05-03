<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    private function logAuthEvent($user, $event) {
        AuditLog::create([
            'user_id' => $user->id,
            'event' => $event,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function register(Request $request) {
        $data = $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6'
        ]);

        $user = User::create([
            'name' => 'Shop Owner',
            'email' => $data['email'],
            'password' => Hash::make($data['password'])
        ]);
        
        $user->profile()->create(['shop_name' => 'My Shop']);
        
        $this->logAuthEvent($user, 'registered');

        $token = $user->createToken('mobile')->plainTextToken;
        return response()->json(['access_token' => $token, 'user' => $user]);
    }

    public function login(Request $request) {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة'], 401);
        }
        
        $user = User::where('email', $request->email)->firstOrFail();
        
        $this->logAuthEvent($user, 'login');

        $token = $user->createToken('mobile')->plainTextToken;
        return response()->json(['access_token' => $token, 'user' => $user]);
    }

    public function logout(Request $request) {
        $user = $request->user();
        if($user) {
            $this->logAuthEvent($user, 'logout');
            $user->currentAccessToken()->delete();
        }
        return response()->json(['message' => 'Logged out']);
    }
}