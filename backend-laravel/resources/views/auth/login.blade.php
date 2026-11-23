<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Iniciar sesión · Coquechas</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-900">
        <div class="flex min-h-screen items-center justify-center p-4">
        <main class="w-full max-w-md rounded-xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm font-semibold uppercase tracking-widest text-sky-700">Coquechas</p>
            <h1 class="mt-2 text-2xl font-bold">Iniciar sesión</h1>
            <p class="mt-2 text-sm text-slate-600">Accede a los módulos habilitados para tu cuenta.</p>
             <p class="mt-2 text-xs text-slate-400">
            Servidor: {{ config('app.server_number', 'No definido') }}
            </p>

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
