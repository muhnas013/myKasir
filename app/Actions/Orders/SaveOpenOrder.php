<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Shift;
use App\Models\User;
use App\Services\CartPricer;
use App\Services\OrderNumberGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Menulis pesanan berstatus `open` (open bill) — dan dasar bagi CompleteOrder.
 * Idempoten per idempotency_key: key sama → pesanan yang sama diperbarui, bukan digandakan.
 */
class SaveOpenOrder
{
    public function __construct(private CartPricer $pricer, private OrderNumberGenerator $numbers) {}

    /**
     * @param  array{
     *     lines: list<array{product_id: int, option_ids?: list<int>, qty: int, note?: ?string}>,
     *     order_type: string, idempotency_key: string, customer_label?: ?string, note?: ?string,
     *     discount_type?: string, discount_value?: int, discount_approved_by?: ?int
     * }  $input
     */
    public function handle(User $actor, Shift $shift, array $input): Order
    {
        return DB::transaction(function () use ($actor, $shift, $input) {
            if (! $shift->isOpen() || $shift->user_id !== $actor->id) {
                throw ValidationException::withMessages(['cart' => 'Shift tidak aktif.']);
            }

            if ($input['lines'] === []) {
                throw ValidationException::withMessages(['cart' => 'Keranjang masih kosong.']);
            }

            $order = Order::query()->where('idempotency_key', $input['idempotency_key'])->lockForUpdate()->first();

            if ($order && $order->status !== OrderStatus::Open) {
                throw ValidationException::withMessages(['cart' => 'Pesanan ini sudah diselesaikan.']);
            }
            if ($order && $order->shift_id !== $shift->id) {
                throw ValidationException::withMessages(['cart' => 'Pesanan milik shift lain.']);
            }

            $type = OrderType::from($input['order_type']);
            $priced = $this->pricer->price(
                $input['lines'],
                $type,
                $input['discount_type'] ?? 'amount',
                (int) ($input['discount_value'] ?? 0),
            );

            $approvedBy = $input['discount_approved_by'] ?? null;
            if ($priced['discount'] > 0) {
                if ($actor->isCashier() && ! $this->isApprover($approvedBy)) {
                    throw ValidationException::withMessages(['discount' => 'Diskon oleh kasir butuh persetujuan PIN admin.']);
                }
            } else {
                $approvedBy = null;
            }

            $attributes = [
                'order_type' => $type,
                'customer_label' => $this->clean($input['customer_label'] ?? null),
                'note' => $this->clean($input['note'] ?? null),
                'subtotal' => $priced['subtotal'],
                'discount' => $priced['discount'],
                'service' => $priced['service'],
                'tax' => $priced['tax'],
                'rounding' => $priced['rounding'],
                'total' => $priced['total'],
                'discount_approved_by' => $approvedBy,
            ];

            if ($order) {
                $order->update($attributes);
                $order->items()->delete();
            } else {
                $date = now()->startOfDay();
                $order = Order::create($attributes + [
                    'number' => $this->numbers->next($date),
                    'business_date' => $date->toDateString(),
                    'shift_id' => $shift->id,
                    'user_id' => $actor->id,
                    'status' => OrderStatus::Open,
                    'idempotency_key' => $input['idempotency_key'],
                ]);
            }

            foreach ($priced['items'] as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'unit_price' => $item['unit_price'],
                    'qty' => $item['qty'],
                    'line_total' => $item['line_total'],
                    'options' => $item['options'] ?: null,
                    'note' => $this->clean($item['note'], 150),
                ]);
            }

            if ($priced['discount'] > 0) {
                AuditLog::create([
                    'user_id' => $actor->id,
                    'approved_by' => $approvedBy,
                    'action' => 'order.discount',
                    'subject_type' => 'order',
                    'subject_id' => $order->id,
                    'new_values' => ['discount' => $priced['discount'], 'subtotal' => $priced['subtotal']],
                    'ip' => request()->ip(),
                ]);
            }

            return $order;
        });
    }

    private function isApprover(?int $userId): bool
    {
        return $userId !== null
            && User::query()->whereKey($userId)->where('is_active', true)->whereIn('role', ['owner', 'admin'])->exists();
    }

    private function clean(?string $value, int $max = 255): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }
}
