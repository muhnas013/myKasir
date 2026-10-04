<?php

namespace App\Http\Controllers\Pos;

use App\Actions\Orders\CompleteOrder;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Dipakai JS offline (resources/js/offline/sync.js) untuk memutar ulang transaksi
 * yang diantrekan saat putus koneksi. Memanggil Action yang sama dengan alur
 * online (Livewire Cart::complete) — bukan Livewire karena butuh bisa dipanggil
 * fetch() polos tanpa runtime Livewire. Lihat docs/06_BUSINESS_PROCESS.md P7.
 */
class OfflineSyncController extends Controller
{
    public function store(Request $request, CompleteOrder $action): JsonResponse
    {
        $data = $request->validate([
            'shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer'],
            'lines.*.option_ids' => ['array'],
            'lines.*.option_ids.*' => ['integer'],
            'lines.*.qty' => ['required', 'integer', 'min:1', 'max:999'],
            'order_type' => ['required', 'in:dine_in,take_away'],
            'customer_label' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:1000'],
            'idempotency_key' => ['required', 'uuid'],
            'method' => ['required', 'in:cash'],
            'paid_amount' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'client_estimated_total' => ['nullable', 'integer', 'min:0'],
        ]);

        $shift = Shift::findOrFail($data['shift_id']);

        try {
            $order = $action->handle(Auth::user(), $shift, [
                'lines' => $data['lines'],
                'order_type' => $data['order_type'],
                'customer_label' => $data['customer_label'] ?? null,
                'note' => $data['note'] ?? null,
                'discount_type' => 'amount',
                'discount_value' => 0,
                'idempotency_key' => $data['idempotency_key'],
                'method' => $data['method'],
                'paid_amount' => $data['paid_amount'],
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        }

        if (isset($data['client_estimated_total']) && $data['client_estimated_total'] !== $order->total) {
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'order.offline_adjusted',
                'subject_type' => 'order',
                'subject_id' => $order->id,
                'old_values' => ['client_estimated_total' => $data['client_estimated_total']],
                'new_values' => ['total' => $order->total],
                'ip' => $request->ip(),
            ]);
        }

        return response()->json(['id' => $order->id, 'number' => $order->number, 'total' => $order->total]);
    }
}
