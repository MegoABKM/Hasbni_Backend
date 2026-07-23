<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Saas\Models\SupportTicket;

final class SupportTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'support_admin']);
    }

    public function view(User $user, SupportTicket $ticket): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, SupportTicket $ticket): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, SupportTicket $ticket): bool
    {
        return $user->role === 'super_admin';
    }
}
