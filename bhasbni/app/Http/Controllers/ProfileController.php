<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $request) {
        $user = $request->user();
        
        // Safety: Create profile if missing
        if (!$user->profile) {
            $user->profile()->create([
                'shop_name' => $user->name ? $user->name . "'s Shop" : 'My Shop',
                'address' => '',
                'phone_number' => ''
            ]);
            $user->refresh();
        }

        $profile = $user->profile;
        $profile->exchange_rates = $user->exchangeRates;
        return $profile;
    }

    public function update(Request $request) {
        // 1. VALIDATE INPUT
        $request->validate([
            'shop_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
            'exchange_rates' => 'nullable|array',
            'exchange_rates.*.currency_code' => 'required|string',
            'exchange_rates.*.rate_to_usd' => 'required|numeric',
        ]);

        $user = $request->user();

        // 2. SAFETY: Ensure Profile Exists before updating
        // This prevents the "Call to a member function update() on null" 500 error
        $profile = $user->profile()->firstOrCreate(
            [], // Conditions
            ['shop_name' => 'New Shop'] // Default if creating
        );

        // 3. UPDATE PROFILE
        $profile->update($request->only(['shop_name', 'address', 'phone_number', 'city']));

        // 4. UPSERT EXCHANGE RATES
        if ($request->has('exchange_rates')) {
            foreach ($request->exchange_rates as $rate) {
                // Determine rate based on incoming data type
                $rateValue = is_array($rate) ? $rate['rate_to_usd'] : $rate;
                $code = is_array($rate) ? $rate['currency_code'] : null;

                if ($code) {
                    $user->exchangeRates()->updateOrCreate(
                        ['currency_code' => $code], // Search by Code AND User ID
                        ['rate_to_usd' => $rateValue]
                    );
                }
            }
        }
        
        // Return fresh data
        return $this->show($request);
    }

    public function setManagerPassword(Request $request) {
        $request->validate(['p_password' => 'required']);
        
        $user = $request->user();
        if (!$user->profile) {
             $user->profile()->create(['shop_name' => 'My Shop']);
        }

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