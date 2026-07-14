<?php

namespace Tests\Feature\Delivery;

use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_manages_settings_users_and_permission_overrides(): void
    {
        $this->seed(PermissionSeeder::class);
        $superadmin = User::factory()->create(['role' => UserRole::Superadmin]);
        $managedSuperadmin = User::factory()->create(['role' => UserRole::Superadmin]);
        $member = User::factory()->create(['role' => UserRole::Member]);
        $permission = Permission::query()->where('name', 'analytics.view')->firstOrFail();

        $this->actingAs($superadmin)->put('/admin/settings', [
            'site_name' => 'Perpustakaan Pemilu', 'member_registration_enabled' => '1',
            'admin_2fa_required' => '0', 'embed_allowed_domains' => "portal.kpu.go.id\nmedia.kpu.go.id",
        ])->assertRedirect('/admin/settings');
        $this->assertSame('Perpustakaan Pemilu', Setting::valueOf('site_name'));

        $this->actingAs($superadmin)->put("/admin/users/{$member->id}", ['status' => 'inactive'])->assertRedirect('/admin/users');
        $this->assertSame('inactive', $member->fresh()->status->value);

        $this->actingAs($superadmin)->put("/admin/users/{$managedSuperadmin->id}/permissions", ['permissions' => [$permission->id => 'deny']])->assertRedirect();
        $this->assertDatabaseHas('user_permissions', ['user_id' => $managedSuperadmin->id, 'permission_id' => $permission->id, 'allowed' => false]);

        $this->actingAs($superadmin)->put("/admin/users/{$member->id}/permissions", ['permissions' => [$permission->id => 'allow']])->assertForbidden();
        $this->assertDatabaseMissing('user_permissions', ['user_id' => $member->id]);
    }

    public function test_superadmin_can_create_only_member_or_superadmin_accounts(): void
    {
        $this->seed(PermissionSeeder::class);
        $superadmin = User::factory()->create(['role' => UserRole::Superadmin]);

        $this->actingAs($superadmin)->post('/admin/users', [
            'name' => 'Anggota Baru',
            'email' => 'anggota.baru@example.test',
            'password' => 'PasswordAman!2026',
            'role' => UserRole::Member->value,
        ])->assertRedirect('/admin/users');

        $this->actingAs($superadmin)->post('/admin/users', [
            'name' => 'Superadmin Baru',
            'email' => 'superadmin.baru@example.test',
            'password' => 'PasswordAman!2026',
            'role' => UserRole::Superadmin->value,
        ])->assertRedirect('/admin/users');

        $this->actingAs($superadmin)->from('/admin/users')->post('/admin/users', [
            'name' => 'Akun Public Tidak Sah',
            'email' => 'public@example.test',
            'password' => 'PasswordAman!2026',
            'role' => UserRole::Public->value,
        ])->assertRedirect('/admin/users')->assertSessionHasErrors('role');

        $this->assertDatabaseHas('users', ['email' => 'anggota.baru@example.test', 'role' => UserRole::Member->value]);
        $this->assertDatabaseHas('users', ['email' => 'superadmin.baru@example.test', 'role' => UserRole::Superadmin->value]);
        $this->assertDatabaseMissing('users', ['email' => 'public@example.test']);
    }

    public function test_superadmin_denied_admin_management_cannot_update_another_superadmin(): void
    {
        $this->seed(PermissionSeeder::class);
        $actor = User::factory()->create(['role' => UserRole::Superadmin]);
        $target = User::factory()->create(['role' => UserRole::Superadmin]);
        $manageAdmins = Permission::query()->where('name', 'users.manage_admins')->firstOrFail();

        $actor->permissions()->syncWithoutDetaching([
            $manageAdmins->id => ['allowed' => false],
        ]);

        $this->actingAs($actor)
            ->put("/admin/users/{$target->id}", ['status' => 'inactive'])
            ->assertForbidden();

        $this->assertSame('active', $target->fresh()->status->value);
    }
}
