<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class EmployeeController extends Controller {
    public function index(Request $request) {
        return $request->user()->employees;
    }
    public function store(Request $request) {
        return $request->user()->employees()->create($request->all());
    }
    public function update(Request $request, $id) {
        $request->user()->employees()->findOrFail($id)->update($request->all());
        return response()->json(['success'=>true]);
    }
    public function destroy(Request $request, $id) {
        $request->user()->employees()->findOrFail($id)->delete();
        return response()->json(['success'=>true]);
    }
}