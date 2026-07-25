<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Announcement;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AnnouncementPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AnnouncementResource');
    }

    public function view(AuthUser $authUser, Announcement $announcement): bool
    {
        return $authUser->can('View:AnnouncementResource');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AnnouncementResource');
    }

    public function update(AuthUser $authUser, Announcement $announcement): bool
    {
        return $authUser->can('Update:AnnouncementResource');
    }

    public function delete(AuthUser $authUser, Announcement $announcement): bool
    {
        return $authUser->can('Delete:AnnouncementResource');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AnnouncementResource');
    }

    public function restore(AuthUser $authUser, Announcement $announcement): bool
    {
        return $authUser->can('Restore:AnnouncementResource');
    }

    public function forceDelete(AuthUser $authUser, Announcement $announcement): bool
    {
        return $authUser->can('ForceDelete:AnnouncementResource');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AnnouncementResource');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AnnouncementResource');
    }

    public function replicate(AuthUser $authUser, Announcement $announcement): bool
    {
        return $authUser->can('Replicate:AnnouncementResource');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AnnouncementResource');
    }
}
