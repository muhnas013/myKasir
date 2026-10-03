<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PinLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_login_with_correct_pin(): void
    {
        $cashier = User::factory()->cashier()->create(['pin_hash' => bcrypt('123456')]);

        $response = $this->post('/login/pin', [
            'user_id' => $cashier->id,
            'pin' => '123456',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($cashier);
    }

    public function test_wrong_pin_is_rejected(): void
    {
        $cashier = User::factory()->cashier()->create(['pin_hash' => bcrypt('123456')]);

        $response = $this->post('/login/pin', [
            'user_id' => $cashier->id,
            'pin' => '999999',
        ]);

        $response->assertSessionHasErrors('pin');
        $this->assertGuest();
    }

    public function test_five_wrong_pin_attempts_locks_and_logs_audit(): void
    {
        $cashier = User::factory()->cashier()->create(['pin_hash' => bcrypt('123456')]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login/pin', [
                'user_id' => $cashier->id,
                'pin' => '999999',
            ]);
        }

        $response = $this->post('/login/pin', [
            'user_id' => $cashier->id,
            'pin' => '123456',
        ]);

        $response->assertSessionHasErrors('pin');
        $this->assertGuest();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $cashier->id,
            'action' => 'auth.pin_locked',
        ]);
    }

    public function test_inactive_user_cannot_login_with_pin(): void
    {
        $cashier = User::factory()->cashier()->inactive()->create(['pin_hash' => bcrypt('123456')]);

        $response = $this->post('/login/pin', [
            'user_id' => $cashier->id,
            'pin' => '123456',
        ]);

        $response->assertSessionHasErrors('pin');
        $this->assertGuest();
    }
}
