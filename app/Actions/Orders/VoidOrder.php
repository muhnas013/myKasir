<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoidOrder
{
    public function __construct(private StockService $stock) {}

    /**
     * @param  ?int  $approvedBy  id owner/admin hasil VerifyApproverPin; wajib bila pelaku kasir
     */
    public function handle(Order $order, User $actor, string $reason, ?int $approvedBy = null): Order
    {
        $reason = trim($reason);
        if (mb_strlen($reason) < 5) {
            throw ValidationException::withMessages(['reason' => 'Alasan minimal 5 karakter.']);
        }

        return DB::transaction(function () use ($order, $actor, $reason, $approvedBy) {
            $order = Order::query()->with('shift')->lockForUpdate()->findOrFail($order->id);

            if ($order->status !== OrderStatus::Paid) {
                throw ValidationException::withMessages(['order' => 'Hanya transaksi lunas yang bisa di-void.']);
            }

            if (! $order->shift->isOpen() && ! $actor->isOwner()) {
                throw ValidationException::withMessages(['order' => 'Transaksi dari shift yang sudah ditutup hanya bisa di-void oleh pemilik.']);
            }

            if ($actor->isCashier()) {
                $approverOk = $approvedBy !== null
                    && User::query()->whereKey($approvedBy)->where('is_active', true)->whereIn('role', ['owner', 'admin'])->exists();

                if (! $approverOk) {
                    throw ValidationException::withMessages(['approver_pin' => 'Void oleh kasir butuh persetujuan PIN admin.']);
                }
            }

            $this->stock->returnForVoid($order, $actor);

            $order->update([
                'status' => OrderStatus::Void,
                'voided_at' => now(),
                'voided_by' => $actor->id,
                'void_reason' => mb_substr($reason, 0, 255),
            ]);

            AuditLog::create([
                'user_id' => $actor->id,
                'approved_by' => $approvedBy,
                'action' => 'order.void',
                'subject_type' => 'order',
                'subject_id' => $order->id,
                'old_values' => ['status' => 'paid', 'total' => $order->total],
                'new_values' => ['status' => 'void', 'reason' => $reason],
                'ip' => request()->ip(),
            ]);

            return $order;
        });
    }
}
