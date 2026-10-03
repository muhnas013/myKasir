<?php

namespace App\Livewire\Pos;

use App\Actions\Auth\VerifyApproverPin;
use App\Actions\Orders\CompleteOrder;
use App\Actions\Orders\SaveOpenOrder;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Services\CartPricer;
use App\Services\SettingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Keranjang kasir. Klien hanya memegang product_id, option_ids, qty — harga/total
 * selalu dihitung ulang server (CartPricer) saat render dan saat bayar.
 */
class Cart extends Component
{
    /** @var list<array{key: string, product_id: int, option_ids: list<int>, qty: int}> */
    public array $lines = [];

    public string $order_type = 'take_away';

    public string $customer_label = '';

    public string $note = '';

    // Diskon yang berlaku: Locked agar tidak bisa diubah klien setelah persetujuan PIN.
    #[Locked]
    public string $discount_type = 'amount';

    #[Locked]
    public int $discount_value = 0;

    #[Locked]
    public ?int $discountApprovedBy = null;

    #[Locked]
    public string $idempotencyKey = '';

    #[Locked]
    public ?int $completedOrderId = null;

    // Draf form diskon
    public bool $showDiscount = false;

    public string $draft_discount_type = 'amount';

    public int|string $draft_discount_value = 0;

    public string $approver_pin = '';

    // Dialog bayar
    public bool $showPay = false;

    public string $method = 'cash';

    public int|string $paid_amount = 0;

    public string $reference = '';

    public function mount(): void
    {
        Gate::authorize('pos.transact');
        $this->idempotencyKey = (string) Str::uuid();

        if ($resume = request()->query('resume')) {
            $this->loadOpenOrder((int) $resume);
        }
    }

    #[On('cart-add')]
    public function add(int $productId, array $optionIds = []): void
    {
        Gate::authorize('pos.transact');

        $optionIds = array_values(array_unique(array_map('intval', $optionIds)));
        sort($optionIds);

        try {
            // Validasi produk/opsi di server sebelum masuk keranjang.
            app(CartPricer::class)->price([['product_id' => $productId, 'option_ids' => $optionIds, 'qty' => 1]], OrderType::TakeAway);
        } catch (ValidationException $e) {
            $this->dispatch('toast', type: 'danger', message: $e->validator->errors()->first());

            return;
        }

        $this->completedOrderId = null;

        foreach ($this->lines as &$line) {
            if ($line['product_id'] === $productId && $line['option_ids'] === $optionIds) {
                $line['qty'] = min(999, $line['qty'] + 1);

                return;
            }
        }
        unset($line);

        $this->lines[] = ['key' => (string) Str::uuid(), 'product_id' => $productId, 'option_ids' => $optionIds, 'qty' => 1];
    }

    public function increment(string $key): void
    {
        foreach ($this->lines as &$line) {
            if ($line['key'] === $key) {
                $line['qty'] = min(999, $line['qty'] + 1);
            }
        }
    }

    public function decrement(string $key): void
    {
        foreach ($this->lines as $i => &$line) {
            if ($line['key'] === $key) {
                $line['qty']--;
                if ($line['qty'] < 1) {
                    unset($this->lines[$i]);
                }
            }
        }
        $this->lines = array_values($this->lines);
    }

    public function remove(string $key): void
    {
        $this->lines = array_values(array_filter($this->lines, fn ($line) => $line['key'] !== $key));
    }

    public function openDiscount(): void
    {
        $this->draft_discount_type = $this->discount_type;
        $this->draft_discount_value = $this->discount_value;
        $this->approver_pin = '';
        $this->resetErrorBag();
        $this->showDiscount = true;
    }

    public function closeDiscount(): void
    {
        $this->showDiscount = false;
        $this->approver_pin = '';
        $this->resetErrorBag();
    }

    public function applyDiscount(): void
    {
        Gate::authorize('pos.transact');

        $this->validate([
            'draft_discount_type' => ['required', 'in:amount,percent'],
            'draft_discount_value' => ['required', 'integer', 'min:0', 'max:'.($this->draft_discount_type === 'percent' ? 100 : 1000000000)],
        ]);

        $value = (int) $this->draft_discount_value;
        $approvedBy = null;

        if ($value > 0 && Auth::user()->isCashier()) {
            try {
                $approvedBy = app(VerifyApproverPin::class)->handle(Auth::user(), $this->approver_pin);
            } catch (ValidationException $e) {
                $this->addError('approver_pin', $e->validator->errors()->first());

                return;
            }
        }

        $this->discount_type = $this->draft_discount_type;
        $this->discount_value = $value;
        $this->discountApprovedBy = $approvedBy;
        $this->closeDiscount();
    }

    public function openPay(): void
    {
        Gate::authorize('pos.transact');

        if ($this->lines === []) {
            return;
        }

        $methods = $this->enabledMethods();
        if ($methods === []) {
            $this->dispatch('toast', type: 'danger', message: 'Belum ada metode pembayaran aktif. Hubungi pemilik.');

            return;
        }

        $this->method = array_key_first($methods);
        $this->paid_amount = 0;
        $this->reference = '';
        $this->resetErrorBag();
        $this->showPay = true;
    }

