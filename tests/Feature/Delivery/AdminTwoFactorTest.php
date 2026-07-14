<?php

namespace Tests\Feature\Delivery;

use App\Domain\Security\TotpService;
use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_enabled_superadmin_must_complete_totp_challenge_after_login(): void
    {
        $this->seed(PermissionSeeder::class);
        $secret = app(TotpService::class)->generateSecret();
        $admin = User::factory()->create([
            'role' => UserRole::Superadmin,
            'password' => 'Password!123',
            'two_factor_secret' => $secret,
            'two_factor_enabled_at' => now(),
        ]);

        $this->post('/login', ['email' => $admin->email, 'password' => 'Password!123'])
            ->assertRedirect('/two-factor/challenge');
        $this->get('/admin')->assertRedirect('/two-factor/challenge');
        $this->post('/two-factor/challenge', ['code' => '000000'])->assertSessionHasErrors('code');

        $this->post('/two-factor/challenge', ['code' => app(TotpService::class)->currentCode($secret)])
            ->assertRedirect('/admin');
        $this->get('/admin')->assertOk();
    }

    public function test_superadmin_can_enable_and_disable_two_factor_with_password_confirmation(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Superadmin, 'password' => 'Password!123']);
        $this->actingAs($admin)->get('/two-factor/setup')->assertOk();
        $secret = session('two_factor.pending_secret');
        $this->assertNotEmpty($secret);

        $this->actingAs($admin)->post('/two-factor/setup', ['code' => app(TotpService::class)->currentCode($secret)])
            ->assertRedirect('/admin');
        $this->assertNotNull($admin->fresh()->two_factor_enabled_at);

        $this->actingAs($admin)->delete('/two-factor', ['password' => 'Password!123'])->assertRedirect();
        $this->assertNull($admin->fresh()->two_factor_enabled_at);
    }

    public function test_member_cannot_use_any_two_factor_administration_endpoint(): void
    {
        $member = User::factory()->create(['role' => UserRole::Member, 'password' => 'Password!123']);

        $this->actingAs($member)->get('/two-factor/setup')->assertForbidden();
        $this->actingAs($member)->post('/two-factor/setup', ['code' => '000000'])->assertForbidden();
        $this->actingAs($member)->get('/two-factor/challenge')->assertForbidden();
        $this->actingAs($member)->post('/two-factor/challenge', ['code' => '000000'])->assertForbidden();
        $this->actingAs($member)->delete('/two-factor', ['password' => 'Password!123'])->assertForbidden();
    }
}
