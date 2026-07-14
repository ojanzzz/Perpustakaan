<?php

namespace App\Policies;

use App\Domain\Authorization\PermissionService;
use App\Models\Collection;
use App\Models\User;

class CollectionPolicy
{
    public function __construct(private readonly PermissionService $permissions) {}

    public function viewAny(User $user): bool
    {
        return $this->permissions->allows($user, 'books.view');
    }

    public function create(User $user): bool
    {
        return $this->permissions->allows($user, 'taxonomy.manage');
    }

    public function update(User $user, Collection $collection): bool
    {
        return $this->permissions->allows($user, 'taxonomy.manage');
    }

    public function delete(User $user, Collection $collection): bool
    {
        return $this->permissions->allows($user, 'taxonomy.manage');
    }
}
