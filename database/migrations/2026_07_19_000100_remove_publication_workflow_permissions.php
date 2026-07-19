<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $permissions = [
        'books.submit',
        'books.review',
        'books.publish',
        'books.schedule',
        'books.archive',
    ];

    public function up(): void
    {
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->whereIn('name', $this->permissions)->delete();
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();

        foreach ($this->permissions as $permission) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $permission,
                'group' => 'books',
                'description' => ucfirst(str_replace('.', ' ', $permission)),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! Schema::hasTable('role_permissions')) {
            return;
        }

        $rows = DB::table('permissions')
            ->whereIn('name', $this->permissions)
            ->pluck('id')
            ->map(fn (int $permissionId): array => [
                'role' => 'superadmin',
                'permission_id' => $permissionId,
            ])
            ->all();

        DB::table('role_permissions')->insertOrIgnore($rows);
    }
};
