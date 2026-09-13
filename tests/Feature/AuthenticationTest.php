<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_is_available(): void
    {
        $this->get('/login')->assertOk()->assertSee('EDUTECH');
    }

    public function test_active_user_can_authenticate(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);
        $user = User::factory()->create(['role' => UserRole::Superadmin, 'active' => true, 'password' => 'secret123']);
        $this->post('/login', ['email' => $user->email, 'password' => 'secret123'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_guests_are_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
