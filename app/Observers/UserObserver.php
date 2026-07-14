<?php

namespace App\Observers;

use App\Domain\Audit\AuditRecorder;
use App\Models\User;

class UserObserver
{
    public function __construct(private readonly AuditRecorder $audit) {}

    public function created(User $user): void
    {
        $this->audit->record('users.create', $user, after: $user->getAttributes());
    }

    public function updated(User $user): void
    {
        $this->audit->record('users.update', $user, $user->getOriginal(), $user->getChanges());
    }

    public function deleted(User $user): void
    {
        $this->audit->record('users.delete', $user, $user->getOriginal());
    }

    public function restored(User $user): void
    {
        $this->audit->record('users.restore', $user, after: $user->getAttributes());
    }
}
