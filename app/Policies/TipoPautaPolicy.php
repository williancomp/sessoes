<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\TipoPauta;
use Illuminate\Auth\Access\HandlesAuthorization;

class TipoPautaPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TipoPauta');
    }

    public function view(AuthUser $authUser, TipoPauta $tipoPauta): bool
    {
        return $authUser->can('View:TipoPauta');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TipoPauta');
    }

    public function update(AuthUser $authUser, TipoPauta $tipoPauta): bool
    {
        return $authUser->can('Update:TipoPauta');
    }

    public function delete(AuthUser $authUser, TipoPauta $tipoPauta): bool
    {
        return $authUser->can('Delete:TipoPauta');
    }

    public function restore(AuthUser $authUser, TipoPauta $tipoPauta): bool
    {
        return $authUser->can('Restore:TipoPauta');
    }

    public function forceDelete(AuthUser $authUser, TipoPauta $tipoPauta): bool
    {
        return $authUser->can('ForceDelete:TipoPauta');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TipoPauta');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TipoPauta');
    }

    public function replicate(AuthUser $authUser, TipoPauta $tipoPauta): bool
    {
        return $authUser->can('Replicate:TipoPauta');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TipoPauta');
    }

}