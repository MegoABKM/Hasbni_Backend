<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request) {
        $data = $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6'
        ]);

        $user = User::create([
            'name' => 'Shop Owner', // <--- ADD THIS LINE
            'email' => $data['email'],
            'password' => Hash::make($data['password'])
        ]);
        
        // Initialize Profile
        $user->profile()->create(['shop_name' => 'My Shop']);

        $token = $user->createToken('mobile')->plainTextToken;
        return response()->json(['access_token' => $token, 'user' => $user]);
    }

    public function login(Request $request) {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة'], 401);
        }
        
        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('mobile')->plainTextToken;
        return response()->json(['access_token' => $token, 'user' => $user]);
    }

    public function logout(Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}