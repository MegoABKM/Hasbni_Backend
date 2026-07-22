<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<body style="margin:0;background:#f3f4f6;color:#111827;font-family:Arial,sans-serif;">
    <div style="max-width:620px;margin:32px auto;padding:32px;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;">
        <p style="margin:0 0 8px;color:#0f766e;font-size:12px;font-weight:bold;text-transform:uppercase;letter-spacing:.08em;">{{ config('app.name', 'Universal SaaS') }}</p>
        <h1 style="margin:0 0 24px;font-size:24px;">{{ __('An admin has replied to your support ticket.') }}</h1>
        <p style="color:#4b5563;">{{ __('Your support request has received a new reply.') }}</p>
        <div style="margin:24px 0;padding:20px;background:#f9fafb;border-radius:8px;">
            <p style="margin:0 0 8px;font-weight:bold;">{{ $ticket->subject }}</p>
            <p style="margin:0;white-space:pre-line;color:#374151;">{{ $ticket->admin_reply }}</p>
        </div>
        <p style="margin:0;color:#6b7280;font-size:13px;">{{ __('Ticket') }} #{{ $ticket->getKey() }}</p>
    </div>
</body>
</html>
