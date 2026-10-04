<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Satu-satunya jalan mengubah stok (produk ber-track_stock maupun bahan baku); selalu menulis stock_movements.
 */
class StockService
{
    public function __construct(private SettingService $settings) {}

    /**
     * Sellable dicek dari resep DASAR (tanpa opsi varian, karena opsi belum dipilih di grid) atau
     * dari stock_qty produk bila produk tak berresep.
     */
    public function isSellable(Product $product): bool
    {
        $recipes = $product->relationLoaded('recipes') ? $product->recipes : $product->recipes()->with('ingredient')->get();
        $allowNegative = (bool) $this->settings->get('allow_negative_stock', false);

        if ($recipes->isNotEmpty()) {
            if ($allowNegative) {
                return true;
            }

            foreach ($recipes as $recipe) {
                $ingredient = $recipe->relationLoaded('ingredient') ? $recipe->ingredient : Ingredient::find($recipe->ingredient_id);
                if (! $ingredient || bccomp((string) $ingredient->stock_qty, (string) $recipe->qty, 3) < 0) {
                    return false;
                }
            }

            return true;
        }

        return ! $product->track_stock
            || $product->stock_qty > 0
            || $allowNegative;
    }

    /**
     * HPP per porsi: Σ (qty resep × avg_cost bahan) ÷ 1000, dibulatkan half-up. Meliputi resep dasar + opsi terpilih.
     *
     * @param  list<int>  $optionIds
     */
    public function porsiCost(Product $product, array $optionIds = []): int
    {
        $recipes = ($product->relationLoaded('recipes') ? $product->recipes : $product->recipes()->get())
            ->concat($this->optionRecipes($product, $optionIds));

        if ($recipes->isEmpty()) {
            // Tanpa resep bahan baku: pakai Modal/HPP manual produk (07) sebagai HPP flat per porsi.
            return $product->cost_price;
        }

        $ingredients = $recipes->first()->relationLoaded('ingredient')
            ? null
            : Ingredient::query()->whereIn('id', $recipes->pluck('ingredient_id')->unique())->get()->keyBy('id');

        $total = '0';
        foreach ($recipes as $recipe) {
            $ingredient = $recipe->relationLoaded('ingredient') ? $recipe->ingredient : $ingredients?->get($recipe->ingredient_id);
            if (! $ingredient) {
                continue;
            }
            $total = bcadd($total, bcmul((string) $recipe->qty, (string) $ingredient->avg_cost, 6), 6);
        }

        return $this->roundHalfUp(bcdiv($total, '1000', 6));
    }

    /**
     * Potong stok untuk satu order lunas. Produk berresep memotong bahan (dasar + opsi terpilih);
     * produk tanpa resep dengan track_stock memotong stock_qty produk. Lempar ValidationException bila stok tak cukup.
     *
     * @param  iterable<array{product_id: int, qty: int, option_ids?: list<int>}>  $items
     */
    public function deductForSale(Order $order, iterable $items, User $actor): void
    {
        $allowNegative = (bool) $this->settings->get('allow_negative_stock', false);

        $productQty = [];
        $ingredientQty = [];

        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            if (! $product) {
                continue;
            }

            $optionIds = $item['option_ids'] ?? [];
            $recipes = Recipe::query()
                ->where('product_id', $product->id)
                ->when($optionIds !== [], fn ($q) => $q->orWhereIn('variant_option_id', $optionIds))
                ->get();

            if ($recipes->isNotEmpty()) {
                foreach ($recipes as $recipe) {
                    $needed = bcmul((string) $recipe->qty, (string) $item['qty'], 3);
                    $ingredientQty[$recipe->ingredient_id] = bcadd($ingredientQty[$recipe->ingredient_id] ?? '0', $needed, 3);
                }
            } elseif ($product->track_stock) {
                $productQty[$product->id] = ($productQty[$product->id] ?? 0) + $item['qty'];
            }
        }

        ksort($productQty); // urutan tetap → hindari deadlock
        foreach ($productQty as $productId => $qty) {
            $product = Product::query()->lockForUpdate()->find($productId);

            if (! $product || ! $product->track_stock) {
                continue;
            }

            if (! $allowNegative && $product->stock_qty < $qty) {
                throw ValidationException::withMessages(['cart' => "Stok {$product->name} tidak cukup."]);
            }

            $product->decrement('stock_qty', $qty);

            StockMovement::create([
                'product_id' => $product->id,
                'type' => StockMovementType::Sale,
                'qty' => -$qty,
                'order_id' => $order->id,
                'user_id' => $actor->id,
            ]);
        }

