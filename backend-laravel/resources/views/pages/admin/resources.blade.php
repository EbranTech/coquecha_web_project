@extends('layouts.app')

@section('content')
<div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_24rem]">
    <section>
        <p class="text-sm font-medium text-sky-700">Inventario operativo</p>
        <h1 class="mt-1 text-3xl font-bold">Recursos</h1>
        <p class="mt-2 text-slate-600">Registra fuentes, tanques, bombas u otros recursos de la comunidad.</p>

        @if (session('success'))
            <p class="mt-5 rounded bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</p>
        @endif
        @if (session('error'))
            <p class="mt-5 rounded bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}</p>
        @endif

        <div class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600"><tr><th class="px-4 py-3">Recurso</th><th class="px-4 py-3">Capacidad</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3"><span class="sr-only">Acciones</span></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($resources as $resource)
                        <tr><td class="px-4 py-3 font-medium">{{ $resource->name }}<p class="mt-1 font-normal text-slate-500">{{ $resource->resource_type }} · {{ $resource->location_description ?: 'Sin ubicación' }}</p></td><td class="px-4 py-3 text-slate-600">{{ $resource->capacity !== null ? $resource->capacity.' '.$resource->capacity_unit : '—' }}</td><td class="px-4 py-3"><span class="rounded-full px-2 py-1 text-xs {{ $resource->status === 'available' ? 'bg-emerald-100 text-emerald-800' : ($resource->status === 'maintenance' ? 'bg-amber-100 text-amber-800' : 'bg-slate-200 text-slate-700') }}">{{ $resource->status === 'available' ? 'Disponible' : ($resource->status === 'maintenance' ? 'Mantenimiento' : 'Inactivo') }}</span></td><td class="space-x-2 px-4 py-3 text-right"><a class="text-sky-700 hover:underline" href="{{ route('admin.resources.edit', $resource) }}">Editar</a><form class="inline" method="POST" action="{{ route('admin.resources.destroy', $resource) }}" onsubmit="return confirm('¿Eliminar este recurso?')">@csrf @method('DELETE')<button class="text-red-700 hover:underline" type="submit">Eliminar</button></form></td></tr>
                    @empty
                        <tr><td class="px-4 py-6 text-slate-500" colspan="4">Aún no hay recursos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <aside class="h-fit rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <h2 class="text-lg font-semibold">{{ $editingId ? 'Editar recurso' : 'Nuevo recurso' }}</h2>
        <form class="mt-4 space-y-4" method="POST" action="{{ $editingId ? route('admin.resources.update', $editingId) : route('admin.resources.store') }}">
            @csrf
            @if ($editingId) @method('PUT') @endif

            <div><label class="block text-sm font-medium">Nombre</label><input class="mt-1 w-full rounded border-slate-300" name="name" value="{{ old('name', $name) }}"><p class="mt-1 text-sm text-red-600">@error('name') {{ $message }} @enderror</p></div>
            <div><label class="block text-sm font-medium">Tipo</label><input class="mt-1 w-full rounded border-slate-300" placeholder="Tanque, bomba, pozo..." name="resource_type" value="{{ old('resource_type', $resource_type) }}"><p class="mt-1 text-sm text-red-600">@error('resource_type') {{ $message }} @enderror</p></div>
            <div class="grid grid-cols-2 gap-3"><div><label class="block text-sm font-medium">Capacidad</label><input class="mt-1 w-full rounded border-slate-300" inputmode="decimal" name="capacity" value="{{ old('capacity', $capacity) }}"><p class="mt-1 text-sm text-red-600">@error('capacity') {{ $message }} @enderror</p></div><div><label class="block text-sm font-medium">Unidad</label><input class="mt-1 w-full rounded border-slate-300" placeholder="litros" name="capacity_unit" value="{{ old('capacity_unit', $capacity_unit) }}"></div></div>
            <div><label class="block text-sm font-medium">Ubicación</label><input class="mt-1 w-full rounded border-slate-300" name="location_description" value="{{ old('location_description', $location_description) }}"><p class="mt-1 text-sm text-red-600">@error('location_description') {{ $message }} @enderror</p></div>
            <div><label class="block text-sm font-medium">Sectores donde puede utilizarse</label><select class="mt-1 w-full rounded border-slate-300" multiple size="4" name="sectorIds[]">@foreach ($sectors as $sector)<option value="{{ $sector->id }}" @selected(in_array($sector->id, (array) old('sectorIds', $sectorIds)))>{{ $sector->name }}</option>@endforeach</select><p class="mt-1 text-xs text-slate-500">Usa Ctrl/Cmd para elegir varios sectores.</p><p class="mt-1 text-sm text-red-600">@error('sectorIds') {{ $message }} @enderror @error('sectorIds.*') {{ $message }} @enderror</p></div>
            <div><label class="block text-sm font-medium">Descripción</label><textarea class="mt-1 w-full rounded border-slate-300" rows="2" name="description">{{ old('description', $description) }}</textarea></div>
            <div><label class="block text-sm font-medium">Estado</label><select class="mt-1 w-full rounded border-slate-300" name="status"><option value="available" @selected((string) old('status', $status) === (string) 'available')>Disponible</option><option value="maintenance" @selected((string) old('status', $status) === (string) 'maintenance')>En mantenimiento</option><option value="inactive" @selected((string) old('status', $status) === (string) 'inactive')>Inactivo</option></select></div>
            <div class="flex gap-3"><button class="rounded bg-sky-700 px-4 py-2 font-medium text-white hover:bg-sky-800" type="submit">{{ $editingId ? 'Guardar cambios' : 'Crear recurso' }}</button>@if ($editingId)<a class="text-sm text-slate-600 hover:underline" href="{{ route('admin.resources') }}">Cancelar</a>@endif</div>
        </form>
    </aside>
</div>
@endsection
