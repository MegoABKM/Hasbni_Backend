<?php

declare(strict_types=1);

namespace App\Saas\Policies;

use App\Saas\Models\PromoCode;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PromoCodePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PromoCodeResource');
    }

    public function view(AuthUser $authUser, PromoCode $promoCode): bool
    {
        return $authUser->can('View:PromoCodeResource');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PromoCodeResource');
    }

    public function update(AuthUser $authUser, PromoCode $promoCode): bool
    {
        return $authUser->can('Update:PromoCodeResource');
    }

    public function delete(AuthUser $authUser, PromoCode $promoCode): bool
    {
        return $authUser->can('Delete:PromoCodeResource');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PromoCodeResource');
    }

    public function restore(AuthUser $authUser, PromoCode $promoCode): bool
    {
        return $authUser->can('Restore:PromoCodeResource');
    }

    public function forceDelete(AuthUser $authUser, PromoCode $promoCode): bool
    {
        return $authUser->can('ForceDelete:PromoCodeResource');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PromoCodeResource');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PromoCodeResource');
    }

    public function replicate(AuthUser $authUser, PromoCode $promoCode): bool
    {
        return $authUser->can('Replicate:PromoCodeResource');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PromoCodeResource');
    }
}
