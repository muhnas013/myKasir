<x-layouts.app title="Kasir">
    <div class="app-topbar">
        <h1>Kasir</h1>
    </div>
    <x-empty-state title="Modul Kasir &amp; Shift tersedia di Fase 3" icon="store">
        <p>Anda berhasil masuk sebagai {{ auth()->user()->name }}.</p>
    </x-empty-state>
</x-layouts.app>
