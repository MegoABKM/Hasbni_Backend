<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Saas\Models\Subscription;

final class SubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:SubscriptionResource');
    }

    public function view(User $user, Subscription $subscription): bool
    {
        return $user->can('View:SubscriptionResource');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:SubscriptionResource');
    }

    public function update(User $user, Subscription $subscription): bool
    {
        return $user->can('Update:SubscriptionResource');
    }

    public function delete(User $user, Subscription $subscription): bool
    {
        return $user->can('Delete:SubscriptionResource');
    }
}
