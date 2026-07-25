<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FailedJob;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class FailedJobPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FailedJobResource');
    }

    public function view(AuthUser $authUser, FailedJob $failedJob): bool
    {
        return $authUser->can('View:FailedJobResource');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FailedJobResource');
    }

    public function update(AuthUser $authUser, FailedJob $failedJob): bool
    {
        return $authUser->can('Update:FailedJobResource');
    }

    public function delete(AuthUser $authUser, FailedJob $failedJob): bool
    {
        return $authUser->can('Delete:FailedJobResource');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:FailedJobResource');
    }

    public function restore(AuthUser $authUser, FailedJob $failedJob): bool
    {
        return $authUser->can('Restore:FailedJobResource');
    }

    public function forceDelete(AuthUser $authUser, FailedJob $failedJob): bool
    {
        return $authUser->can('ForceDelete:FailedJobResource');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FailedJobResource');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FailedJobResource');
    }

    public function replicate(AuthUser $authUser, FailedJob $failedJob): bool
    {
        return $authUser->can('Replicate:FailedJobResource');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FailedJobResource');
    }
}
