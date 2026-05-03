<?php
namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        // استخدام simplePaginate هو الأسرع والأقل استهلاكاً للـ RAM
        return AuditLog::where('user_id', $request->user()->id)
            ->latest()
            ->simplePaginate(30); 
    }
}