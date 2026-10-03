<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\General;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_tax_rate_and_it_is_audited(): void
    {
        $owner = User::factory()->owner()->create();

        Livewire::actingAs($owner)
            ->test(General::class)
            ->set('outlet_name', 'Warung Demo')
            ->set('tax_enabled', true)
            ->set('tax_rate', 11)
            ->call('save')
            ->assertHasNoErrors();

        $settings = app(SettingService::class);
        $settings->forget();

        $this->assertTrue($settings->get('tax_enabled'));
        $this->assertSame(1100, $settings->get('tax_rate_bp'));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $owner->id,
            'action' => 'setting.updated',
        ]);
    }

    public function test_admin_cannot_access_settings_page(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/settings');

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/settings');

        $response->assertRedirect('/login');
    }
}
