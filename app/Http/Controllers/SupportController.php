<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Faq;
use App\Models\Instruction;
use App\Models\SupportTicket;

class SupportController extends Controller
{
    public function faqs()
    {
        return response()->json([
            'success' => true,
            'data' => Faq::where('is_active', true)->orderBy('sort_order', 'asc')->get()
        ]);
    }

    public function instructions()
    {
        return response()->json([
            'success' => true,
            'data' => Instruction::where('is_active', true)->orderBy('sort_order', 'asc')->get()
        ]);
    }

    public function myTickets(Request $request)
    {
        $tickets = $request->user()->supportTickets()->latest()->get();
        return response()->json(['success' => true, 'data' => $tickets]);
    }

    public function createTicket(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $ticket = $request->user()->supportTickets()->create([
            'subject' => $request->subject,
            'message' => $request->message,
            'status' => 'open'
        ]);

        return response()->json([
            'success' => true, 
            'message' => 'تم استلام طلبك بنجاح. سيتم الرد عليك قريباً.',
            'data' => $ticket
        ]);
    }
}