        ksort($ingredientQty);
        foreach ($ingredientQty as $ingredientId => $qty) {
            $ingredient = Ingredient::query()->lockForUpdate()->find($ingredientId);

            if (! $ingredient) {
                continue;
            }

            if (! $allowNegative && bccomp((string) $ingredient->stock_qty, $qty, 3) < 0) {
                throw ValidationException::withMessages(['cart' => "Stok {$ingredient->name} tidak cukup."]);
            }

            $ingredient->decrement('stock_qty', $qty);

            StockMovement::create([
                'ingredient_id' => $ingredient->id,
                'type' => StockMovementType::Sale,
                'qty' => bcmul($qty, '-1', 3),
                'order_id' => $order->id,
                'user_id' => $actor->id,
            ]);
        }
    }

    /** Kembalikan stok penjualan order (void): produk track_stock maupun bahan baku. */
    public function returnForVoid(Order $order, User $actor): void
    {
        $sales = StockMovement::query()
            ->where('order_id', $order->id)
            ->where('type', StockMovementType::Sale)
            ->get();

        foreach ($sales as $sale) {
            if ($sale->product_id) {
                $qty = (int) abs((float) $sale->qty);
                Product::query()->lockForUpdate()->find($sale->product_id)?->increment('stock_qty', $qty);

                StockMovement::create([
                    'product_id' => $sale->product_id,
                    'type' => StockMovementType::VoidReturn,
                    'qty' => $qty,
                    'order_id' => $order->id,
                    'user_id' => $actor->id,
                ]);
            } elseif ($sale->ingredient_id) {
                $qty = bcmul((string) $sale->qty, '-1', 3);
                Ingredient::query()->lockForUpdate()->find($sale->ingredient_id)?->increment('stock_qty', $qty);

                StockMovement::create([
                    'ingredient_id' => $sale->ingredient_id,
                    'type' => StockMovementType::VoidReturn,
                    'qty' => $qty,
                    'order_id' => $order->id,
                    'user_id' => $actor->id,
                ]);
            }
        }
    }

    /**
     * Stok masuk (docs/06 P6): qty > 0, harga beli total → avg_cost baru = rata-rata tertimbang, half-up.
     */
    public function receivePurchase(Ingredient $ingredient, string $qty, int $totalCost, User $actor): Ingredient
    {
        if (bccomp($qty, '0', 3) <= 0) {
            throw ValidationException::withMessages(['qty' => 'Jumlah stok masuk harus lebih dari 0.']);
        }
        if ($totalCost <= 0) {
            throw ValidationException::withMessages(['total_cost' => 'Harga beli total harus lebih dari 0.']);
        }

        return DB::transaction(function () use ($ingredient, $qty, $totalCost, $actor) {
            $ingredient = Ingredient::query()->lockForUpdate()->findOrFail($ingredient->id);

            $newQty = bcadd((string) $ingredient->stock_qty, $qty, 3);
            $numerator = bcadd(
                bcmul((string) $ingredient->stock_qty, (string) $ingredient->avg_cost, 6),
                bcmul((string) $totalCost, '1000', 6),
                6,
            );
            $newAvgCost = $this->roundHalfUp(bcdiv($numerator, $newQty, 6));

            $ingredient->update(['stock_qty' => $newQty, 'avg_cost' => $newAvgCost]);

            StockMovement::create([
                'ingredient_id' => $ingredient->id,
                'type' => StockMovementType::Purchase,
                'qty' => $qty,
                'total_cost' => $totalCost,
                'user_id' => $actor->id,
            ]);

            return $ingredient;
        });
    }

    /**
     * Opname (docs/06 P6): input stok fisik, selisih dicatat sebagai adjustment + alasan wajib.
     */
    public function adjustStock(Ingredient $ingredient, string $physicalQty, string $reason, User $actor): Ingredient
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'Alasan opname wajib diisi.']);
        }

        return DB::transaction(function () use ($ingredient, $physicalQty, $reason, $actor) {
            $ingredient = Ingredient::query()->lockForUpdate()->findOrFail($ingredient->id);

            $diff = bcsub($physicalQty, (string) $ingredient->stock_qty, 3);
            if (bccomp($diff, '0', 3) === 0) {
                return $ingredient;
            }

            $ingredient->update(['stock_qty' => $physicalQty]);

            StockMovement::create([
                'ingredient_id' => $ingredient->id,
                'type' => StockMovementType::Adjustment,
                'qty' => $diff,
                'user_id' => $actor->id,
                'note' => mb_substr($reason, 0, 255),
            ]);

            return $ingredient;
        });
    }

    /**
     * @param  list<int>  $optionIds
     * @return Collection<int, Recipe>
     */
    private function optionRecipes(Product $product, array $optionIds): Collection
    {
        if ($optionIds === []) {
            return collect();
        }

        if ($product->relationLoaded('variantGroups')) {
            return $product->variantGroups
                ->flatMap(fn ($group) => $group->relationLoaded('options') ? $group->options : collect())
                ->whereIn('id', $optionIds)
                ->flatMap(fn ($option) => $option->relationLoaded('recipes') ? $option->recipes : $option->recipes()->get());
        }

        return Recipe::query()->whereIn('variant_option_id', $optionIds)->get();
    }

    /** bcmath half-up: hanya untuk nilai non-negatif (qty & biaya stok selalu ≥ 0). */
    private function roundHalfUp(string $value): int
    {
        return (int) bcadd($value, '0.5', 0);
    }
}
