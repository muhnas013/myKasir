<?php

namespace App\Livewire\Pos;

use App\Models\Product;
use App\Services\SettingService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Kasir')]
class Register extends Component
{
    public function mount(): void
    {
        Gate::authorize('pos.transact');
    }

    public function render()
    {
        return view('livewire.pos.register', [
            'offlineCatalog' => $this->offlineCatalog(),
            'offlineSettings' => $this->offlineSettings(),
        ]);
    }

    /**
     * Katalog produk yang bisa dijual offline (F6, 06 P7): tanpa grup varian
     * sama sekali — opsi berbayar tidak ditebak JS. Dipakai Alpine saat offline.
     *
     * @return list<array{id: int, name: string, price: int}>
     */
    private function offlineCatalog(): array
    {
        return Product::query()
            ->active()
            ->doesntHave('variantGroups')
            ->orderBy('name')
            ->get(['id', 'name', 'price'])
            ->map(fn (Product $p) => ['id' => $p->id, 'name' => $p->name, 'price' => $p->price])
            ->all();
    }

    /** Tarif yang dipakai JS untuk estimasi total offline — sama rumus dengan PriceCalculator (06). */
    private function offlineSettings(): array
    {
        $settings = app(SettingService::class);

        return [
            'service_enabled' => (bool) $settings->get('service_enabled', false),
            'service_rate_bp' => (int) $settings->get('service_rate_bp', 0),
            'tax_enabled' => (bool) $settings->get('tax_enabled', false),
            'tax_rate_bp' => (int) $settings->get('tax_rate_bp', 0),
            'rounding_unit' => (int) $settings->get('rounding_unit', 100),
        ];
    }
}
