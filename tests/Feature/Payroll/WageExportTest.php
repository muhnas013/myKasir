<?php

namespace Tests\Feature\Payroll;

use App\Enums\OrderStatus;
use App\Enums\ShiftStatus;
use App\Exports\WageSummaryExport;
use App\Models\Order;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class WageExportTest extends TestCase
{
    use RefreshDatabase;

    private function closedShift(User $user, string $openedAt): Shift
    {
        return Shift::create([
            'user_id' => $user->id,
            'status' => ShiftStatus::Closed,
            'opened_at' => $openedAt,
            'closed_at' => $openedAt,
            'opening_cash' => 0,
            'expected_cash' => 0,
            'counted_cash' => 0,
            'cash_difference' => 0,
        ]);
    }

    private function paidOrder(Shift $shift, int $total): Order
    {
        return Order::create([
            'number' => 'T-'.Str::random(6),
            'business_date' => $shift->opened_at->toDateString(),
            'shift_id' => $shift->id,
            'user_id' => $shift->user_id,
            'status' => OrderStatus::Paid,
            'order_type' => 'take_away',
            'subtotal' => $total,
            'total' => $total,
            'idempotency_key' => (string) Str::uuid(),
            'paid_at' => $shift->opened_at,
        ]);
    }

    public function test_owner_bisa_unduh_excel_rekap_upah(): void
    {
        Excel::fake();

        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create(['name' => 'Kasir A']);
        $shift = $this->closedShift($cashier, '2026-10-01 08:00:00');
        $this->paidOrder($shift, 100000);

        $today = '2026-10-01';
        $filename = "penggajian-{$today}-sd-{$today}.xlsx";

        $this->actingAs($owner)->get("/payroll/export?start={$today}&end={$today}")->assertOk();

        Excel::assertDownloaded($filename, function (WageSummaryExport $export) {
            $this->assertSame(
                ['No', 'Pegawai', 'Omzet', 'Upah Dasar', 'Bonus Aktivitas', 'Bonus Penjualan', 'Total Upah'],
                $export->headings()
            );
            $this->assertCount(1, $export->collection());

            return true;
        });
    }

    public function test_beberapa_shift_karyawan_yang_sama_digabung_jadi_satu_baris(): void
    {
        Excel::fake();

        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create(['name' => 'Aulia']);
        $shiftA = $this->closedShift($cashier, '2026-10-01 08:00:00');
        $shiftB = $this->closedShift($cashier, '2026-10-02 08:00:00');
        $this->paidOrder($shiftA, 10000);
        $this->paidOrder($shiftB, 10000);

        $this->actingAs($owner)->get('/payroll/export?start=2026-10-01&end=2026-10-02')->assertOk();

        Excel::assertDownloaded('penggajian-2026-10-01-sd-2026-10-02.xlsx', function (WageSummaryExport $export) {
            $rows = $export->collection()->values();

            $this->assertCount(1, $rows);
            $this->assertSame('Aulia', $rows[0]['name']);
            $this->assertSame(20000, $rows[0]['omzet']);
            $this->assertSame(70000, $rows[0]['base']); // 2 shift x 35000
            $this->assertSame(70000, $rows[0]['total']);

            return true;
        });
    }

    public function test_admin_tidak_bisa_mengakses_export_penggajian(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/payroll/export?start=2026-10-01&end=2026-10-01')->assertForbidden();
    }

    public function test_kasir_tidak_bisa_mengakses_export_penggajian(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)->get('/payroll/export?start=2026-10-01&end=2026-10-01')->assertForbidden();
    }

    public function test_guest_diarahkan_ke_login(): void
    {
        $this->get('/payroll/export?start=2026-10-01&end=2026-10-01')->assertRedirect('/login');
    }
}
