@props(['title' => 'MyKasir'])
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'MyKasir' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <div class="app-shell">
        <aside class="app-sidebar">
            <div class="app-sidebar__brand">MyKasir</div>
            <nav class="app-sidebar__nav">
                @can('pos.transact')
                    <a href="{{ route('pos.index') }}" class="app-sidebar__link @if(request()->routeIs('pos.*')) is-active @endif">
                        <x-icon name="store" /> Kasir
                    </a>
                @endcan
                @can('pos.transact')
                    <a href="{{ route('orders.index') }}" class="app-sidebar__link @if(request()->routeIs('orders.*')) is-active @endif">
                        <x-icon name="receipt" /> Transaksi
                    </a>
                @endcan
                @can('menu.manage')
                    <a href="{{ route('menu.index') }}" class="app-sidebar__link @if(request()->routeIs('menu.*')) is-active @endif">
                        <x-icon name="utensils" /> Menu
                    </a>
                @endcan
                @can('stock.manage')
                    <a href="{{ route('stock.index') }}" class="app-sidebar__link @if(request()->routeIs('stock.*')) is-active @endif">
                        <x-icon name="package" /> Stok
                    </a>
                @endcan
                @can('settings.manage')
                    <a href="{{ route('settings.index') }}" class="app-sidebar__link @if(request()->routeIs('settings.*')) is-active @endif">
                        <x-icon name="settings" /> Pengaturan
                    </a>
                @endcan
            </nav>
            @php($activeShift = auth()->user()->activeShift())
            @if ($activeShift)
                <div class="shift-card">
                    <div class="shift-card__label">Shift aktif</div>
                    <div class="shift-card__value">sejak {{ $activeShift->opened_at->format('H.i') }}</div>
                    <a href="{{ route('shift.close') }}" class="shift-card__link">Tutup Shift</a>
                </div>
            @endif
            <form method="POST" action="{{ route('lock') }}" style="margin-top:auto">
                @csrf
                <x-button variant="secondary" type="submit" style="width:100%">
                    <x-icon name="lock" /> Kunci
                </x-button>
            </form>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-button variant="ghost" type="submit" style="width:100%">
                    <x-icon name="log-out" /> Keluar
                </x-button>
            </form>
        </aside>
        <main class="app-main">
            {{ $slot }}
        </main>
    </div>

    <div class="toast-stack" x-data="{ toasts: [] }" x-on:toast.window="
        toasts.push({ id: Date.now(), type: $event.detail.type ?? 'success', message: $event.detail.message });
        setTimeout(() => toasts.shift(), 4000)
    ">
        <template x-for="toast in toasts" :key="toast.id">
            <div class="toast" :class="'toast--' + toast.type" x-text="toast.message"></div>
        </template>
    </div>

    @livewireScripts
</body>
</html>
