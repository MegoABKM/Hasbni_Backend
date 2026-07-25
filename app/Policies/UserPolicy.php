<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Support\RbacPermission;

final class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:UserResource');
    }

    public function view(User $user, User $tenant): bool
    {
        return $tenant->isTenant() && $user->can('View:UserResource');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:UserResource');
    }

    public function update(User $user, User $tenant): bool
    {
        return $tenant->isTenant() && $user->can('Update:UserResource');
    }

    public function delete(User $user, User $tenant): bool
    {
        return $tenant->isTenant() && $user->can('Delete:UserResource');
    }

    public function restore(User $user, User $tenant): bool
    {
        return false;
    }

    public function forceDelete(User $user, User $tenant): bool
    {
        return $tenant->isTenant() && $user->can('ForceDelete:UserResource');
    }

    public function impersonate(User $user, User $tenant): bool
    {
        return $tenant->isTenant()
            && $user->can(RbacPermission::IMPERSONATE_TENANT);
    }
}
