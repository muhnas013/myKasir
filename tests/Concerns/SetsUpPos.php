<?php

namespace Tests\Concerns;

use App\Actions\Shifts\OpenShift;
use App\Models\Product;
use App\Models\Shift;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

trait SetsUpPos
{
    protected User $cashier;

    protected Shift $shift;

    protected Product $esKopi;

    protected Product $nasiGoreng;

    protected Product $pisangGoreng;

    protected function setUpPos(array $settings = []): void
    {
        $values = array_merge([
            'tax_enabled' => true,
            'tax_rate_bp' => 1000,
            'service_enabled' => false,
            'service_rate_bp' => 500,
            'rounding_unit' => 100,
            'method_cash' => true,
            'method_qris' => true,
            'method_card' => true,
            'allow_negative_stock' => false,
        ], $settings);

        foreach ($values as $key => $value) {
            app(SettingService::class)->set($key, $value);
        }

        $this->cashier = User::factory()->cashier()->create();
        $this->shift = app(OpenShift::class)->handle($this->cashier, 200000);

        $this->esKopi = Product::factory()->create(['name' => 'Es Kopi Susu Gula Aren', 'price' => 18000]);
        $this->nasiGoreng = Product::factory()->create(['name' => 'Nasi Goreng Spesial', 'price' => 25000]);
        $this->pisangGoreng = Product::factory()->create(['name' => 'Pisang Goreng', 'price' => 12000]);
    }

    protected function admin(string $pin = '246813'): User
    {
        return User::factory()->admin()->create(['pin_hash' => Hash::make($pin)]);
    }

    /** Keranjang standar docs/23: 2× Es Kopi + 1 Nasi Goreng + 1 Pisang Goreng = 73.000. */
    protected function standardLines(): array
    {
        return [
            ['product_id' => $this->esKopi->id, 'qty' => 2],
            ['product_id' => $this->nasiGoreng->id, 'qty' => 1],
            ['product_id' => $this->pisangGoreng->id, 'qty' => 1],
        ];
    }

    protected function cashInput(array $override = []): array
    {
        return array_merge([
            'lines' => $this->standardLines(),
            'order_type' => 'take_away',
            'idempotency_key' => (string) Str::uuid(),
            'method' => 'cash',
            'paid_amount' => 100000,
        ], $override);
    }
}
