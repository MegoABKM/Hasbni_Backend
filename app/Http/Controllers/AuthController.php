<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditLog;
use App\Models\Plan;
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
            'name'          => 'required|string|max:100',
            'shop_name'     => 'required|string|max:150',
            'email'         => 'required|email|unique:users',
            'phone'         => 'required|string|max:20',
            'country'       => 'required|string|max:100',
            'business_type' => 'required|string|max:100',
            'password'      => ['required', \Illuminate\Validation\Rules\Password::min(8)->letters()->numbers()]
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            if ($errors->has('email') && $errors->first('email') === 'The email has already been taken.') {
                return response()->json(['message' => 'error_messages.email_taken'], 422);
            }
            if ($errors->has('password')) {
                return response()->json(['message' => 'error_messages.weak_password'], 422);
            }
            return response()->json(['message' => 'error_messages.validation_error'], 422);
        }

        $data = $validator->validated();

        // 1. إنشاء المستخدم (ولكن بدون تفعيل الإيميل حالياً)
        $user = User::create([
            'name'          => $data['name'],
            'email'         => $data['email'],
            'phone'         => $data['phone'],
            'country'       => $data['country'],
            'business_type' => $data['business_type'],
            'password'      => Hash::make($data['password']),
            'role'          => 'shop_owner', 
        ]);
        
        $user->profile()->create([
            'shop_name'    => $data['shop_name'],
            'phone_number' => $data['phone']
        ]);
        
        // ربط الباقة المجانية تلقائياً
        $freePlan = Plan::where('name', 'Free')->first();
        if ($freePlan) {
            $user->subscription()->create([
                'plan_id'       => $freePlan->id,
                'status'        => 'active',
                'billing_cycle' => 'monthly', 
                'starts_at'     => now(),
                'ends_at'       => now()->addMonth(), 
            ]);
        }

        // 2. توليد رمز OTP للتحقق من الحساب وإرساله
        $otp = rand(100000, 999999);
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($otp), 'created_at' => now()->addMinutes(15)] 
        );

        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\ResetPasswordOtpMail($otp));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Registration Mail failed: " . $e->getMessage());
            \Illuminate\Support\Facades\Log::info("OTP for registration {$user->email} is: {$otp}");
        }

        // 🚨 نرجع رد يفيد بأن الحساب مسجل ولكنه يتطلب التحقق (بدون توكن)
        return response()->json([
            'message' => 'verification_required',
            'email' => $user->email
        ], 200);
    }

    // 🚀 دالة التحقق من إيميل التسجيل وتوليد التوكن
    public function verifyEmailRegistration(Request $request) {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric|digits:6',
        ]);

        $record = \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record || !Hash::check($request->otp, $record->token)) {
            return response()->json(['success' => false, 'message' => 'error_messages.invalid_otp'], 400);
        }

        // تفعيل الحساب في قاعدة البيانات
        $user = User::where('email', $request->email)->first();
        $user->email_verified_at = now();
        $user->save();

        // مسح الرمز المستخدم
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        $this->logAuthEvent($user, 'verified_and_logged_in');

        // 🚀 الآن فقط نقوم بتوليد التوكن وإعطائه الصلاحية الكاملة للدخول
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'user' => $user
        ], 200);
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
    
   public function forgotPassword(Request $request) {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $otp = rand(100000, 999999);
        $expiresAt = now()->addMinutes(15);

        // حفظ الـ OTP مشفراً في قاعدة البيانات
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => \Illuminate\Support\Facades\Hash::make($otp), 'created_at' => $expiresAt] 
        );

        try {
            // إرسال الإيميل الفعلي عبر SMTP
            \Illuminate\Support\Facades\Mail::to($request->email)->send(new \App\Mail\ResetPasswordOtpMail($otp));
            
            return response()->json(['success' => true, 'message' => 'تم إرسال رمز التحقق إلى بريدك الإلكتروني.']);
        } catch (\Exception $e) {
            // تسجيل الخطأ في السيرفر في حال وجود مشكلة في بيانات الـ SMTP
            \Illuminate\Support\Facades\Log::error("SMTP Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'فشل إرسال الإيميل، يرجى التأكد من إعدادات الـ SMTP في السيرفر.'], 500);
        }
    }
    public function verifyOtp(Request $request) {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric|digits:6',
        ]);

        $record = \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record || !Hash::check($request->otp, $record->token)) {
            return response()->json(['success' => false, 'message' => 'error_messages.invalid_otp'], 400);
        }

        if (\Carbon\Carbon::parse($record->created_at)->isPast()) {
            return response()->json(['success' => false, 'message' => 'error_messages.expired_otp'], 400);
        }

        return response()->json(['success' => true, 'message' => 'OTP verified successfully.']);
    }

    public function resetPassword(Request $request) {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric|digits:6',
            'password' => ['required', \Illuminate\Validation\Rules\Password::min(8)->letters()->numbers()]
        ]);

        $record = \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record || !Hash::check($request->otp, $record->token)) {
            return response()->json(['success' => false, 'message' => 'error_messages.invalid_otp'], 400);
        }

        // تحديث كلمة المرور
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // حذف رمز الـ OTP بعد استخدامه
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // حذف كل الجلسات السابقة (تسجيل الخروج من كل الأجهزة)
        $user->tokens()->delete();

        $this->logAuthEvent($user, 'password_reset');

        return response()->json(['success' => true, 'message' => 'success_messages.password_reset']);
    }
}