<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Saas\Models\AppConfig;

final class AppConfigPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'super_admin';
    }

    public function view(User $user, AppConfig $config): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, AppConfig $config): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, AppConfig $config): bool
    {
        return $this->viewAny($user);
    }
}
