<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Instruction;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function faqs()
    {
        return response()->json([
            'success' => true,
            'data' => Faq::where('is_active', true)->orderBy('sort_order', 'asc')->get(),
        ]);
    }

    public function instructions()
    {
        return response()->json([
            'success' => true,
            'data' => Instruction::where('is_active', true)->orderBy('sort_order', 'asc')->get(),
        ]);
    }

    public function myTickets(Request $request)
    {
        $tickets = $request->user()->supportTickets()->latest()->get();

        return response()->json(['success' => true, 'data' => $tickets]);
    }

    public function createTicket(Request $request): JsonResponse
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $ticket = $request->user()->supportTickets()->create([
            'subject' => $request->subject,
            'message' => $request->message,
            'status' => 'open',
        ]);

        $recipients = User::query()
            ->whereIn('role', ['super_admin', 'support_admin'])
            ->select('id')
            ->get();

        if ($recipients->isNotEmpty()) {
            Notification::make()
                ->title(__('New support ticket'))
                ->body(__('A tenant submitted a new support ticket: :subject', [
                    'subject' => $ticket->subject,
                ]))
                ->warning()
                ->sendToDatabase($recipients, isEventDispatched: true);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم استلام طلبك بنجاح. سيتم الرد عليك قريباً.',
            'data' => $ticket,
        ]);
    }
}
