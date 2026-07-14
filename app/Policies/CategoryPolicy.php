<?php

namespace App\Policies;

use App\Domain\Authorization\PermissionService;
use App\Models\Category;
use App\Models\User;

class CategoryPolicy
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

    public function update(User $user, Category $category): bool
    {
        return $this->permissions->allows($user, 'taxonomy.manage');
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->permissions->allows($user, 'taxonomy.manage');
    }
}
