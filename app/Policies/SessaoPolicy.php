<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Sessao;
use Illuminate\Auth\Access\HandlesAuthorization;

class SessaoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Sessao');
    }

    public function view(AuthUser $authUser, Sessao $sessao): bool
    {
        return $authUser->can('View:Sessao');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Sessao');
    }

    public function update(AuthUser $authUser, Sessao $sessao): bool
    {
        return $authUser->can('Update:Sessao');
    }

    public function delete(AuthUser $authUser, Sessao $sessao): bool
    {
        return $authUser->can('Delete:Sessao');
    }

    public function restore(AuthUser $authUser, Sessao $sessao): bool
    {
        return $authUser->can('Restore:Sessao');
    }

    public function forceDelete(AuthUser $authUser, Sessao $sessao): bool
    {
        return $authUser->can('ForceDelete:Sessao');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Sessao');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Sessao');
    }

    public function replicate(AuthUser $authUser, Sessao $sessao): bool
    {
        return $authUser->can('Replicate:Sessao');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Sessao');
    }

}