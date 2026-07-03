<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
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

            <nav class="hidden md:flex items-center gap-1">
                <a href="{{ route('dashboard') }}"
                   class="neo-btn {{ request()->routeIs('dashboard') ? 'bg-black text-lime-400' : 'bg-white text-black' }}">
                    Dashboard
                </a>
                <a href="{{ route('projects') }}"
                   class="neo-btn {{ request()->routeIs('projects') ? 'bg-black text-lime-400' : 'bg-white text-black' }}">
                    Projects
                </a>
                <a href="{{ route('notes') }}"
                   class="neo-btn {{ request()->routeIs('notes') ? 'bg-black text-lime-400' : 'bg-white text-black' }}">
                    Notes
                </a>
                <a href="{{ route('hashtags') }}"
                   class="neo-btn {{ request()->routeIs('hashtags') ? 'bg-black text-lime-400' : 'bg-white text-black' }}">
                    #Tags
                </a>
            </nav>

            {{-- Mobile hamburger --}}
            <div x-data="{ open: false }" class="md:hidden">
                <button @click="open = !open"
                        class="neo-btn bg-white text-black px-3 py-1 text-lg">
                    ☰
                </button>
                <div x-show="open" x-cloak
                     class="absolute top-full left-0 right-0 bg-lime-300 border-b-4 border-black z-50">
                    <a href="{{ route('dashboard') }}" class="block px-4 py-3 border-b-2 border-black font-bold hover:bg-lime-400">Dashboard</a>
                    <a href="{{ route('projects') }}" class="block px-4 py-3 border-b-2 border-black font-bold hover:bg-lime-400">Projects</a>
                    <a href="{{ route('notes') }}" class="block px-4 py-3 border-b-2 border-black font-bold hover:bg-lime-400">Notes</a>
                    <a href="{{ route('hashtags') }}" class="block px-4 py-3 font-bold hover:bg-lime-400">#Tags</a>
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
