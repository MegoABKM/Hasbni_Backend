<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $request) {
        $user = $request->user();
        $profile = $user->profile;
        // Append exchange rates to profile response to match Flutter model
        $profile->exchange_rates = $user->exchangeRates;
        return $profile;
    }

    public function update(Request $request) {
        $user = $request->user();
        
        // Update Profile
        $user->profile()->update($request->only(['shop_name', 'address', 'phone_number', 'city']));

        // Upsert Exchange Rates
        if ($request->has('exchange_rates')) {
            foreach ($request->exchange_rates as $rate) {
                $user->exchangeRates()->updateOrCreate(
                    ['currency_code' => $rate['currency_code']],
                    ['rate_to_usd' => $rate['rate_to_usd']]
                );
            }
        }
        
        return $this->show($request);
    }

    public function setManagerPassword(Request $request) {
        $request->validate(['p_password' => 'required']);
        $request->user()->profile()->update([
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
        return response()->json((bool)$request->user()->profile->manager_password);
    }
}