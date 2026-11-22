
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? config('app.name') }} · Coquechas</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">

    <header class="sticky top-0 z-50 border-b border-slate-200 bg-white shadow-sm">

        <!-- BARRA SUPERIOR -->
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            <div class="flex h-16 items-center justify-between gap-4">

                <!-- LOGO -->
                <a href="{{ route('admin.dashboard') }}"
                   class="flex shrink-0 items-center gap-3">

                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-700 text-white shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg"
                             class="h-6 w-6"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor"
                             stroke-width="1.8">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  d="M12 3c-2.5 4-7 8.5-7 12a7 7 0 0014 0c0-3.5-4.5-8-7-12z"/>
                        </svg>
                    </div>

                    <div class="leading-tight">
                        <span class="block text-lg font-bold tracking-tight text-slate-900">
                            Coquechas
                        </span>
                        <span class="block text-xs text-slate-500">
                            Administración
                        </span>
                    </div>
                </a>

                @auth
                    <!-- USUARIO Y SESIÓN DESKTOP -->
                    <div class="hidden items-center gap-4 md:flex">

                        <div class="flex items-center gap-3 border-l border-slate-200 pl-4">

                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-sky-100 font-semibold text-sky-700">
                                {{ strtoupper(substr(auth()->user()->display_name, 0, 1)) }}
                            </div>

                            <div class="hidden leading-tight lg:block">
                                <p class="max-w-40 truncate text-sm font-semibold text-slate-800">
                                    {{ auth()->user()->display_name }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    Administrador
                                </p>
                            </div>
                        </div>

                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600">
                                <span>Salir</span>
                            </button>
                        </form>

                    </div>

                    <!-- BOTÓN MÓVIL -->
                    <button id="mobile-menu-button"
                            type="button"
                            aria-label="Abrir menú"
                            aria-controls="mobile-menu"
                            aria-expanded="false"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-200 p-2 text-slate-600 transition hover:bg-slate-100 md:hidden">

                        <svg xmlns="http://www.w3.org/2000/svg"
                             class="h-6 w-6"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor"
                             stroke-width="2">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                @endauth

            </div>
        </div>

        @auth
            <!-- NAVEGACIÓN DESKTOP -->
            <div class="hidden border-t border-slate-100 bg-white md:block">

                <nav aria-label="Módulos y secciones"
                     class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">

                    <!-- MÓDULOS INTERNOS -->
                    <div class="flex items-center gap-1">

                        @php
                            $navItems = [
                                ['label' => 'Sectores', 'route' => 'admin.sectors'],
                                ['label' => 'Recursos', 'route' => 'admin.resources'],
                                ['label' => 'Turnos', 'route' => 'admin.shifts'],
                                ['label' => 'Usuarios', 'route' => 'admin.users'],
                            ];
                        @endphp

                        @foreach ($navItems as $item)
                            <a href="{{ route($item['route']) }}"
                               @class([
                                   'inline-flex items-center border-b-2 px-4 py-3 text-sm font-medium transition',
                                   'border-sky-600 bg-sky-50 text-sky-700' => request()->routeIs($item['route']),
                                   'border-transparent text-slate-600 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900' => !request()->routeIs($item['route']),
                               ])>
                                {{ $item['label'] }}
                            </a>
                        @endforeach

                    </div>

                    <!-- SISTEMAS EXTERNOS -->
                    <div class="flex shrink-0 items-center gap-2">

                        <a href="{{ config('modules.operations_url') }}"
                           class="inline-flex items-center gap-2 rounded-lg bg-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-800">
                            Operaciones
                        </a>

                        <a href="{{ config('modules.indicators_url') }}"
                           class="inline-flex items-center gap-2 rounded-lg bg-cyan-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-cyan-900">
                            Indicadores
                        </a>

                    </div>
                </nav>
            </div>

            <!-- MENÚ MÓVIL -->
            <div id="mobile-menu"
                 class="hidden border-t border-slate-200 bg-white md:hidden">

                <nav aria-label="Navegación móvil"
                     class="space-y-1 px-4 py-4">

                    @foreach ($navItems as $item)
                        <a href="{{ route($item['route']) }}"
                           @class([
                               'block rounded-lg px-4 py-3 text-sm fontx-medium transition',
                               'bg-sky-50 text-sky-700' => request()->routeIs($item['route']),
                               'text-slate-700 hover:bg-slate-100' => !request()->routeIs($item['route']),
                           ])>
                            {{ $item['label'] }}
                        </a>
                    @endforeach

                    <div class="my-3 border-t border-slate-200"></div>

                    <a href="{{ config('modules.operations_url') }}"
                       class="block rounded-lg bg-sky-700 px-4 py-3 text-center text-sm font-semibold text-white">
                        Operaciones
                    </a>

                    <a href="{{ config('modules.indicators_url') }}"
                       class="block rounded-lg bg-cyan-800 px-4 py-3 text-center text-sm font-semibold text-white">
                        Indicadores
                    </a>

                    <div class="mt-4 flex items-center justify-between border-t border-slate-200 pt-4">

                        <span class="text-sm font-medium text-slate-700">
                            {{ auth()->user()->display_name }}
                        </span>

                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="rounded-lg bg-red-50 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-100">
                                Cerrar sesión
                            </button>
                        </form>

                    </div>
                </nav>
            </div>
        @endauth

    </header>

    <!-- CONTENIDO -->
    <main class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @yield('content')
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const button = document.getElementById('mobile-menu-button');
            const menu = document.getElementById('mobile-menu');

            if (!button || !menu) return;

            button.addEventListener('click', function () {
                const isHidden = menu.classList.toggle('hidden');

                button.setAttribute(
                    'aria-expanded',
                    String(!isHidden)
                );

                button.setAttribute(
                    'aria-label',
                    isHidden ? 'Abrir menú' : 'Cerrar menú'
                );
            });
        });
    </script>

</body>
</html>
