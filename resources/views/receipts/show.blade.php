<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk {{ $order->number }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="receipt-page">
    <div class="receipt-actions">
        <button type="button" class="btn btn--primary" onclick="window.print()">Cetak Struk</button>
    </div>

    <article class="receipt">
        <header class="receipt__header">
            <div class="receipt__outlet">{{ $outlet['name'] }}</div>
            @if ($outlet['address'])<div>{{ $outlet['address'] }}</div>@endif
            @if ($outlet['phone'])<div>{{ $outlet['phone'] }}</div>@endif
        </header>

        <div class="receipt__meta">
            <div>No. {{ $order->number }}</div>
            <div>{{ ($order->paid_at ?? $order->created_at)->timezone('Asia/Makassar')->format('d/m/Y H.i') }} WITA</div>
            <div>Kasir: {{ $order->user->name }}</div>
            <div>{{ $order->order_type->label() }}@if ($order->customer_label) · {{ $order->customer_label }}@endif</div>
            @if ($order->status->value === 'void')<div class="receipt__void">** VOID **</div>@endif
        </div>

        <div class="receipt__rule"></div>

        @foreach ($order->items as $item)
            <div class="receipt__item">
                <div>{{ $item->product_name }}</div>
                @if ($item->options)
                    <div class="receipt__opts">{{ collect($item->options)->pluck('name')->implode(', ') }}</div>
                @endif
                <div class="receipt__line">
                    <span>{{ $item->qty }} x {{ \App\Support\Money::format($item->unit_price) }}</span>
                    <span>{{ \App\Support\Money::format($item->line_total) }}</span>
                </div>
            </div>
        @endforeach

        <div class="receipt__rule"></div>

        <div class="receipt__line"><span>Subtotal</span><span>{{ \App\Support\Money::format($order->subtotal) }}</span></div>
        @if ($order->discount > 0)
            <div class="receipt__line"><span>Diskon</span><span>-{{ \App\Support\Money::format($order->discount) }}</span></div>
        @endif
        @if ($order->service > 0)
            <div class="receipt__line"><span>Layanan</span><span>{{ \App\Support\Money::format($order->service) }}</span></div>
        @endif
        @if ($order->tax > 0)
            <div class="receipt__line"><span>PB1 {{ rtrim(rtrim(number_format($outlet['tax_rate_bp'] / 100, 2, ',', ''), '0'), ',') }}%</span><span>{{ \App\Support\Money::format($order->tax) }}</span></div>
        @endif
        @if ($order->rounding !== 0)
            <div class="receipt__line"><span>Pembulatan</span><span>{{ \App\Support\Money::format($order->rounding) }}</span></div>
        @endif
        <div class="receipt__line receipt__line--total"><span>Total</span><span>{{ \App\Support\Money::format($order->total) }}</span></div>

        @if ($order->payment)
            <div class="receipt__line"><span>Bayar ({{ $order->payment->method->label() }})</span><span>{{ \App\Support\Money::format($order->payment->paid_amount) }}</span></div>
            <div class="receipt__line"><span>Kembali</span><span>{{ \App\Support\Money::format($order->payment->change_amount) }}</span></div>
            @if ($order->payment->reference)
                <div class="receipt__line"><span>Ref.</span><span>{{ $order->payment->reference }}</span></div>
            @endif
        @endif

        @if ($outlet['footer'])
            <div class="receipt__rule"></div>
            <footer class="receipt__footer">{{ $outlet['footer'] }}</footer>
        @endif
    </article>
</body>
</html>
