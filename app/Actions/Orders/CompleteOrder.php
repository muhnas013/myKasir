<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Shift;
use App\Models\User;
use App\Services\SettingService;
use App\Services\StockService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteOrder
{
    public function __construct(
        private SaveOpenOrder $saveOpenOrder,
        private StockService $stock,
        private SettingService $settings,
    ) {}

    /**
     * Satu transaksi DB: order (paid) + item + payment + potong stok. Gagal → rollback penuh.
     * `idempotency_key` sama → order yang sama dikembalikan, tidak ada order ganda.
     *
     * @param  array<string, mixed>  $input  lihat SaveOpenOrder + method, paid_amount, reference
     */
    public function handle(User $actor, Shift $shift, array $input): Order
    {
        try {
            return DB::transaction(fn () => $this->complete($actor, $shift, $input));
        } catch (UniqueConstraintViolationException $e) {
            // Dua permintaan bersamaan dengan key sama: yang kalah mengembalikan order pemenang.
            $existing = Order::query()
                ->where('idempotency_key', $input['idempotency_key'])
                ->where('status', OrderStatus::Paid)
                ->first();

            return $existing ?? throw $e;
        }
    }

    private function complete(User $actor, Shift $shift, array $input): Order
    {
        $existing = Order::query()->where('idempotency_key', $input['idempotency_key'])->lockForUpdate()->first();
        if ($existing?->status === OrderStatus::Paid) {
            return $existing;
        }

        $method = PaymentMethod::tryFrom((string) ($input['method'] ?? ''));
        if (! $method || ! $this->settings->get('method_'.$method->value, false)) {
            throw ValidationException::withMessages(['method' => 'Metode pembayaran tidak tersedia.']);
        }

        $order = $this->saveOpenOrder->handle($actor, $shift, $input);

        $paid = (int) ($input['paid_amount'] ?? 0);
        $reference = trim((string) ($input['reference'] ?? ''));

        match ($method) {
            PaymentMethod::Cash => $paid >= $order->total
                ?: throw ValidationException::withMessages(['paid_amount' => 'Uang diterima kurang dari total.']),
            PaymentMethod::Card => $reference !== ''
                ?: throw ValidationException::withMessages(['reference' => 'Nomor referensi wajib diisi.']),
            PaymentMethod::Qris => null,
        };

        if ($method !== PaymentMethod::Cash) {
            $paid = $order->total;
        }

        $order->load('items');
        $this->stock->deductForSale($order, $order->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'qty' => $item->qty,
            'option_ids' => collect($item->options ?? [])->pluck('id')->all(),
        ]), $actor);

        $order->payment()->create([
            'method' => $method,
            'amount' => $order->total,
            'paid_amount' => $paid,
            'change_amount' => $method === PaymentMethod::Cash ? $paid - $order->total : 0,
            'reference' => $method === PaymentMethod::Card ? mb_substr($reference, 0, 50) : null,
        ]);

        $order->update(['status' => OrderStatus::Paid, 'paid_at' => now()]);

        return $order->fresh(['items', 'payment']);
    }
}
