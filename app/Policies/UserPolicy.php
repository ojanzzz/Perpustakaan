<?php

namespace App\Policies;

use App\Domain\Authorization\PermissionService;
use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function __construct(private readonly PermissionService $permissions) {}

    public function viewAny(User $actor): bool
    {
        return $this->permissions->allows($actor, 'users.manage_members')
            || $this->permissions->allows($actor, 'users.manage_admins');
    }

    public function view(User $actor, User $target): bool
    {
        return $this->canManage($actor, $target);
    }

    public function update(User $actor, User $target): bool
    {
        return $this->canManage($actor, $target);
    }

    public function delete(User $actor, User $target): bool
    {
        return ! $actor->is($target) && $this->canManage($actor, $target);
    }

    public function restore(User $actor, User $target): bool
    {
        return $this->canManage($actor, $target);
    }

    public function forceDelete(User $actor, User $target): bool
    {
        return ! $actor->is($target)
            && $this->permissions->allows($actor, 'users.manage_admins');
    }

    private function canManage(User $actor, User $target): bool
    {
        $permission = $target->role === UserRole::Superadmin
            ? 'users.manage_admins'
            : 'users.manage_members';

        return $this->permissions->allows($actor, $permission);
    }
}
