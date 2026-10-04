<?php

namespace Tests\Feature\Reports;

use App\Actions\Orders\CompleteOrder;
use App\Actions\Orders\VoidOrder;
use App\Actions\Shifts\OpenShift;
use App\Exports\OrdersExport;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Models\User;
use App\Services\ReportService;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Concerns\SetsUpPos;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase, SetsUpPos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPos(['tax_enabled' => false]);
    }

    private function complete(array $override = [])
    {
        return app(CompleteOrder::class)->handle($this->cashier, $this->shift, $this->cashInput($override));
    }

    public function test_keuntungan_bersih_mengurangi_hpp_dari_omzet(): void
    {
        $this->esKopi->update(['cost_price' => 8000]);
        $this->nasiGoreng->update(['cost_price' => 10000]);
        $this->pisangGoreng->update(['cost_price' => 5000]);

        $this->complete(); // 2x esKopi + 1x nasiGoreng + 1x pisangGoreng = 73.000, hpp = 2*8000+10000+5000 = 31.000

        $today = now()->toDateString();
        $summary = app(ReportService::class)->summary($today, $today);

        $this->assertSame(73000, $summary['omzet']);
        $this->assertSame(31000, $summary['hpp']);
        $this->assertSame(42000, $summary['keuntungan_bersih']);
    }

    public function test_omzet_hari_ini_menjumlahkan_order_lunas_dan_mengabaikan_void(): void
    {
        $this->complete();
        $this->complete();
        $voided = $this->complete();
        app(VoidOrder::class)->handle($voided, User::factory()->owner()->create(), 'Dibatalkan untuk pengujian laporan');

        $today = now()->toDateString();
        $summary = app(ReportService::class)->summary($today, $today);

        $this->assertSame(146000, $summary['omzet']);
        $this->assertSame(2, $summary['transaksi']);
        $this->assertSame(73000, $summary['rata_rata']);

        $history = app(ReportService::class)->history($today, $today);
        $this->assertCount(3, $history, 'Riwayat mencakup paid + void.');
    }

    public function test_grafik_per_jam_memakai_jam_asia_makassar(): void
    {
        $order = $this->complete();
        $order->forceFill([
            'paid_at' => now()->startOfDay()->setTime(23, 50),
            'business_date' => now()->toDateString(),
        ])->save();

        $today = now()->toDateString();
        $hourly = app(ReportService::class)->hourly($today, $today);

        $this->assertSame(73000, $hourly[23]);
    }

    public function test_export_excel_berisi_kolom_dan_jumlah_baris_sesuai_periode(): void
    {
        Excel::fake();

        $this->complete();
        $this->complete();
        $voided = $this->complete();
        $owner = User::factory()->owner()->create();
        app(VoidOrder::class)->handle($voided, $owner, 'Dibatalkan untuk pengujian laporan');

        $today = now()->toDateString();
        $filename = "laporan-{$today}-sd-{$today}.xlsx";

        $this->actingAs($owner)->get("/reports/export?start={$today}&end={$today}")->assertOk();

        Excel::assertDownloaded($filename, function (OrdersExport $export) {
            $this->assertSame(
                ['No', 'Waktu', 'Kasir', 'Item', 'Metode', 'Subtotal', 'Diskon', 'Layanan', 'PB1', 'Total', 'Status'],
                $export->headings()
            );
            $this->assertCount(3, $export->collection());

            return true;
        });
    }

    public function test_kasir_hanya_melihat_data_sendiri_dan_tanpa_tombol_unduh(): void
    {
        $otherCashier = User::factory()->cashier()->create();
        $otherShift = app(OpenShift::class)->handle($otherCashier, 200000);
        app(CompleteOrder::class)->handle($otherCashier, $otherShift, $this->cashInput([
            'lines' => [['product_id' => $this->esKopi->id, 'qty' => 5]],
        ]));

        $this->complete();

        Livewire::actingAs($this->cashier)
            ->test(ReportsIndex::class)
            ->assertSee(Money::format(73000))
            ->assertDontSee(Money::format(90000))
            ->assertDontSee('Unduh Excel');
    }

    public function test_cashier_does_not_see_profit_figures(): void
    {
        $this->complete();

        Livewire::actingAs($this->cashier)
            ->test(ReportsIndex::class)
            ->assertDontSee('Keuntungan Bersih')
            ->assertDontSee('Total HPP');
    }

    public function test_owner_sees_profit_figures(): void
    {
        $this->complete();

        Livewire::actingAs(User::factory()->owner()->create())
            ->test(ReportsIndex::class)
            ->assertSee('Keuntungan Bersih')
            ->assertSee('Total HPP');
    }

    public function test_kasir_tidak_bisa_mengakses_export(): void
    {
        $this->actingAs($this->cashier)->get('/reports/export')->assertForbidden();
    }

    public function test_guest_diarahkan_ke_login(): void
    {
        $this->get('/reports')->assertRedirect('/login');
    }
}
