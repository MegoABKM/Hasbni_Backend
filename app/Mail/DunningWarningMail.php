<?php

declare(strict_types=1);

namespace App\Mail;

use App\Saas\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class DunningWarningMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Subscription $subscription,
        public readonly int $day,
        public readonly bool $isImpendingExpiration = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->isImpendingExpiration
            ? __('Your subscription expires soon')
            : __('Action required: update your payment method'));
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.dunning-warning',
            with: [
                'user' => $this->subscription->user,
                'subscription' => $this->subscription,
                'day' => $this->day,
                'isImpendingExpiration' => $this->isImpendingExpiration,
            ],
        );
    }
}
