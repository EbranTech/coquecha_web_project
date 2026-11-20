<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Iniciar sesión · Coquechas</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-900">
        <header class="border-b border-slate-200 bg-white"><div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4"><a class="font-semibold text-sky-800" href="{{ route('login') }}">Coquechas</a><nav class="flex gap-4 text-sm"><a class="text-sky-800 hover:underline" href="http://localhost:5175">Operaciones</a><a class="text-sky-800 hover:underline" href="http://localhost:3000">Indicadores</a></nav></div></header>
        <div class="flex min-h-[calc(100vh-65px)] items-center justify-center p-4">
        <main class="w-full max-w-md rounded-xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm font-semibold uppercase tracking-widest text-sky-700">Coquechas</p>
            <h1 class="mt-2 text-2xl font-bold">Panel administrativo</h1>
            <p class="mt-2 text-sm text-slate-600">Inicia sesión con una cuenta administradora.</p>

            <form class="mt-6 space-y-4" action="{{ route('login.store') }}" method="POST">
                @csrf
                <div>
                    <label class="block text-sm font-medium" for="username">Usuario</label>
                    <input class="mt-1 w-full rounded border-slate-300" id="username" name="username" required value="{{ old('username') }}" autofocus>
                    @error('username') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium" for="password">Contraseña</label>
                    <input class="mt-1 w-full rounded border-slate-300" id="password" name="password" type="password" required>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600"><input name="remember" type="checkbox"> Recordarme</label>
                <button class="w-full rounded bg-sky-700 px-4 py-2 font-semibold text-white hover:bg-sky-800" type="submit">Ingresar</button>
            </form>
        </main>
        </div>
    </body>
</html>
