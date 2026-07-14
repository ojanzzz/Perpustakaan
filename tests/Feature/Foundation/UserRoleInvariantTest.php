<?php

namespace Tests\Feature\Foundation;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserRoleInvariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_roles_are_exactly_public_member_and_superadmin(): void
    {
        $this->assertSame(
            ['public', 'member', 'superadmin'],
            array_map(fn (UserRole $role): string => $role->value, UserRole::cases()),
        );
    }

    public function test_legacy_admin_level_enum_no_longer_exists(): void
    {
        $this->assertFileDoesNotExist(app_path('Enums/AdminLevel.php'));
    }

    public function test_public_account_cannot_be_created_through_eloquent(): void
    {
        try {
            User::query()->create([
                'name' => 'Public Account',
                'email' => 'public@example.test',
                'password' => 'Password!123',
                'role' => 'public',
                'status' => 'inactive',
            ]);
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Akses public tidak dapat memiliki akun.',
                $exception->errors()['role'][0] ?? null,
            );

            return;
        }

        $this->fail('A public account was persisted through Eloquent.');
    }

    public function test_member_and_superadmin_accounts_can_be_persisted(): void
    {
        $member = User::query()->create([
            'name' => 'Member Account',
            'email' => 'member@example.test',
            'password' => 'Password!123',
            'role' => UserRole::Member,
            'status' => 'active',
        ]);
        $superadmin = User::query()->create([
            'name' => 'Superadmin Account',
            'email' => 'superadmin@example.test',
            'password' => 'Password!123',
            'role' => UserRole::Superadmin,
            'status' => 'active',
        ]);

        $this->assertSame(UserRole::Member, $member->role);
        $this->assertSame(UserRole::Superadmin, $superadmin->role);
    }

    public function test_existing_account_cannot_be_changed_to_public_through_eloquent(): void
    {
        $member = User::query()->create([
            'name' => 'Existing Member',
            'email' => 'existing.member@example.test',
            'password' => 'Password!123',
            'role' => UserRole::Member,
            'status' => 'active',
        ]);

        $member->role = UserRole::Public;

        try {
            $member->save();
            $this->fail('An existing account was changed to public through Eloquent.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Akses public tidak dapat memiliki akun.',
                $exception->errors()['role'][0] ?? null,
            );
        }

        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'role' => 'member',
            'status' => 'active',
        ]);
    }
}
