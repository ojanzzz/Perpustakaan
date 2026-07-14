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

        // Permission dashboard hanya berlaku untuk akun superadmin. Pemeriksaan
        // role harus mendahului override agar user_permissions tidak dapat
        // menaikkan hak akses akun member.
        if ($user->role !== UserRole::Superadmin) {
            return false;
        }

        $override = DB::table('user_permissions')
            ->where('user_id', $user->getKey())
            ->where('permission_id', $permissionId)
            ->first();

        if ($override !== null) {
            return (bool) $override->allowed;
        }

        return DB::table('role_permissions')
            ->where('role', UserRole::Superadmin->value)
            ->where('permission_id', $permissionId)
            ->exists();
    }
}
