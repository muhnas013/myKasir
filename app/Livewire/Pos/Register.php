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
     * Katalog produk yang bisa dijual offline (F6, 06 P7 — iterasi 2: termasuk
     * varian). Harga opsi (`price_delta`) ikut di-cache di sini agar JS bisa
     * menampilkan estimasi tanpa menebak; server tetap satu-satunya penghitung
     * otoritatif saat sinkron (`CartPricer`).
     *
     * @return list<array{id: int, name: string, price: int, photo_url: ?string, variant_groups: list<array{id: int, name: string, is_required: bool, max_select: int, options: list<array{id: int, name: string, price_delta: int}>}>}>
     */
    private function offlineCatalog(): array
    {
        return Product::query()
            ->active()
            ->with('variantGroups.options')
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'image_path'])
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'price' => $p->price,
                'photo_url' => $p->photo_url,
                'variant_groups' => $p->variantGroups->map(fn ($g) => [
                    'id' => $g->id,
                    'name' => $g->name,
                    'is_required' => (bool) $g->is_required,
                    'max_select' => (int) $g->max_select,
                    'options' => $g->options->map(fn ($o) => [
                        'id' => $o->id,
                        'name' => $o->name,
                        'price_delta' => $o->price_delta,
                    ])->all(),
                ])->all(),
            ])
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
