<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_login_with_email_and_password(): void
    {
        $owner = User::factory()->owner()->create(['email' => 'owner@test.local']);

        $response = $this->post('/login', [
            'email' => 'owner@test.local',
            'password' => 'password',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($owner);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->owner()->create(['email' => 'owner@test.local']);

        $response = $this->post('/login', [
            'email' => 'owner@test.local',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_owner_cannot_login(): void
    {
        User::factory()->owner()->inactive()->create(['email' => 'owner@test.local']);

        $response = $this->post('/login', [
            'email' => 'owner@test.local',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_cashier_without_settings_access_gets_403(): void
    {
        $cashier = User::factory()->cashier()->create();

        $response = $this->actingAs($cashier)->get('/settings');

        $response->assertForbidden();
    }
}
