<?php

namespace Tests\Feature\Menu;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_and_admin_can_open_menu_page(): void
    {
        $this->actingAs(User::factory()->owner()->create())->get('/menu')->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get('/menu')->assertOk();
    }

    public function test_cashier_gets_403_on_menu_page(): void
    {
        $this->actingAs(User::factory()->cashier()->create())->get('/menu')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/menu')->assertRedirect('/login');
    }
}
