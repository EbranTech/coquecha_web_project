@extends('layouts.app')

@section('content')
<div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
    <section>
        <p class="text-sm font-medium text-sky-700">Catálogo operativo</p>
        <h1 class="mt-1 text-3xl font-bold">Sectores</h1>
        <p class="mt-2 text-slate-600">Define las zonas que atenderá la distribución comunitaria.</p>

        @if (session('success'))
            <p class="mt-5 rounded bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</p>
        @endif
        @if (session('error'))
            <p class="mt-5 rounded bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}</p>
        @endif

        <div class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600"><tr><th class="px-4 py-3">Nombre</th><th class="px-4 py-3">Ubicación</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3"><span class="sr-only">Acciones</span></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($sectors as $sector)
                        <tr><td class="px-4 py-3 font-medium">{{ $sector->name }}<p class="mt-1 font-normal text-slate-500">{{ $sector->description }}</p></td><td class="px-4 py-3 text-slate-600">{{ $sector->location_description ?: '—' }}</td><td class="px-4 py-3"><span class="rounded-full px-2 py-1 text-xs {{ $sector->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">{{ $sector->status === 'active' ? 'Activo' : 'Inactivo' }}</span></td><td class="space-x-2 px-4 py-3 text-right"><a class="text-sky-700 hover:underline" href="{{ route('admin.sectors.edit', $sector) }}">Editar</a><form class="inline" method="POST" action="{{ route('admin.sectors.destroy', $sector) }}" onsubmit="return confirm('¿Eliminar este sector?')">@csrf @method('DELETE')<button class="text-red-700 hover:underline" type="submit">Eliminar</button></form></td></tr>
                    @empty
                        <tr><td class="px-4 py-6 text-slate-500" colspan="4">Aún no hay sectores registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <aside class="h-fit rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <h2 class="text-lg font-semibold">{{ $editingId ? 'Editar sector' : 'Nuevo sector' }}</h2>
        <form class="mt-4 space-y-4" method="POST" action="{{ $editingId ? route('admin.sectors.update', $editingId) : route('admin.sectors.store') }}">
            @csrf
            @if ($editingId) @method('PUT') @endif

            <div><label class="block text-sm font-medium">Nombre</label><input class="mt-1 w-full rounded border-slate-300" name="name" value="{{ old('name', $name) }}"><p class="mt-1 text-sm text-red-600">@error('name') {{ $message }} @enderror</p></div>
            <div><label class="block text-sm font-medium">Descripción</label><textarea class="mt-1 w-full rounded border-slate-300" rows="3" name="description">{{ old('description', $description) }}</textarea><p class="mt-1 text-sm text-red-600">@error('description') {{ $message }} @enderror</p></div>
            <div><label class="block text-sm font-medium">Ubicación</label><input class="mt-1 w-full rounded border-slate-300" name="location_description" value="{{ old('location_description', $location_description) }}"><p class="mt-1 text-sm text-red-600">@error('location_description') {{ $message }} @enderror</p></div>
            <div><label class="block text-sm font-medium">Estado</label><select class="mt-1 w-full rounded border-slate-300" name="status"><option value="active" @selected((string) old('status', $status) === (string) 'active')>Activo</option><option value="inactive" @selected((string) old('status', $status) === (string) 'inactive')>Inactivo</option></select></div>
            <div><p class="block text-sm font-medium">Coordinadores responsables</p><div class="mt-1 space-y-2 rounded border border-slate-300 p-3">@forelse($coordinators as $coordinator)<label class="flex items-center gap-2 text-sm"><input type="checkbox" value="{{ $coordinator->id }}" name="coordinatorIds[]" @checked(in_array($coordinator->id, (array) old('coordinatorIds', $coordinatorIds)))>{{ $coordinator->display_name }} ({{ $coordinator->username }})</label>@empty<p class="text-sm text-slate-500">No hay coordinadores activos.</p>@endforelse</div><p class="mt-1 text-sm text-red-600">@error('coordinatorIds') {{ $message }} @enderror</p></div>
            <div class="flex gap-3"><button class="rounded bg-sky-700 px-4 py-2 font-medium text-white hover:bg-sky-800" type="submit">{{ $editingId ? 'Guardar cambios' : 'Crear sector' }}</button>@if ($editingId)<a class="text-sm text-slate-600 hover:underline" href="{{ route('admin.sectors') }}">Cancelar</a>@endif</div>
        </form>
    </aside>
</div>
@endsection
