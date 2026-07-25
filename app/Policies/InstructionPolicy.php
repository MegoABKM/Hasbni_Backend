<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Instruction;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class InstructionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:InstructionResource');
    }

    public function view(AuthUser $authUser, Instruction $instruction): bool
    {
        return $authUser->can('View:InstructionResource');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:InstructionResource');
    }

    public function update(AuthUser $authUser, Instruction $instruction): bool
    {
        return $authUser->can('Update:InstructionResource');
    }

    public function delete(AuthUser $authUser, Instruction $instruction): bool
    {
        return $authUser->can('Delete:InstructionResource');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:InstructionResource');
    }

    public function restore(AuthUser $authUser, Instruction $instruction): bool
    {
        return $authUser->can('Restore:InstructionResource');
    }

    public function forceDelete(AuthUser $authUser, Instruction $instruction): bool
    {
        return $authUser->can('ForceDelete:InstructionResource');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:InstructionResource');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:InstructionResource');
    }

    public function replicate(AuthUser $authUser, Instruction $instruction): bool
    {
        return $authUser->can('Replicate:InstructionResource');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:InstructionResource');
    }
}
