<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="theme-color" content="#a3e635" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="default" />
    <meta name="apple-mobile-web-app-title" content="Noted" />
    <link rel="manifest" href="/manifest.webmanifest" />
    <link rel="icon" type="image/svg+xml" href="/icons/icon-192.svg" />
    <link rel="apple-touch-icon" href="/icons/icon-192.svg" />
    <title>{{ config('app.name', 'Noted') }} — {{ $title ?? 'App' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-white font-mono min-h-screen">

    {{-- Top Navigation --}}
    <header class="border-b-4 border-black bg-gradient-to-r from-lime-400 to-lime-300 sticky top-0 z-50">
        <div class="max-w-screen-xl mx-auto flex items-center justify-between px-4 py-3">
            <a href="{{ route('dashboard') }}"
               class="text-2xl font-black tracking-tight uppercase text-black hover:underline">
                📋 Noted
            </a>

            <nav class="hidden md:flex items-center gap-2">
                <a href="{{ route('dashboard') }}"
                   class="neo-btn {{ request()->routeIs('dashboard') ? 'bg-black text-lime-400' : 'bg-white text-black' }}">
                    Dashboard
                </a>
                <a href="{{ route('projects') }}"
                   class="neo-btn {{ request()->routeIs('projects') ? 'bg-black text-lime-400' : 'bg-white text-black' }}">
                    Projects
                </a>
                <a href="{{ route('ai') }}"
                   class="neo-btn {{ request()->routeIs('ai') ? 'bg-black text-lime-400' : 'bg-white text-black' }}">
                    AI
                </a>
                     <a href="{{ route('cashflow') }}"
                         class="neo-btn {{ request()->routeIs('cashflow') ? 'bg-black text-lime-400' : 'bg-white text-black' }}">
                          Cashflow
                     </a>
                <a href="{{ route('notes') }}"
                   class="neo-btn {{ request()->routeIs('notes') ? 'bg-black text-lime-400' : 'bg-white text-black' }}">
                    Notes
                </a>
                <a href="{{ route('accounts') }}"
                   class="neo-btn {{ request()->routeIs('accounts') ? 'bg-black text-lime-400' : 'bg-white text-black' }}">
                    Accounts
                </a>
                <a href="{{ route('hashtags') }}"
                   class="neo-btn {{ request()->routeIs('hashtags') ? 'bg-black text-lime-400' : 'bg-white text-black' }}">
                    #Tags
                </a>
                @auth
                    <a href="{{ route('password.edit') }}"
                       class="neo-btn {{ request()->routeIs('password.edit') ? 'bg-black text-lime-400' : 'bg-white text-black' }} text-sm px-3 py-1">
                        Password
                    </a>
                    <span class="text-xs font-black px-2">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="inline-block">
                        @csrf
                        <button type="submit" class="neo-btn bg-white text-black px-3 py-1 text-sm">Logout</button>
                    </form>
                @endauth
            </nav>

            {{-- Mobile hamburger --}}
            <div x-data="{ open: false }" class="md:hidden">
                <button @click="open = !open"
                        class="neo-btn bg-white text-black px-3 py-1 text-lg">
                    ☰
                </button>
                <div x-show="open" x-cloak
                     class="absolute top-full left-0 right-0 bg-lime-300 border-b-4 border-black z-50 space-y-1 py-1">
                    <a href="{{ route('dashboard') }}" class="block px-4 py-3 border-b-2 border-black font-bold hover:bg-lime-400">Dashboard</a>
                    <a href="{{ route('projects') }}" class="block px-4 py-3 border-b-2 border-black font-bold hover:bg-lime-400">Projects</a>
                    <a href="{{ route('ai') }}" class="block px-4 py-3 border-b-2 border-black font-bold hover:bg-lime-400">AI</a>
                    <a href="{{ route('cashflow') }}" class="block px-4 py-3 border-b-2 border-black font-bold hover:bg-lime-400">Cashflow</a>
                    <a href="{{ route('notes') }}" class="block px-4 py-3 border-b-2 border-black font-bold hover:bg-lime-400">Notes</a>
                    <a href="{{ route('accounts') }}" class="block px-4 py-3 border-b-2 border-black font-bold hover:bg-lime-400 mt-2">Accounts</a>
                    <a href="{{ route('hashtags') }}" class="block px-4 py-3 font-bold hover:bg-lime-400">#Tags</a>
                    @auth
                        <a href="{{ route('password.edit') }}" class="block px-4 py-3 border-t-2 border-black font-bold hover:bg-lime-400">Ubah Password</a>
                        <div class="px-4 py-2 border-t-2 border-black text-xs font-black">{{ auth()->user()->name }}</div>
                        <form method="POST" action="{{ route('logout') }}" class="px-4 py-3 border-t-2 border-black">
                            @csrf
                            <button type="submit" class="neo-btn bg-white text-black w-full">Logout</button>
                        </form>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="max-w-screen-xl mx-auto px-4 mt-4">
            <div class="border-4 border-black bg-lime-300 p-3 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] font-bold">
                ✅ {{ session('success') }}
            </div>
        </div>
    @endif

    <main class="max-w-screen-xl mx-auto px-4 py-6">
        {{ $slot }}
    </main>

    <footer class="border-t-4 border-black bg-gradient-to-r from-lime-100 to-lime-200 mt-12 py-4 text-center font-bold text-sm">
        &copy; {{ date('Y') }} Noted — Task Schedule &amp; Notes App
    </footer>

    @livewireScripts
</body>
</html>
