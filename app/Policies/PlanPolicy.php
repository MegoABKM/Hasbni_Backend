<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Saas\Models\Plan;

final class PlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'finance_admin']);
    }

    public function view(User $user, Plan $plan): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Plan $plan): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Plan $plan): bool
    {
        return $user->role === 'super_admin';
    }
}
