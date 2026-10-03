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
                    <a href="{{ route('pos.placeholder') }}" class="app-sidebar__link @if(request()->routeIs('pos.*')) is-active @endif">
                        <x-icon name="store" /> Kasir
                    </a>
                @endcan
                @can('settings.manage')
                    <a href="{{ route('settings.index') }}" class="app-sidebar__link @if(request()->routeIs('settings.*')) is-active @endif">
                        <x-icon name="settings" /> Pengaturan
                    </a>
                @endcan
            </nav>
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
