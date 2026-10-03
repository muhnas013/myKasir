<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\Users;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_a_cashier_with_pin(): void
    {
        $owner = User::factory()->owner()->create();

        Livewire::actingAs($owner)
            ->test(Users::class)
            ->call('create')
            ->set('name', 'Kasir Baru')
            ->set('role', 'cashier')
            ->set('pin', '246813')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'Kasir Baru',
            'role' => 'cashier',
        ]);
    }

    public function test_weak_pin_is_rejected(): void
    {
        $owner = User::factory()->owner()->create();

        Livewire::actingAs($owner)
            ->test(Users::class)
            ->call('create')
            ->set('name', 'Kasir Lemah')
            ->set('role', 'cashier')
            ->set('pin', '111111')
            ->call('save')
            ->assertHasErrors('pin');
    }

    public function test_last_active_owner_cannot_deactivate_self(): void
    {
        $owner = User::factory()->owner()->create();

        Livewire::actingAs($owner)
            ->test(Users::class)
            ->call('toggleActive', $owner->id);

        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
            'is_active' => true,
        ]);
    }

    public function test_owner_can_deactivate_a_cashier(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();

        Livewire::actingAs($owner)
            ->test(Users::class)
            ->call('toggleActive', $cashier->id);

        $this->assertDatabaseHas('users', [
            'id' => $cashier->id,
            'is_active' => false,
        ]);
    }
}
