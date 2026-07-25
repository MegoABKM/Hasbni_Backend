<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

final class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:RoleResource');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('View:RoleResource');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:RoleResource');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('Update:RoleResource');
    }

    public function delete(User $user, Role $role): bool
    {
        return $role->name !== 'super_admin'
            && $user->can('Delete:RoleResource');
    }
}
