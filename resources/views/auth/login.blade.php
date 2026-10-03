<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — MyKasir</title>
    @vite(['resources/css/app.css'])
</head>
<body>
    <div class="auth-screen">
        <div class="auth-card">
            <div class="auth-card__brand">MyKasir</div>
            <h1 class="auth-card__title">Masuk Pemilik/Admin</h1>

            <form method="POST" action="{{ route('login.store') }}" class="field-group">
                @csrf
                <x-input
                    label="Email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    :error="$errors->first('email')"
                    autofocus
                    required
                />
                <x-input
                    label="Kata sandi"
                    name="password"
                    type="password"
                    :error="$errors->first('password')"
                    required
                />
                <x-button type="submit" style="width:100%">Masuk</x-button>
            </form>

            <p style="text-align:center; margin-top: var(--sp-6);">
                <a href="{{ route('login.pin') }}">Masuk sebagai kasir dengan PIN</a>
            </p>
        </div>
    </div>
</body>
</html>
