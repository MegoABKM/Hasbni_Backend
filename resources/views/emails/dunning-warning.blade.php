<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Payment action required') }}</title>
</head>
<body style="margin:0;background:#f3f4f6;font-family:Arial,sans-serif;color:#111827">
<div style="max-width:600px;margin:32px auto;background:#fff;border:1px solid #e5e7eb;padding:32px">
    <h1 style="font-size:22px;margin:0 0 18px">
        {{ $isImpendingExpiration ? __('Subscription expiration reminder') : __('Payment action required') }}
    </h1>
    <p>{{ __('Hello :name,', ['name' => $user->name]) }}</p>
    @if ($isImpendingExpiration)
        <p>{{ __('Your subscription expires in :days days. Renew it to avoid entering the grace period.', ['days' => $day]) }}</p>
        <p><strong>{{ __('Subscription ends') }}:</strong> <bdi>{{ $subscription->ends_at?->translatedFormat('Y-m-d H:i') }}</bdi></p>
    @else
        <p>{{ __('We could not renew your subscription. Please update your payment method before the grace period ends to avoid write access being suspended.') }}</p>
        <p><strong>{{ __('Grace period ends') }}:</strong> <bdi>{{ $subscription->grace_period_ends_at?->translatedFormat('Y-m-d H:i') }}</bdi></p>
        <p>{{ __('Your existing data remains available in read-only mode after the grace period.') }}</p>
    @endif
</div>
</body>
</html>
