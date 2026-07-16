<?php

namespace Tests\Feature\Foundation;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccessLevelUpgradeMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_upgrade_migration_repairs_users_table_from_older_installations(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['role']);
            $table->dropIndex(['status']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['role', 'status']);
        });

        $this->assertFalse(Schema::hasColumn('users', 'role'));
        $this->assertFalse(Schema::hasColumn('users', 'status'));

        DB::table('users')->insert([
            'name' => 'Legacy Member',
            'email' => 'legacy-member@example.test',
            'password' => 'legacy-password',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_07_14_000900_simplify_access_levels.php');
        $migration->up();

        $this->assertTrue(Schema::hasColumn('users', 'role'));
        $this->assertTrue(Schema::hasColumn('users', 'status'));
        $this->assertDatabaseHas('users', [
            'email' => 'legacy-member@example.test',
            'role' => 'member',
            'status' => 'active',
        ]);
    }

    public function test_upgrade_migration_converts_legacy_access_data_and_permissions(): void
    {
        Schema::dropIfExists('role_permissions');

        if (! Schema::hasColumn('users', 'admin_level')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('admin_level', 30)->nullable()->index();
            });
        }

        Schema::dropIfExists('admin_level_permissions');
        Schema::create('admin_level_permissions', function (Blueprint $table): void {
            $table->string('admin_level', 30);
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['admin_level', 'permission_id']);
        });

        $now = now();

        DB::table('users')->insert([
            $this->legacyUser('superadmin@example.test', 'admin', 'superadmin', 'inactive', $now),
            $this->legacyUser('editor@example.test', 'admin', 'editor', 'active', $now),
            $this->legacyUser('content@example.test', 'admin', 'content_admin', 'suspended', $now),
            $this->legacyUser('auditor@example.test', 'admin', 'auditor', 'active', $now),
            $this->legacyUser('visitor@example.test', 'visitor', null, 'active', $now),
            $this->legacyUser('member@example.test', 'member', null, 'suspended', $now),
        ]);

        DB::table('permissions')->insert([
            ['name' => 'books.create', 'group' => 'books', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'audit.view', 'group' => 'governance', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $permissionIds = DB::table('permissions')->orderBy('id')->pluck('id');

        DB::table('admin_level_permissions')->insert([
            ['admin_level' => 'editor', 'permission_id' => $permissionIds[0]],
            ['admin_level' => 'superadmin', 'permission_id' => $permissionIds[1]],
        ]);

        $migration = require database_path('migrations/2026_07_14_000900_simplify_access_levels.php');
        $migration->up();

        $this->assertTrue(Schema::hasTable('role_permissions'));
        $this->assertFalse(Schema::hasTable('admin_level_permissions'));
        $this->assertFalse(Schema::hasColumn('users', 'admin_level'));

        $this->assertLegacyUserBecame('superadmin@example.test', 'superadmin', 'active');
        $this->assertLegacyUserBecame('editor@example.test', 'member', 'inactive');
        $this->assertLegacyUserBecame('content@example.test', 'member', 'inactive');
        $this->assertLegacyUserBecame('auditor@example.test', 'member', 'inactive');
        $this->assertLegacyUserBecame('visitor@example.test', 'public', 'inactive');
        $this->assertLegacyUserBecame('member@example.test', 'member', 'suspended');

        $this->assertEqualsCanonicalizing(
            $permissionIds->all(),
            DB::table('role_permissions')
                ->where('role', 'superadmin')
                ->pluck('permission_id')
                ->all(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function legacyUser(
        string $email,
        string $role,
        ?string $adminLevel,
        string $status,
        mixed $now,
    ): array {
        return [
            'name' => $email,
            'email' => $email,
            'password' => 'legacy-password',
            'role' => $role,
            'admin_level' => $adminLevel,
            'status' => $status,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function assertLegacyUserBecame(string $email, string $role, string $status): void
    {
        $this->assertDatabaseHas('users', [
            'email' => $email,
            'role' => $role,
            'status' => $status,
        ]);
    }
}
