<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }} · Coquechas</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-900">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-4 sm:px-6">
                <a class="font-semibold text-sky-800" href="{{ route('admin.dashboard') }}">Coquechas · Administración</a>
                @auth
                    <nav aria-label="Módulos y secciones" class="flex flex-wrap items-center justify-end gap-1 rounded-xl border border-slate-200 bg-slate-100 p-1.5 text-sm shadow-sm">
                        <a class="rounded-lg px-3 py-2 font-medium text-slate-700 hover:bg-white hover:text-sky-800" href="{{ route('admin.sectors') }}">Sectores</a>
                        <a class="rounded-lg px-3 py-2 font-medium text-slate-700 hover:bg-white hover:text-sky-800" href="{{ route('admin.resources') }}">Recursos</a>
                        <a class="rounded-lg px-3 py-2 font-medium text-slate-700 hover:bg-white hover:text-sky-800" href="{{ route('admin.shifts') }}">Turnos</a>
                        <a class="rounded-lg px-3 py-2 font-medium text-slate-700 hover:bg-white hover:text-sky-800" href="{{ route('admin.users') }}">Usuarios</a>
                        <a class="rounded-lg bg-sky-700 px-3 py-2 font-semibold text-white hover:bg-sky-800" href="{{ config('modules.operations_url') }}">Operaciones</a>
                        <a class="rounded-lg bg-cyan-800 px-3 py-2 font-semibold text-white hover:bg-cyan-900" href="{{ config('modules.indicators_url') }}">Indicadores</a>
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
            @yield('content')
        </main>
    </body>
</html>
