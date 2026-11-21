<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Acceso restringido · Coquechas</title>
    <style>body{font-family:system-ui;background:#f1f5f9;color:#172033;display:grid;min-height:100vh;place-items:center;margin:0}.card{max-width:34rem;background:#fff;padding:2.5rem;border-radius:1rem;box-shadow:0 10px 30px #0001}a,button{display:inline-block;margin:.5rem .5rem 0 0;padding:.7rem 1rem;border-radius:.5rem;border:0;background:#08758e;color:#fff;text-decoration:none;font:inherit}</style>
</head>
<body>
    <main class="card">
        <p>COQUECHAS · ACCESO</p>
        <h1>No tienes permiso para acceder a esta sección.</h1>
        <p>Tu cuenta inició sesión correctamente, pero este módulo requiere un rol o sector asignado diferente.</p>
        @auth
            @if (auth()->user()->hasRole('admin'))
                <a href="{{ route('admin.dashboard') }}">Ir a mi inicio</a>
            @else
                <a href="{{ config('modules.operations_url') }}">Ir a mi inicio</a>
            @endif
            <form method="post" action="{{ route('logout') }}">@csrf<button type="submit">Cerrar sesión</button></form>
        @else
            <a href="{{ route('login') }}">Volver a iniciar sesión</a>
        @endauth
    </main>
</body>
</html>
