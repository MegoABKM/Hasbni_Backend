<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FcmToken;

class FcmController extends Controller
{
    public function updateToken(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
            'token' => 'required|string'
        ]);

        FcmToken::updateOrCreate(
            [
                'user_id' => $request->user()->id, 
                'device_id' => $request->device_id
            ],
            [
                'token' => $request->token
            ]
        );

        return response()->json(['success' => true]);
    }
}