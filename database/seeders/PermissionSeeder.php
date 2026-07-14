<?php

namespace Database\Seeders;

use App\Enums\AdminLevel;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /** @var array<string, string> */
    private array $permissions = [
        'dashboard.view' => 'dashboard',
        'books.view' => 'books', 'books.create' => 'books', 'books.update' => 'books',
        'books.upload' => 'books', 'books.preview' => 'books', 'books.submit' => 'books',
        'books.review' => 'books', 'books.publish' => 'books', 'books.schedule' => 'books',
        'books.archive' => 'books', 'books.delete' => 'books', 'books.force_delete' => 'books',
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

        $editor = [
            'dashboard.view', 'books.view', 'books.create', 'books.update',
            'books.upload', 'books.preview', 'books.submit',
        ];
        $contentAdmin = array_merge($editor, [
            'books.review', 'books.publish', 'books.schedule', 'books.archive',
            'books.delete', 'books.versions', 'taxonomy.manage',
            'announcements.manage', 'feedback.manage', 'analytics.view', 'analytics.export',
        ]);
        $auditor = [
            'dashboard.view', 'books.view', 'analytics.view', 'analytics.export',
            'audit.view', 'backup.view',
        ];
        $superadmin = array_keys($this->permissions);

        $map = [
            AdminLevel::Editor->value => $editor,
            AdminLevel::ContentAdmin->value => $contentAdmin,
            AdminLevel::Auditor->value => $auditor,
            AdminLevel::Superadmin->value => $superadmin,
        ];

        foreach ($map as $level => $permissionNames) {
            $ids = Permission::query()->whereIn('name', $permissionNames)->pluck('id');
            foreach ($ids as $id) {
                DB::table('admin_level_permissions')->insertOrIgnore([
                    'admin_level' => $level,
                    'permission_id' => $id,
                ]);
            }
        }
    }

    private function description(string $permission): string
    {
        return ucfirst(str_replace(['.', '_'], [' ', ' '], $permission));
    }
}
