<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /** @var array<string, string> */
    private array $permissions = [
        'dashboard.view' => 'dashboard',
        'books.view' => 'books', 'books.create' => 'books', 'books.update' => 'books',
        'books.upload' => 'books', 'books.preview' => 'books',
        'books.delete' => 'books', 'books.force_delete' => 'books',
        'books.versions' => 'books', 'taxonomy.manage' => 'catalog',
        'announcements.manage' => 'content', 'feedback.manage' => 'content',
        'analytics.view' => 'analytics', 'analytics.export' => 'analytics',
        'audit.view' => 'audit', 'backup.view' => 'backup', 'backup.run' => 'backup',
        'backup.restore' => 'backup', 'users.manage_members' => 'users',
        'users.manage_admins' => 'users', 'permissions.manage' => 'authorization',
        'settings.manage' => 'settings', 'branding.manage' => 'settings',
        'security.manage' => 'security', 'api.manage' => 'api',
        'embed_domains.manage' => 'embed', 'maintenance.manage' => 'operations',
    ];

    public function run(): void
    {
        foreach ($this->permissions as $name => $group) {
            Permission::query()->updateOrCreate(
                ['name' => $name],
                ['group' => $group, 'description' => $this->description($name)]
            );
        }

        DB::table('role_permissions')->delete();

        foreach (Permission::query()->pluck('id') as $permissionId) {
            DB::table('role_permissions')->insert([
                'role' => UserRole::Superadmin->value,
                'permission_id' => $permissionId,
            ]);
        }
    }

    private function description(string $permission): string
    {
        return ucfirst(str_replace(['.', '_'], [' ', ' '], $permission));
    }
}
