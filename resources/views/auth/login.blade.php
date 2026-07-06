<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="theme-color" content="#a3e635" />
    <title>{{ config('app.name', 'Noted') }} - Login</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[linear-gradient(135deg,#d9f99d_0%,#bbf7d0_40%,#ffffff_100%)] font-mono">
    <main class="min-h-screen flex items-center justify-center p-6">
        <div class="w-full max-w-md neo-card p-6 md:p-8">
            <p class="text-xs font-black uppercase tracking-widest text-lime-700">Welcome Back</p>
            <h1 class="text-3xl font-black mt-1 mb-6">Login Noted</h1>

            @if ($errors->any())
                <div class="mb-4 border-4 border-black bg-red-100 px-3 py-2 font-bold text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.submit') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="neo-label">Email</label>
                    <input id="email" name="email" type="email" required autofocus
                           value="{{ old('email') }}"
                           class="neo-input w-full" placeholder="you@example.com" />
                </div>

                <div>
                    <label for="password" class="neo-label">Password</label>
                    <input id="password" name="password" type="password" required
                           class="neo-input w-full" placeholder="Password" />
                </div>

                <label class="inline-flex items-center gap-2 font-bold text-sm">
                    <input type="checkbox" name="remember" value="1" class="w-4 h-4 border-2 border-black" {{ old('remember') ? 'checked' : '' }} />
                    Ingat saya di device ini
                </label>

                <button type="submit" class="neo-btn w-full bg-black text-lime-400 justify-center">
                    Masuk
                </button>
            </form>

            <p class="mt-5 text-xs font-bold text-gray-600">
                Dengan memilih "ingat saya", Anda tidak perlu login ulang di device yang sama.
            </p>
        </div>
    </main>
</body>
</html>
