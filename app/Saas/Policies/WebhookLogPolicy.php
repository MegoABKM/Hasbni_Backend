<?php

declare(strict_types=1);

namespace App\Saas\Policies;

use App\Saas\Models\WebhookLog;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class WebhookLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:WebhookLogResource');
    }

    public function view(AuthUser $authUser, WebhookLog $webhookLog): bool
    {
        return $authUser->can('View:WebhookLogResource');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:WebhookLogResource');
    }

    public function update(AuthUser $authUser, WebhookLog $webhookLog): bool
    {
        return $authUser->can('Update:WebhookLogResource');
    }

    public function delete(AuthUser $authUser, WebhookLog $webhookLog): bool
    {
        return $authUser->can('Delete:WebhookLogResource');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:WebhookLogResource');
    }

    public function restore(AuthUser $authUser, WebhookLog $webhookLog): bool
    {
        return $authUser->can('Restore:WebhookLogResource');
    }

    public function forceDelete(AuthUser $authUser, WebhookLog $webhookLog): bool
    {
        return $authUser->can('ForceDelete:WebhookLogResource');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:WebhookLogResource');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:WebhookLogResource');
    }

    public function replicate(AuthUser $authUser, WebhookLog $webhookLog): bool
    {
        return $authUser->can('Replicate:WebhookLogResource');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:WebhookLogResource');
    }
}
