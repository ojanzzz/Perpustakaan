<?php

namespace Tests\Feature\Foundation;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class MemberAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_is_hidden_when_disabled(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_registration_creates_a_member_when_enabled(): void
    {
        $this->enableRegistration();

        $response = $this->post('/register', [
            'name' => 'Anggota Demo',
            'email' => 'anggota@example.test',
            'password' => 'Password!2026',
            'password_confirmation' => 'Password!2026',
        ]);

        $response->assertRedirect('/');
        $user = User::query()->where('email', 'anggota@example.test')->firstOrFail();
        $this->assertSame(UserRole::Member, $user->role);
        $this->assertNull($user->admin_level);
        $this->assertAuthenticatedAs($user);
    }

    public function test_password_reset_link_can_be_requested(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email]);

        $token = null;
        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $notification) use (&$token): bool {
                $token = $notification->token;

                return true;
            }
        );

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword!2026',
            'password_confirmation' => 'NewPassword!2026',
        ])->assertRedirect('/login');

        $this->assertTrue(Hash::check('NewPassword!2026', $user->fresh()->password));
    }

    public function test_member_can_verify_email_through_a_signed_link(): void
    {
        Event::fake([Verified::class]);
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->actingAs($user)->get($url)->assertRedirect('/');

        $this->assertNotNull($user->fresh()->email_verified_at);
        Event::assertDispatched(Verified::class);
    }

    private function enableRegistration(): void
    {
        $this->app['db']->table('settings')->insert([
            'group' => 'auth',
            'key' => 'member_registration_enabled',
            'value' => 'true',
            'type' => 'boolean',
            'is_public' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
