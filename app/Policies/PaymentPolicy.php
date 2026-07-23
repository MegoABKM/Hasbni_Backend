<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Saas\Models\Payment;

final class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'finance_admin']);
    }

    public function view(User $user, Payment $payment): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Payment $payment): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->role === 'super_admin';
    }

    public function refund(User $user, Payment $payment): bool
    {
        return $this->viewAny($user) && $payment->status === 'successful';
    }
}
