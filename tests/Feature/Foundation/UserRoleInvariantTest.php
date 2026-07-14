<?php

namespace Tests\Feature\Foundation;

use App\Enums\AdminLevel;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserRoleInvariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_requires_an_admin_level(): void
    {
        $this->expectException(ValidationException::class);

        User::factory()->create([
            'role' => UserRole::Admin,
            'admin_level' => null,
        ]);
    }

    public function test_non_admin_must_not_have_an_admin_level(): void
    {
        $this->expectException(ValidationException::class);

        User::factory()->create([
            'role' => UserRole::Member,
            'admin_level' => AdminLevel::Editor,
        ]);
    }

    public function test_valid_admin_and_member_are_persisted(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'admin_level' => AdminLevel::Editor,
        ]);
        $member = User::factory()->create([
            'role' => UserRole::Member,
            'admin_level' => null,
        ]);

        $this->assertSame(AdminLevel::Editor, $admin->admin_level);
        $this->assertSame(UserRole::Member, $member->role);
    }
}
