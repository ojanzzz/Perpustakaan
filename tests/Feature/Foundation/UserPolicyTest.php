<?php

namespace Tests\Feature\Foundation;

use App\Enums\AdminLevel;
use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_management_policy_uses_granular_permissions(): void
    {
        $this->seed(PermissionSeeder::class);
        $member = User::factory()->create();
        $editor = User::factory()->create([
            'role' => UserRole::Admin,
            'admin_level' => AdminLevel::Editor,
        ]);
        $superadmin = User::factory()->create([
            'role' => UserRole::Admin,
            'admin_level' => AdminLevel::Superadmin,
        ]);

        $this->assertFalse($editor->can('update', $member));
        $this->assertTrue($superadmin->can('update', $member));
        $this->assertFalse($superadmin->can('delete', $superadmin));
    }
}
