<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Membatalkan pesanan tersimpan (belum dibayar). Tidak menyentuh stok/kas. */
class DiscardOpenOrder
{
    public function handle(Order $order, User $actor): Order
    {
        return DB::transaction(function () use ($order, $actor) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->status !== OrderStatus::Open) {
                throw ValidationException::withMessages(['order' => 'Hanya pesanan tersimpan yang bisa dibatalkan di sini.']);
            }

            $order->update([
                'status' => OrderStatus::Void,
                'voided_at' => now(),
                'voided_by' => $actor->id,
                'void_reason' => 'Pesanan tersimpan dibatalkan',
            ]);

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'order.void',
                'subject_type' => 'order',
                'subject_id' => $order->id,
                'old_values' => ['status' => 'open'],
                'new_values' => ['status' => 'void'],
                'ip' => request()->ip(),
            ]);

            return $order;
        });
    }
}
