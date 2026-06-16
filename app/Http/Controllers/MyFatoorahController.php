<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class MyFatoorahController extends Controller
{
    private $baseUrl;
    private $apiKey;

    public function __construct()
    {
        // تغيير الرابط من apitest إلى api عند الإطلاق الفعلي
        $this->baseUrl = env('MYFATOORAH_URL', 'https://apitest.myfatoorah.com');
        $this->apiKey = env('MYFATOORAH_TOKEN', '');
    }

    // 1. إنشاء رابط الدفع وارساله لتطبيق فلاتر
    public function checkout(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'cycle' => 'required|in:monthly,yearly',
        ]);

        $user = $request->user();
        $plan = Plan::findOrFail($request->plan_id);
        
        $amount = $request->cycle === 'yearly' ? $plan->yearly_price : $plan->monthly_price;

        $response = Http::withToken($this->apiKey)->post("{$this->baseUrl}/v2/SendPayment", [
            'NotificationOption' => 'LNK',
            'InvoiceValue'       => $amount,
            'CustomerName'       => $user->name,
            'DisplayCurrencyIso' => 'USD',
            'CustomerEmail'      => $user->email,
            'CallBackUrl'        => url('/api/webhooks/myfatoorah/callback'),
            'ErrorUrl'           => url('/api/webhooks/myfatoorah/callback'),
            'Language'           => 'ar',
            'UserDefinedField'   => $user->id . '|' . $plan->id . '|' . $request->cycle, // تمرير بيانات العميل والباقة خفية
        ]);

        if ($response->successful() && $response->json('IsSuccess') === true) {
            return response()->json([
                'success' => true,
                'payment_url' => $response->json('Data.InvoiceURL')
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Payment gateway error'], 500);
    }

    // 2. استقبال الرد من ماي فاتورة بعد الدفع
    public function callback(Request $request)
    {
        $paymentId = $request->query('paymentId');
        
        if (!$paymentId) {
            return response()->json(['error' => 'No payment ID provided'], 400);
        }

        $response = Http::withToken($this->apiKey)->post("{$this->baseUrl}/v2/getPaymentStatus", [
            'Key' => $paymentId,
            'KeyType' => 'PaymentId'
        ]);

        $data = $response->json();

        if ($data['IsSuccess'] === true && $data['Data']['InvoiceStatus'] === 'Paid') {
            
            $customData = explode('|', $data['Data']['UserDefinedField']); // استرجاع البيانات الخفية
            $userId = $customData[0] ?? null;
            $planId = $customData[1] ?? null;
            $cycle = $customData[2] ?? 'monthly';

            if ($userId && $planId) {
                // منع تسجيل الدفعة مرتين
                $existing = Payment::where('transaction_id', $paymentId)->first();
                if($existing) return response()->json(['success' => true]);

                $daysToAdd = $cycle === 'yearly' ? 365 : 30;

                // تحديث أو إنشاء اشتراك
                $subscription = Subscription::updateOrCreate(
                    ['user_id' => $userId],
                    [
                        'plan_id' => $planId,
                        'status' => 'active',
                        'billing_cycle' => $cycle,
                        'starts_at' => now(),
                        'ends_at' => now()->addDays($daysToAdd),
                    ]
                );

                // تسجيل الفاتورة
                Payment::create([
                    'user_id' => $userId,
                    'subscription_id' => $subscription->id,
                    'amount' => $data['Data']['InvoiceValue'],
                    'currency' => 'USD',
                    'payment_method' => 'myfatoorah',
                    'status' => 'successful',
                    'transaction_id' => $paymentId,
                    'paid_at' => now(),
                ]);

                Log::info("MyFatoorah Payment Success for User ID: {$userId}");
                
                // يمكنك توجيه المستخدم لصفحة نجاح أو إرجاع رسالة JSON حسب حاجتك في فلاتر
                return response()->json(['success' => true, 'message' => 'Payment Successful! Subscription Active.']);
            }
        }

        return response()->json(['success' => false, 'message' => 'Payment failed or canceled.']);
    }
}