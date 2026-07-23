<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'support_admin', 'finance_admin']);
    }

    public function view(User $user, User $tenant): bool
    {
        return $this->viewAny($user) && $tenant->role === 'tenant';
    }

    public function create(User $user): bool
    {
        return $user->role === 'super_admin';
    }

    public function update(User $user, User $tenant): bool
    {
        return $user->role === 'super_admin' && $tenant->role === 'tenant';
    }

    public function delete(User $user, User $tenant): bool
    {
        return $this->update($user, $tenant);
    }

    public function restore(User $user, User $tenant): bool
    {
        return false;
    }

    public function forceDelete(User $user, User $tenant): bool
    {
        return $this->delete($user, $tenant);
    }

    public function impersonate(User $user, User $tenant): bool
    {
        return $this->update($user, $tenant);
    }
}
