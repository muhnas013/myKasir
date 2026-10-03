<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Satu-satunya jalan mengubah stok; selalu menulis stock_movements.
 * Stok bahan baku/resep menyusul di F4.
 */
class StockService
{
    public function __construct(private SettingService $settings) {}

    public function isSellable(Product $product): bool
    {
        return ! $product->track_stock
            || $product->stock_qty > 0
            || (bool) $this->settings->get('allow_negative_stock', false);
    }

    /**
     * Potong stok produk ber-track_stock. Lempar ValidationException bila tak cukup.
     *
     * @param  iterable<array{product_id: int, qty: int}>  $items
     */
    public function deductForSale(Order $order, iterable $items, User $actor): void
    {
        $perProduct = [];
        foreach ($items as $item) {
            $perProduct[$item['product_id']] = ($perProduct[$item['product_id']] ?? 0) + $item['qty'];
        }
        ksort($perProduct); // urutan tetap → hindari deadlock

        $allowNegative = (bool) $this->settings->get('allow_negative_stock', false);

        foreach ($perProduct as $productId => $qty) {
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
    }

    /** Kembalikan stok penjualan order (void). */
    public function returnForVoid(Order $order, User $actor): void
    {
        $sales = StockMovement::query()
            ->where('order_id', $order->id)
            ->where('type', StockMovementType::Sale)
            ->whereNotNull('product_id')
            ->get();

        foreach ($sales as $sale) {
            $qty = (int) abs((float) $sale->qty);

            Product::query()->lockForUpdate()->find($sale->product_id)?->increment('stock_qty', $qty);

            StockMovement::create([
                'product_id' => $sale->product_id,
                'type' => StockMovementType::VoidReturn,
                'qty' => $qty,
                'order_id' => $order->id,
                'user_id' => $actor->id,
            ]);
        }
    }
}
