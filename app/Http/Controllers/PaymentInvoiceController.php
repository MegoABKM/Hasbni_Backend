<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Saas\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class PaymentInvoiceController extends Controller
{
    public function download(Request $request, Payment $payment): Response
    {
        $user = $request->user();

        abort_unless(
            $user !== null
            && ($user->can('View:PaymentResource') || $payment->user_id === $user->getKey()),
            Response::HTTP_FORBIDDEN,
        );

        $payment->loadMissing(['user', 'subscription.plan']);

        return Pdf::loadView('receipt', [
            'payment' => $payment,
            'pdf' => true,
        ])->download("invoice-{$payment->getKey()}.pdf");
    }
}
