<?php
namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Profile;

class ProfileController extends Controller
{
    public function upsert(Request $request)
    {
        $user = $request->user();

        // 1. تحديث أو إنشاء البروفايل الأساسي
        $profile = Profile::updateOrCreate(
            ['user_id' => $user->id],
            $request->only(['shop_name', 'address', 'phone_number', 'city'])
        );

        // 2. تحديث أسعار الصرف المرتبطة بالمستخدم (وليس البروفايل)
        if ($request->has('exchange_rates') && is_array($request->exchange_rates)) {
            
            $user->exchangeRates()->delete();

            $ratesData = [];
            foreach ($request->exchange_rates as $rate) {
                if (isset($rate['rate_to_usd']) && $rate['rate_to_usd'] > 0) {
                    $ratesData[] = new ExchangeRate([
                        'currency_code' => strtoupper($rate['currency_code']),
                        'rate_to_usd' => (float) $rate['rate_to_usd'],
                    ]);
                }
            }

            if (!empty($ratesData)) {
                $user->exchangeRates()->saveMany($ratesData);
            }
        }

        // 👈 إرجاع البروفايل مع إرفاق العملات يدوياً ليتعرف عليها فلاتر
        $profile->exchange_rates = $user->exchangeRates;
        return response()->json($profile, 200);
    }

    public function show(Request $request) {
        $user = $request->user();
        
        if (!$user->profile) {
            $user->profile()->create([
                'shop_name' => $user->name ? $user->name . "'s Shop" : 'My Shop',
                'address' => '',
                'phone_number' => ''
            ]);
            $user->refresh();
        }

        $profile = $user->profile;
        // 👈 جلب العملات من المستخدم (User) وليس البروفايل
        $profile->exchange_rates = $user->exchangeRates; 
        return $profile;
    }

    public function update(Request $request) {
        return $this->upsert($request); 
    }

    public function setManagerPassword(Request $request) {
        $request->validate(['p_password' => 'required']);
        $user = $request->user();
        if (!$user->profile) $user->profile()->create(['shop_name' => 'My Shop']);
        
        $user->profile()->update([
            'manager_password' => Hash::make($request->p_password)
        ]);
        return response()->json(true);
    }

    public function verifyManagerPassword(Request $request) {
        $request->validate(['p_password' => 'required']);
        $profile = $request->user()->profile;
        if (!$profile || !$profile->manager_password) return response()->json(false);
        return response()->json(Hash::check($request->p_password, $profile->manager_password));
    }
    
    public function isManagerPasswordSet(Request $request) {
        $user = $request->user();
        if (!$user->profile) return response()->json(false);
        return response()->json((bool)$user->profile->manager_password);
    }
}


























