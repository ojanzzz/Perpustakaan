<?php

namespace Tests\Feature\Foundation;

use App\Domain\Authorization\PermissionService;
use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_permissions_are_resolved_for_superadmin(): void
    {
        $permission = Permission::factory()->create(['name' => 'books.create']);
        $superadmin = User::factory()->create(['role' => UserRole::Superadmin]);
        DB::table('role_permissions')->insert([
            'role' => UserRole::Superadmin->value,
            'permission_id' => $permission->id,
        ]);

        $this->assertTrue(app(PermissionService::class)->allows($superadmin, 'books.create'));
    }

    public function test_superadmin_override_can_deny_or_grant_a_permission(): void
    {
        $create = Permission::factory()->create(['name' => 'books.create']);
        $update = Permission::factory()->create(['name' => 'books.update']);
        $superadmin = User::factory()->create(['role' => UserRole::Superadmin]);
        DB::table('role_permissions')->insert([
            'role' => UserRole::Superadmin->value,
            'permission_id' => $create->id,
        ]);
        $superadmin->permissions()->attach($create, ['allowed' => false]);
        $superadmin->permissions()->attach($update, ['allowed' => true]);

        $service = app(PermissionService::class);

        $this->assertFalse($service->allows($superadmin, 'books.create'));
        $this->assertTrue($service->allows($superadmin, 'books.update'));
    }

    public function test_member_override_cannot_escalate_to_administrative_access(): void
    {
        $permission = Permission::factory()->create(['name' => 'dashboard.view']);
        $member = User::factory()->create(['role' => UserRole::Member]);
        $member->permissions()->attach($permission, ['allowed' => true]);

        $this->assertFalse(app(PermissionService::class)->allows($member, 'dashboard.view'));
        $this->actingAs($member)->get('/admin')->assertForbidden();
    }

    public function test_permission_middleware_protects_admin_routes(): void
    {
        $permission = Permission::factory()->create(['name' => 'dashboard.view']);
        $superadmin = User::factory()->create(['role' => UserRole::Superadmin]);

        $this->actingAs($superadmin)->get('/admin')->assertForbidden();

        DB::table('role_permissions')->insert([
            'role' => UserRole::Superadmin->value,
            'permission_id' => $permission->id,
        ]);

        $this->actingAs($superadmin->fresh())->get('/admin')->assertOk();
    }
}
