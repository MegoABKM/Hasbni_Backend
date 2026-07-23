<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Payment Receipt') }} #{{ $payment->getKey() }}</title>
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f3f4f6; color: #111827; }
        .receipt-page { max-width: 820px; margin: 40px auto; padding: 0 20px; }
        .receipt { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; box-shadow: 0 10px 30px rgba(15, 23, 42, .08); overflow: hidden; }
        .receipt-header { display: flex; justify-content: space-between; gap: 24px; padding: 36px; border-bottom: 1px solid #e5e7eb; }
        .eyebrow { margin: 0 0 8px; color: #0f766e; font-size: 12px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        h1 { margin: 0; font-size: 28px; letter-spacing: -.02em; }
        .muted { color: #6b7280; }
        .receipt-meta { text-align: end; font-size: 14px; line-height: 1.8; }
        .receipt-body { padding: 36px; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px; margin-bottom: 36px; }
        .label { display: block; margin-bottom: 6px; color: #6b7280; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; }
        .value { font-size: 15px; font-weight: 600; }
        .summary { width: 100%; border-collapse: collapse; }
        .summary th, .summary td { padding: 16px 0; border-bottom: 1px solid #e5e7eb; text-align: start; }
        .summary th { color: #6b7280; font-size: 13px; font-weight: 600; }
        .summary td:last-child, .summary th:last-child { text-align: end; }
        .total td { border-bottom: 0; padding-top: 24px; font-size: 20px; font-weight: 800; }
        .badge { display: inline-flex; padding: 4px 10px; border-radius: 999px; background: #dcfce7; color: #166534; font-size: 12px; font-weight: 700; text-transform: capitalize; }
        .receipt-footer { display: flex; justify-content: space-between; gap: 16px; padding: 20px 36px; background: #f9fafb; color: #6b7280; font-size: 12px; }
        .print-button { position: fixed; top: 20px; inset-inline-end: 20px; padding: 10px 16px; border: 0; border-radius: 8px; background: #0f766e; color: #fff; cursor: pointer; font-weight: 700; }
        @media (max-width: 640px) { .receipt-page { margin: 16px auto; padding: 0 12px; } .receipt-header, .receipt-body { padding: 24px; } .receipt-header, .receipt-footer { flex-direction: column; } .receipt-meta { text-align: start; } .grid { grid-template-columns: 1fr; } }
        @media print { body { background: #fff; } .receipt-page { max-width: none; margin: 0; padding: 0; } .receipt { border: 0; box-shadow: none; } .print-button { display: none; } }
    </style>
</head>
<body>
    @unless ($pdf ?? false)
        <button class="print-button no-print" type="button" onclick="window.print()">{{ __('Print') }}</button>
    @endunless

    <main class="receipt-page">
        <article class="receipt">
            <header class="receipt-header">
                <div>
                    <p class="eyebrow">{{ config('app.name', 'Universal SaaS') }}</p>
                    <h1>{{ __('Payment Receipt') }}</h1>
                </div>
                <div class="receipt-meta">
                    <div><strong>{{ __('Receipt') }}:</strong> #{{ $payment->getKey() }}</div>
                    <div><strong>{{ __('Date') }}:</strong> {{ $payment->paid_at?->format('F j, Y') ?? __('Not Available') }}</div>
                    <div><strong>{{ __('Transaction ID') }}:</strong> <bdi>{{ $payment->transaction_id ?? 'PAY-'.$payment->getKey() }}</bdi></div>
                </div>
            </header>

            <section class="receipt-body">
                <div class="grid">
                    <div>
                        <span class="label">{{ __('Billed To') }}</span>
                        <div class="value">{{ $payment->user?->name ?? __('Unknown Tenant') }}</div>
                        <div class="muted">{{ $payment->user?->email ?? __('No Email') }}</div>
                        @if ($payment->user?->country)
                            <div class="muted">{{ $payment->user->country }}</div>
                        @endif
                    </div>
                    <div>
                        <span class="label">{{ __('Payment Status') }}</span>
                        <span class="badge">{{ str_replace('_', ' ', $payment->status) }}</span>
                    </div>
                </div>

                <table class="summary">
                    <thead>
                        <tr>
                            <th>{{ __('Description') }}</th>
                            <th>{{ __('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                {{ $payment->subscription?->plan?->name ?? __('SaaS Subscription') }}
                                @if ($payment->subscription?->billing_cycle)
                                    <div class="muted">{{ ucfirst($payment->subscription->billing_cycle) }}</div>
                                @endif
                            </td>
                            <td><bdi>{{ number_format((float) $payment->amount, 2) }} {{ strtoupper($payment->currency ?? 'USD') }}</bdi></td>
                        </tr>
                        <tr class="total">
                            <td>{{ __('Amount Paid') }}</td>
                            <td><bdi>{{ number_format((float) $payment->amount, 2) }} {{ strtoupper($payment->currency ?? 'USD') }}</bdi></td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <footer class="receipt-footer">
                <span>{{ __('Thank you for your business.') }}</span>
                <span>{{ config('app.url') }}</span>
            </footer>
        </article>
    </main>
</body>
</html>
