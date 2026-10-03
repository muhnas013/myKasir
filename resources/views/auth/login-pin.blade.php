<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk PIN — MyKasir</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <div class="auth-screen">
        <div class="auth-card"
            x-data="{
                userId: {{ $locked ? Auth::id() : 'null' }},
                pin: '',
                add(d) { if (this.pin.length < 6) { this.pin += d } },
                back() { this.pin = this.pin.slice(0, -1) },
                submit() { if (this.pin.length === 6) { $refs.form.submit() } },
            }"
        >
            <div class="auth-card__brand">MyKasir</div>

            @if (! $locked)
                <h1 class="auth-card__title">Pilih Nama</h1>

                @if ($users->isEmpty())
                    <x-empty-state title="Belum ada kasir/admin aktif" icon="user" />
                @else
                    <div class="user-picker">
                        @foreach ($users as $user)
                            <button
                                type="button"
                                class="user-picker__item"
                                :class="{ 'is-selected': userId === {{ $user->id }} }"
                                @click="userId = {{ $user->id }}"
                            >{{ $user->name }}</button>
                        @endforeach
                    </div>
                @endif
            @else
                <h1 class="auth-card__title">Masukkan PIN — {{ Auth::user()->name }}</h1>
            @endif

            @error('pin')
                <p class="field-error" style="text-align:center; margin-bottom: var(--sp-3);">{{ $message }}</p>
            @enderror
            @error('user_id')
                <p class="field-error" style="text-align:center; margin-bottom: var(--sp-3);">{{ $message }}</p>
            @enderror

            <div class="pin-dots">
                <template x-for="i in 6" :key="i">
                    <span class="pin-dot" :class="{ 'is-filled': pin.length >= i }"></span>
                </template>
            </div>

            <div class="pin-pad">
                @foreach ([1,2,3,4,5,6,7,8,9] as $digit)
                    <button type="button" class="pin-pad__key" @click="add('{{ $digit }}')">{{ $digit }}</button>
                @endforeach
                <button type="button" class="pin-pad__key" @click="back()">⌫</button>
                <button type="button" class="pin-pad__key" @click="add('0')">0</button>
                <button type="button" class="pin-pad__key" @click="submit()" :disabled="pin.length < 6 || !userId">✓</button>
            </div>

            <form method="POST" action="{{ route('login.pin.store') }}" x-ref="form">
                @csrf
                <input type="hidden" name="user_id" :value="userId">
                <input type="hidden" name="pin" :value="pin">
            </form>

            @if (! $locked)
                <p style="text-align:center; margin-top: var(--sp-6);">
                    <a href="{{ route('login') }}">Masuk sebagai pemilik/admin</a>
                </p>
            @endif
        </div>
    </div>

    @livewireScripts
</body>
</html>
