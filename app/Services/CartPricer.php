<?php

namespace App\Services;

use App\Enums\OrderType;
use App\Models\Product;
use Illuminate\Validation\ValidationException;

/**
 * Mengubah keranjang dari klien (hanya product_id, option_ids, qty) menjadi item berharga.
 * Harga SELALU dibaca dari DB — klien tidak pernah menentukan harga/total.
 */
class CartPricer
{
    public function __construct(private PriceCalculator $calculator, private SettingService $settings) {}

    /**
     * @param  list<array{product_id: int, option_ids?: list<int>, qty: int, note?: ?string}>  $lines
     * @return array{items: list<array<string, mixed>>, subtotal: int, discount: int, service: int, tax: int, rounding: int, total: int}
     */
    public function price(array $lines, OrderType $type, string $discountType = 'amount', int $discountValue = 0): array
    {
        $products = Product::query()
            ->active()
            ->with('variantGroups.options')
            ->whereIn('id', collect($lines)->pluck('product_id')->unique())
            ->get()
            ->keyBy('id');

        $items = [];
        $subtotal = 0;

        foreach ($lines as $line) {
            $product = $products->get($line['product_id']);
            if (! $product) {
                throw ValidationException::withMessages(['cart' => 'Ada produk yang sudah tidak tersedia. Hapus dari keranjang.']);
            }

            $qty = (int) $line['qty'];
            if ($qty < 1 || $qty > 999) {
                throw ValidationException::withMessages(['cart' => 'Jumlah item tidak valid.']);
            }

            $options = $this->resolveOptions($product, $line['option_ids'] ?? []);
            $unitPrice = $product->price + array_sum(array_column($options, 'price_delta'));
            $lineTotal = $unitPrice * $qty;
            $subtotal += $lineTotal;

            $items[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'unit_price' => $unitPrice,
                'qty' => $qty,
                'line_total' => $lineTotal,
                'options' => $options,
                'note' => $line['note'] ?? null,
            ];
        }

        $discount = $this->calculator->discount($subtotal, $discountType, $discountValue);

        return ['items' => $items] + $this->calculator->totals(
            $subtotal,
            $discount,
            $type === OrderType::DineIn,
            (bool) $this->settings->get('service_enabled', false),
            (int) $this->settings->get('service_rate_bp', 0),
            (bool) $this->settings->get('tax_enabled', false),
            (int) $this->settings->get('tax_rate_bp', 0),
            (int) $this->settings->get('rounding_unit', 100),
        );
    }

    /**
     * @param  list<int>  $optionIds
     * @return list<array{id: int, name: string, price_delta: int}>
     */
    private function resolveOptions(Product $product, array $optionIds): array
    {
        $optionIds = array_values(array_unique(array_map('intval', $optionIds)));
        $chosen = [];

        foreach ($product->variantGroups as $group) {
            $picked = $group->options->whereIn('id', $optionIds);

            if ($group->is_required && $picked->isEmpty()) {
                throw ValidationException::withMessages(['cart' => "Pilihan {$group->name} untuk {$product->name} wajib diisi."]);
            }

            if ($picked->count() > $group->max_select) {
                throw ValidationException::withMessages(['cart' => "Pilihan {$group->name} untuk {$product->name} melebihi batas."]);
            }

            foreach ($picked as $option) {
                $chosen[] = ['id' => $option->id, 'name' => $option->name, 'price_delta' => $option->price_delta];
            }
        }

        if (count($chosen) !== count($optionIds)) {
            throw ValidationException::withMessages(['cart' => "Pilihan varian untuk {$product->name} tidak valid."]);
        }

        return $chosen;
    }
}
