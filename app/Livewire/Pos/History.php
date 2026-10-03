<?php

namespace App\Livewire\Pos;

use App\Actions\Auth\VerifyApproverPin;
use App\Actions\Orders\DiscardOpenOrder;
use App\Actions\Orders\VoidOrder;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Transaksi')]
class History extends Component
{
    #[Locked]
    public ?int $voidingId = null;

    public string $reason = '';

    public string $approver_pin = '';

    public function mount(): void
    {
        Gate::authorize('pos.transact');
    }

    public function startVoid(int $orderId): void
    {
        $order = Order::findOrFail($orderId);
        Gate::authorize('order.view', $order);
        Gate::authorize('order.void');

        $this->voidingId = $order->id;
        $this->reason = '';
        $this->approver_pin = '';
        $this->resetErrorBag();
    }

    public function cancelVoid(): void
    {
        $this->voidingId = null;
        $this->reason = '';
        $this->approver_pin = '';
        $this->resetErrorBag();
    }

    public function confirmVoid(): void
    {
        $order = Order::findOrFail($this->voidingId);
        Gate::authorize('order.view', $order);
        Gate::authorize('order.void');

        $this->validate(['reason' => ['required', 'string', 'min:5', 'max:255']], [
            'reason.min' => 'Alasan minimal 5 karakter.',
            'reason.required' => 'Alasan wajib diisi.',
        ]);

        $actor = Auth::user();

        try {
            $approvedBy = $actor->isCashier()
                ? app(VerifyApproverPin::class)->handle($actor, $this->approver_pin)
                : null;

            app(VoidOrder::class)->handle($order, $actor, $this->reason, $approvedBy);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
            $this->dispatch('toast', type: 'danger', message: $e->validator->errors()->first());

            return;
        }

        $this->dispatch('toast', type: 'success', message: 'Transaksi di-void.');
        $this->cancelVoid();
    }

    public function discard(int $orderId): void
    {
        $order = Order::findOrFail($orderId);
        Gate::authorize('order.view', $order);

        try {
            app(DiscardOpenOrder::class)->handle($order, Auth::user());
            $this->dispatch('toast', type: 'success', message: 'Pesanan tersimpan dibatalkan.');
        } catch (ValidationException $e) {
            $this->dispatch('toast', type: 'danger', message: $e->validator->errors()->first());
        }
    }

    public function render()
    {
        $user = Auth::user();

        $orders = Order::query()
            ->with(['payment', 'shift', 'user'])
            ->whereDate('business_date', now()->toDateString())
            ->when(! $user->isOwnerOrAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->latest('id')
            ->get();

        return view('livewire.pos.history', ['orders' => $orders]);
    }
}
