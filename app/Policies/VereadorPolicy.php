<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Vereador;
use Illuminate\Auth\Access\HandlesAuthorization;

class VereadorPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Vereador');
    }

    public function view(AuthUser $authUser, Vereador $vereador): bool
    {
        return $authUser->can('View:Vereador');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Vereador');
    }

    public function update(AuthUser $authUser, Vereador $vereador): bool
    {
        return $authUser->can('Update:Vereador');
    }

    public function delete(AuthUser $authUser, Vereador $vereador): bool
    {
        return $authUser->can('Delete:Vereador');
    }

    public function restore(AuthUser $authUser, Vereador $vereador): bool
    {
        return $authUser->can('Restore:Vereador');
    }

    public function forceDelete(AuthUser $authUser, Vereador $vereador): bool
    {
        return $authUser->can('ForceDelete:Vereador');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Vereador');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Vereador');
    }

    public function replicate(AuthUser $authUser, Vereador $vereador): bool
    {
        return $authUser->can('Replicate:Vereador');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Vereador');
    }

}