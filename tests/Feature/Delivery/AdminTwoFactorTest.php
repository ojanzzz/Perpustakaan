<?php

namespace Tests\Feature\Delivery;

use App\Domain\Security\TotpService;
use App\Enums\AdminLevel;
use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_enabled_admin_must_complete_totp_challenge_after_login(): void
    {
        $this->seed(PermissionSeeder::class);
        $secret = app(TotpService::class)->generateSecret();
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'admin_level' => AdminLevel::Superadmin,
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

    public function test_admin_can_enable_and_disable_two_factor_with_password_confirmation(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'admin_level' => AdminLevel::Editor, 'password' => 'Password!123']);
        $this->actingAs($admin)->get('/two-factor/setup')->assertOk();
        $secret = session('two_factor.pending_secret');
        $this->assertNotEmpty($secret);

        $this->actingAs($admin)->post('/two-factor/setup', ['code' => app(TotpService::class)->currentCode($secret)])
            ->assertRedirect('/admin');
        $this->assertNotNull($admin->fresh()->two_factor_enabled_at);

        $this->actingAs($admin)->delete('/two-factor', ['password' => 'Password!123'])->assertRedirect();
        $this->assertNull($admin->fresh()->two_factor_enabled_at);
    }
}
