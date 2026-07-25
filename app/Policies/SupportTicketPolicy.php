<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Saas\Models\SupportTicket;

final class SupportTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:SupportTicketResource');
    }

    public function view(User $user, SupportTicket $ticket): bool
    {
        return $user->can('View:SupportTicketResource');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:SupportTicketResource');
    }

    public function update(User $user, SupportTicket $ticket): bool
    {
        return $user->can('Update:SupportTicketResource');
    }

    public function delete(User $user, SupportTicket $ticket): bool
    {
        return $user->can('Delete:SupportTicketResource');
    }
}
