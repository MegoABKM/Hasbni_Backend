<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Saas\Models\Payment;
use App\Support\RbacPermission;

final class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:PaymentResource');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->can('View:PaymentResource');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:PaymentResource');
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->can('Update:PaymentResource');
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->can('Delete:PaymentResource');
    }

    public function refund(User $user, Payment $payment): bool
    {
        return $payment->status === 'successful'
            && $user->can(RbacPermission::REFUND_PAYMENT);
    }
}
