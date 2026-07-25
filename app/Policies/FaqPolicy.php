<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Faq;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class FaqPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FaqResource');
    }

    public function view(AuthUser $authUser, Faq $faq): bool
    {
        return $authUser->can('View:FaqResource');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FaqResource');
    }

    public function update(AuthUser $authUser, Faq $faq): bool
    {
        return $authUser->can('Update:FaqResource');
    }

    public function delete(AuthUser $authUser, Faq $faq): bool
    {
        return $authUser->can('Delete:FaqResource');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:FaqResource');
    }

    public function restore(AuthUser $authUser, Faq $faq): bool
    {
        return $authUser->can('Restore:FaqResource');
    }

    public function forceDelete(AuthUser $authUser, Faq $faq): bool
    {
        return $authUser->can('ForceDelete:FaqResource');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FaqResource');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FaqResource');
    }

    public function replicate(AuthUser $authUser, Faq $faq): bool
    {
        return $authUser->can('Replicate:FaqResource');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FaqResource');
    }
}
