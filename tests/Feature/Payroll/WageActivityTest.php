<?php

namespace Tests\Feature\Payroll;

use App\Livewire\Payroll\ActivityTable;
use App\Livewire\Payroll\WageSummary;
use App\Models\User;
use App\Models\WageActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WageActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_activity(): void
    {
        Livewire::actingAs(User::factory()->owner()->create())
            ->test(ActivityTable::class)
            ->call('create')
            ->set('name', 'Pembuatan Jelly')
            ->set('bonus_amount', 5000)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('wage_activities', [
            'name' => 'Pembuatan Jelly',
            'bonus_amount' => 5000,
            'is_active' => true,
        ]);
    }

    public function test_bonus_amount_is_validated(): void
    {
        Livewire::actingAs(User::factory()->owner()->create())
            ->test(ActivityTable::class)
            ->call('create')
            ->set('name', 'Jelly')
            ->set('bonus_amount', -1)
            ->call('save')
            ->assertHasErrors('bonus_amount');
    }

    public function test_owner_can_edit_activity(): void
    {
        $activity = WageActivity::factory()->create(['name' => 'Jelly', 'bonus_amount' => 5000]);

        Livewire::actingAs(User::factory()->owner()->create())
            ->test(ActivityTable::class)
            ->call('edit', $activity->id)
            ->set('bonus_amount', 7000)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(7000, $activity->fresh()->bonus_amount);
    }

    public function test_owner_can_toggle_active_status(): void
    {
        $activity = WageActivity::factory()->create(['is_active' => true]);

        Livewire::actingAs(User::factory()->owner()->create())
            ->test(ActivityTable::class)
            ->call('toggleActive', $activity->id);

        $this->assertFalse($activity->fresh()->is_active);
    }

    public function test_owner_can_delete_activity(): void
    {
        $activity = WageActivity::factory()->create();

        Livewire::actingAs(User::factory()->owner()->create())
            ->test(ActivityTable::class)
            ->call('delete', $activity->id);

        $this->assertDatabaseMissing('wage_activities', ['id' => $activity->id]);
    }

    public function test_admin_cannot_access_activity_component(): void
    {
        Livewire::actingAs(User::factory()->admin()->create())
            ->test(ActivityTable::class)
            ->assertForbidden();
    }

    public function test_cashier_cannot_access_activity_component(): void
    {
        Livewire::actingAs(User::factory()->cashier()->create())
            ->test(ActivityTable::class)
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_payroll_page(): void
    {
        $this->get('/payroll')->assertRedirect('/login');
    }

    public function test_admin_cannot_access_payroll_page(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/payroll')
            ->assertForbidden();
    }

    public function test_empty_state_is_shown_without_activities(): void
    {
        Livewire::actingAs(User::factory()->owner()->create())
            ->test(ActivityTable::class)
            ->assertSee('Belum ada aktivitas bonus. Tambah yang pertama.');
    }

    public function test_admin_cannot_access_wage_summary_component(): void
    {
        Livewire::actingAs(User::factory()->admin()->create())
            ->test(WageSummary::class)
            ->assertForbidden();
    }

    public function test_wage_summary_requires_period_before_showing_rows(): void
    {
        Livewire::actingAs(User::factory()->owner()->create())
            ->test(WageSummary::class)
            ->assertSee('Pilih periode dulu untuk melihat Rekap Upah.');
    }

    public function test_wage_summary_shows_empty_state_without_closed_shifts(): void
    {
        Livewire::actingAs(User::factory()->owner()->create())
            ->test(WageSummary::class)
            ->set('period', 'today')
            ->assertSee('Belum ada shift selesai pada periode ini.');
    }
}
