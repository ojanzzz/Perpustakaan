<?php

namespace Tests\Feature\Foundation;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_superadmin_can_manage_users(): void
    {
        $this->seed(PermissionSeeder::class);
        $member = User::factory()->create();
        $otherMember = User::factory()->create();
        $superadmin = User::factory()->create(['role' => UserRole::Superadmin]);

        $this->assertFalse($member->can('update', $otherMember));
        $this->assertTrue($superadmin->can('update', $member));
        $this->assertFalse($superadmin->can('delete', $superadmin));
    }
}
