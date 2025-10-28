<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Legislatura;
use Illuminate\Auth\Access\HandlesAuthorization;

class LegislaturaPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Legislatura');
    }

    public function view(AuthUser $authUser, Legislatura $legislatura): bool
    {
        return $authUser->can('View:Legislatura');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Legislatura');
    }

    public function update(AuthUser $authUser, Legislatura $legislatura): bool
    {
        return $authUser->can('Update:Legislatura');
    }

    public function delete(AuthUser $authUser, Legislatura $legislatura): bool
    {
        return $authUser->can('Delete:Legislatura');
    }

    public function restore(AuthUser $authUser, Legislatura $legislatura): bool
    {
        return $authUser->can('Restore:Legislatura');
    }

    public function forceDelete(AuthUser $authUser, Legislatura $legislatura): bool
    {
        return $authUser->can('ForceDelete:Legislatura');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Legislatura');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Legislatura');
    }

    public function replicate(AuthUser $authUser, Legislatura $legislatura): bool
    {
        return $authUser->can('Replicate:Legislatura');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Legislatura');
    }

}