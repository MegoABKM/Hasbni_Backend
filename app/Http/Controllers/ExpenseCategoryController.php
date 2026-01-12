<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request) {
        return $request->user()->expenseCategories;
    }

    public function store(Request $request) {
        return $request->user()->expenseCategories()->create($request->all());
    }
}