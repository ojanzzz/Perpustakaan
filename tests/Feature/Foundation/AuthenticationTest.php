<?php

namespace Tests\Feature\Foundation;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_available(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk');
    }

    public function test_active_user_can_login_and_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.test',
            'password' => 'password-demo',
            'status' => AccountStatus::Active,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password-demo',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.test',
            'password' => 'password-demo',
            'status' => AccountStatus::Inactive,
        ]);

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password-demo',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
