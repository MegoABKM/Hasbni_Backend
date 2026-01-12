<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class OwnerWithdrawalController extends Controller {
    public function index(Request $request) {
        return $request->user()->withdrawals()->latest('withdrawal_date')->get();
    }
    public function store(Request $request) {
        return $request->user()->withdrawals()->create($request->all());
    }
    public function update(Request $request, $id) {
        $request->user()->withdrawals()->findOrFail($id)->update($request->all());
        return response()->json(['success'=>true]);
    }
    public function destroy(Request $request, $id) {
        $request->user()->withdrawals()->findOrFail($id)->delete();
        return response()->json(['success'=>true]);
    }
}