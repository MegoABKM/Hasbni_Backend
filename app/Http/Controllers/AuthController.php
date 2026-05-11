<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditLog;
use App\Models\Plan; // 👈 تم إضافة استدعاء موديل الخطط
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            if ($errors->has('email') && $errors->first('email') === 'The email has already been taken.') {
                return response()->json(['message' => 'error_messages.email_taken'], 422);
            }
            return response()->json(['message' => 'error_messages.validation_error'], 422);
        }

        $data = $validator->validated();

        $user = User::create([
            'name' => 'Shop Owner',
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'shop_owner', // 👈 التأكد من إعطاء الصلاحية الافتراضية
        ]);
        
        $user->profile()->create(['shop_name' => 'My Shop']);
        
        // 🚀 النظام السحري: ربط المستخدم تلقائياً بالخطة المجانية فور تسجيله
            $freePlan = Plan::where('name', 'Free')->first();
        if ($freePlan) {
            $user->subscription()->create([
                'plan_id' => $freePlan->id,
                'status' => 'active',
                'billing_cycle' => 'monthly', // 👈 تم التعديل إلى شهري
                'starts_at' => now(),
                'ends_at' => now()->addMonth(), // 👈 تم التعديل ليكون شهراً واحداً من الآن
            ]);
        }
        
        $this->logAuthEvent($user, 'registered');
        $token = $user->createToken('mobile')->plainTextToken;
        
        return response()->json(['access_token' => $token, 'user' => $user]);
    }

    public function login(Request $request) {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'invalid_credentials'], 401);
        }
        
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