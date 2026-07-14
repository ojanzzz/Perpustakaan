<?php

namespace App\Domain\Authorization;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PermissionService
{
    public function allows(?User $user, string $permission): bool
    {
        if ($user === null || $user->status !== AccountStatus::Active) {
            return false;
        }

        $permissionId = DB::table('permissions')->where('name', $permission)->value('id');

        if ($permissionId === null) {
            return false;
        }

        $override = DB::table('user_permissions')
            ->where('user_id', $user->getKey())
            ->where('permission_id', $permissionId)
            ->first();

        if ($override !== null) {
            return (bool) $override->allowed;
        }

        if ($user->role !== UserRole::Admin || $user->admin_level === null) {
            return false;
        }

        return DB::table('admin_level_permissions')
            ->where('admin_level', $user->admin_level->value)
            ->where('permission_id', $permissionId)
            ->exists();
    }
}
