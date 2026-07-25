<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Saas\Models\AppConfig;

final class AppConfigPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:AppConfigResource');
    }

    public function view(User $user, AppConfig $config): bool
    {
        return $user->can('View:AppConfigResource');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:AppConfigResource');
    }

    public function update(User $user, AppConfig $config): bool
    {
        return $user->can('Update:AppConfigResource');
    }

    public function delete(User $user, AppConfig $config): bool
    {
        return $user->can('Delete:AppConfigResource');
    }
}
