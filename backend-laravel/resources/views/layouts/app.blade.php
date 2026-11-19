<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }} · Coquechas</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-900">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-4 sm:px-6">
                <a class="font-semibold text-sky-800" href="{{ route('admin.dashboard') }}">Coquechas · Administración</a>
                @auth
                    <nav class="flex items-center gap-4 text-sm">
                        <a class="hover:text-sky-700" href="{{ route('admin.sectors') }}">Sectores</a>
                        <a class="hover:text-sky-700" href="{{ route('admin.resources') }}">Recursos</a>
                        <span class="hidden text-slate-500 sm:inline">{{ auth()->user()->display_name }}</span>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button class="rounded bg-slate-800 px-3 py-1.5 text-white hover:bg-slate-700" type="submit">Salir</button>
                        </form>
                    </nav>
                @endauth
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
            {{ $slot }}
        </main>

        @livewireScripts
    </body>
</html>
