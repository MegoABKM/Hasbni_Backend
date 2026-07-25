<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Saas\Models\Plan;

final class PlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:PlanResource');
    }

    public function view(User $user, Plan $plan): bool
    {
        return $user->can('View:PlanResource');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:PlanResource');
    }

    public function update(User $user, Plan $plan): bool
    {
        return $user->can('Update:PlanResource');
    }

    public function delete(User $user, Plan $plan): bool
    {
        return $user->can('Delete:PlanResource');
    }
}
