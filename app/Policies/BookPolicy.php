<?php

namespace App\Policies;

use App\Domain\Authorization\PermissionService;
use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    public function __construct(private readonly PermissionService $permissions) {}

    public function viewAny(User $user): bool
    {
        return $this->permissions->allows($user, 'books.view');
    }

    public function view(User $user, Book $book): bool
    {
        return $this->permissions->allows($user, 'books.view');
    }

    public function create(User $user): bool
    {
        return $this->permissions->allows($user, 'books.create');
    }

    public function update(User $user, Book $book): bool
    {
        return $this->permissions->allows($user, 'books.update');
    }

    public function delete(User $user, Book $book): bool
    {
        return $this->permissions->allows($user, 'books.delete');
    }

    public function forceDelete(User $user, Book $book): bool
    {
        return $this->permissions->allows($user, 'books.force_delete');
    }
}
