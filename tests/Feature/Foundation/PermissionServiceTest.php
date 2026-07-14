<?php

namespace Tests\Feature\Foundation;

use App\Domain\Authorization\PermissionService;
use App\Enums\AdminLevel;
use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_level_permissions_are_resolved_without_hard_coded_level_checks(): void
    {
        $permission = Permission::factory()->create(['name' => 'books.create']);
        $editor = User::factory()->create([
            'role' => UserRole::Admin,
            'admin_level' => AdminLevel::Editor,
        ]);
        DB::table('admin_level_permissions')->insert([
            'admin_level' => AdminLevel::Editor->value,
            'permission_id' => $permission->id,
        ]);

        $this->assertTrue(app(PermissionService::class)->allows($editor, 'books.create'));
    }

    public function test_user_override_can_deny_or_grant_a_permission(): void
    {
        $create = Permission::factory()->create(['name' => 'books.create']);
        $publish = Permission::factory()->create(['name' => 'books.publish']);
        $editor = User::factory()->create([
            'role' => UserRole::Admin,
            'admin_level' => AdminLevel::Editor,
        ]);
        DB::table('admin_level_permissions')->insert([
            'admin_level' => AdminLevel::Editor->value,
            'permission_id' => $create->id,
        ]);
        $editor->permissions()->attach($create, ['allowed' => false]);
        $editor->permissions()->attach($publish, ['allowed' => true]);

        $service = app(PermissionService::class);

        $this->assertFalse($service->allows($editor, 'books.create'));
        $this->assertTrue($service->allows($editor, 'books.publish'));
    }

    public function test_permission_middleware_protects_admin_routes(): void
    {
        $permission = Permission::factory()->create(['name' => 'dashboard.view']);
        $editor = User::factory()->create([
            'role' => UserRole::Admin,
            'admin_level' => AdminLevel::Editor,
        ]);

        $this->actingAs($editor)->get('/admin')->assertForbidden();

        DB::table('admin_level_permissions')->insert([
            'admin_level' => AdminLevel::Editor->value,
            'permission_id' => $permission->id,
        ]);

        $this->actingAs($editor->fresh())->get('/admin')->assertOk();
    }
}
