<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Saas\Models\AuditLog;

final class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:AuditLogResource');
    }

    public function view(User $user, AuditLog $log): bool
    {
        return $user->can('View:AuditLogResource');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AuditLog $log): bool
    {
        return false;
    }

    public function delete(User $user, AuditLog $log): bool
    {
        return false;
    }
}
