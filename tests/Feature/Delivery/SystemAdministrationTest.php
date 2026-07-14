<?php

namespace Tests\Feature\Delivery;

use App\Enums\AdminLevel;
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
        $superadmin = User::factory()->create(['role' => UserRole::Admin, 'admin_level' => AdminLevel::Superadmin]);
        $member = User::factory()->create(['role' => UserRole::Member, 'admin_level' => null]);
        $permission = Permission::query()->where('name', 'analytics.view')->firstOrFail();

        $this->actingAs($superadmin)->put('/admin/settings', [
            'site_name' => 'Perpustakaan Pemilu', 'member_registration_enabled' => '1',
            'admin_2fa_required' => '0', 'embed_allowed_domains' => "portal.kpu.go.id\nmedia.kpu.go.id",
        ])->assertRedirect('/admin/settings');
        $this->assertSame('Perpustakaan Pemilu', Setting::valueOf('site_name'));

        $this->actingAs($superadmin)->put("/admin/users/{$member->id}", ['status' => 'inactive'])->assertRedirect('/admin/users');
        $this->assertSame('inactive', $member->fresh()->status->value);

        $this->actingAs($superadmin)->put("/admin/users/{$member->id}/permissions", ['permissions' => [$permission->id => 'allow']])->assertRedirect();
        $this->assertDatabaseHas('user_permissions', ['user_id' => $member->id, 'permission_id' => $permission->id, 'allowed' => true]);
    }
}
