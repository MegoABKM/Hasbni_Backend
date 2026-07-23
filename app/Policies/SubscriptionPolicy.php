<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Saas\Models\Subscription;

final class SubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'finance_admin']);
    }

    public function view(User $user, Subscription $subscription): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Subscription $subscription): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Subscription $subscription): bool
    {
        return $this->viewAny($user);
    }
}
