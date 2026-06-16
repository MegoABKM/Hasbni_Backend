<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FcmController extends Controller
{
    public function updateToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string'
        ]);

        $request->user()->update([
            'fcm_token' => $request->fcm_token
        ]);

        return response()->json(['success' => true, 'message' => 'Token updated']);
    }
}