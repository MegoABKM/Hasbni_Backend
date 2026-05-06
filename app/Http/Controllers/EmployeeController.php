<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class EmployeeController extends Controller {
    public function index(Request $request) {
        return $request->user()->employees;
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'pin_code' => 'required|string|max:50',
        ]);

        // Anti-Duplication
        $existing = $request->user()->employees()->where('full_name', $validated['full_name'])->first();
        if ($existing) return $existing;

        return $request->user()->employees()->create($validated);
    }

    public function update(Request $request, $id) {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'pin_code' => 'required|string|max:50',
        ]);
        $request->user()->employees()->findOrFail($id)->update($validated);
        return response()->json(['success'=>true]);
    }

    public function destroy(Request $request, $id) {
        $request->user()->employees()->findOrFail($id)->delete();
        return response()->json(['success'=>true]);
    }
}