    public function closePay(): void
    {
        $this->showPay = false;
        $this->resetErrorBag();
    }

    public function setPaid(int $amount): void
    {
        $this->paid_amount = max(0, $amount);
    }

    public function complete(): void
    {
        Gate::authorize('pos.transact');

        $shift = Auth::user()->activeShift();
        if (! $shift) {
            $this->redirectRoute('shift.open');

            return;
        }

        $this->validate([
            'method' => ['required', 'in:cash,qris,card'],
            'paid_amount' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'reference' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $order = app(CompleteOrder::class)->handle(Auth::user(), $shift, $this->orderInput() + [
                'method' => $this->method,
                'paid_amount' => (int) $this->paid_amount,
                'reference' => $this->reference,
            ]);
        } catch (ValidationException $e) {
            $this->reportErrors($e);

            return;
        }

        $this->completedOrderId = $order->id;
        $this->showPay = false;
        $this->dispatch('toast', type: 'success', message: 'Pembayaran berhasil.');
    }

    public function saveOpen(): void
    {
        Gate::authorize('pos.transact');

        $shift = Auth::user()->activeShift();
        if (! $shift) {
            $this->redirectRoute('shift.open');

            return;
        }

        try {
            app(SaveOpenOrder::class)->handle(Auth::user(), $shift, $this->orderInput());
        } catch (ValidationException $e) {
            $this->reportErrors($e);

            return;
        }

        $this->dispatch('toast', type: 'success', message: 'Pesanan disimpan.');
        $this->newOrder();
    }

    public function newOrder(): void
    {
        $this->reset([
            'lines', 'order_type', 'customer_label', 'note', 'discount_type', 'discount_value',
            'discountApprovedBy', 'completedOrderId', 'showPay', 'showDiscount', 'method', 'paid_amount', 'reference',
        ]);
        $this->idempotencyKey = (string) Str::uuid();
        $this->resetErrorBag();
    }

    private function loadOpenOrder(int $orderId): void
    {
        $order = Order::query()->with('items')->find($orderId);
        $shift = Auth::user()->activeShift();

        if (! $order || ! $shift || $order->status !== OrderStatus::Open || $order->shift_id !== $shift->id) {
            $this->dispatch('toast', type: 'danger', message: 'Pesanan tersimpan tidak ditemukan.');

            return;
        }

        Gate::authorize('order.view', $order);

        $this->idempotencyKey = $order->idempotency_key;
        $this->order_type = $order->order_type->value;
        $this->customer_label = (string) $order->customer_label;
        $this->note = (string) $order->note;
        $this->discount_type = 'amount';
        $this->discount_value = $order->discount;
        $this->discountApprovedBy = $order->discount_approved_by;
        $this->lines = $order->items->map(function ($item) {
            $ids = collect($item->options ?? [])->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();

            return ['key' => (string) Str::uuid(), 'product_id' => $item->product_id, 'option_ids' => $ids, 'qty' => $item->qty];
        })->all();
    }

    /** @return array<string, mixed> */
    private function orderInput(): array
    {
        return [
            'lines' => collect($this->lines)->map(fn ($line) => [
                'product_id' => $line['product_id'],
                'option_ids' => $line['option_ids'],
                'qty' => $line['qty'],
            ])->all(),
            'order_type' => $this->order_type,
            'customer_label' => $this->customer_label,
            'note' => $this->note,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'discount_approved_by' => $this->discountApprovedBy,
            'idempotency_key' => $this->idempotencyKey,
        ];
    }

    private function reportErrors(ValidationException $e): void
    {
        $message = $e->validator->errors()->first();

        foreach ($e->errors() as $field => $messages) {
            $this->addError($field, $messages[0]);
        }

        $this->dispatch('toast', type: 'danger', message: $message);
    }

    /** @return array<string, string> value => label, hanya metode yang diaktifkan di pengaturan */
    private function enabledMethods(): array
    {
        $settings = app(SettingService::class);

        return collect(PaymentMethod::cases())
            ->filter(fn (PaymentMethod $m) => $settings->get('method_'.$m->value, false))
            ->mapWithKeys(fn (PaymentMethod $m) => [$m->value => $m->label()])
            ->all();
    }

    public function render()
    {
        $priced = null;
        $error = null;

        if ($this->lines !== [] && in_array($this->order_type, ['dine_in', 'take_away'], true)) {
            try {
                $priced = app(CartPricer::class)->price(
                    collect($this->lines)->map(fn ($l) => ['product_id' => $l['product_id'], 'option_ids' => $l['option_ids'], 'qty' => $l['qty']])->all(),
                    OrderType::from($this->order_type),
                    $this->discount_type,
                    $this->discount_value,
                );
            } catch (ValidationException $e) {
                $error = $e->validator->errors()->first();
            }
        }

        $qrisPath = app(SettingService::class)->get('qris_image_path');

        return view('livewire.pos.cart', [
            'priced' => $priced,
            'error' => $error,
            'methods' => $this->enabledMethods(),
            'qrisUrl' => $qrisPath ? Storage::disk('public')->url($qrisPath) : null,
            'completed' => $this->completedOrderId ? Order::with('payment')->find($this->completedOrderId) : null,
        ]);
    }
}
