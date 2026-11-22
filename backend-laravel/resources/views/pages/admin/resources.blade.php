
@extends('layouts.app')

@section('content')
@php
    $totalResources = $resources->count();
    $availableResources = $resources->where('status', 'available')->count();
    $maintenanceResources = $resources->where('status', 'maintenance')->count();
    $inactiveResources = $resources->where('status', 'inactive')->count();

    $isEditing = !empty($editingId);
    $hasErrors = $errors->any();

    $inputClass = 'mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100';
    $labelClass = 'block text-sm font-semibold text-slate-700';
@endphp

<div class="space-y-6">

    <!-- ENCABEZADO -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-sky-700">
                Inventario operativo
            </p>

            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">
                Recursos
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                Administra fuentes, tanques, bombas y otros recursos de la comunidad.
            </p>
        </div>

        @if ($isEditing)
            <a href="{{ route('admin.resources') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-700 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-800">
                + Nuevo recurso
            </a>
        @else
            <button type="button"
                    onclick="openResourcePanel()"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-700 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-800">
                + Nuevo recurso
            </button>
        @endif
    </div>

    <!-- MENSAJES -->
    @if (session('success'))
        <div role="status"
             class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div role="alert"
             class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <!-- ESTADÍSTICAS -->
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Total recursos</p>
            <p class="mt-3 text-3xl font-bold text-slate-900">
                {{ $totalResources }}
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Disponibles</p>
            <p class="mt-3 text-3xl font-bold text-emerald-600">
                {{ $availableResources }}
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Mantenimiento</p>
            <p class="mt-3 text-3xl font-bold text-amber-600">
                {{ $maintenanceResources }}
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Inactivos</p>
            <p class="mt-3 text-3xl font-bold text-slate-500">
                {{ $inactiveResources }}
            </p>
        </div>
    </div>

    <!-- TABLA -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">
                    Recursos registrados
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Consulta el inventario y su disponibilidad.
                </p>
            </div>

            <input id="resourceSearch"
                   type="search"
                   placeholder="Buscar recurso..."
                   aria-label="Buscar recurso"
                   class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 sm:w-64">
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-4">Recurso</th>
                        <th class="px-6 py-4">Capacidad</th>
                        <th class="px-6 py-4">Estado</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($resources as $resource)
                        <tr class="resource-row transition hover:bg-slate-50">

                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-900">
                                    {{ $resource->name }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $resource->resource_type }}
                                    ·
                                    {{ $resource->location_description ?: 'Sin ubicación' }}
                                </p>
                            </td>

                            <td class="px-6 py-4">
                                @if ($resource->capacity !== null)
                                    <span class="font-semibold text-slate-800">
                                        {{ $resource->capacity }}
                                    </span>
                                    <span class="text-xs text-slate-500">
                                        {{ $resource->capacity_unit }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            <td class="px-6 py-4">
                                @if ($resource->status === 'available')
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Disponible
                                    </span>
                                @elseif ($resource->status === 'maintenance')
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                        Mantenimiento
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                        Inactivo
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.resources.edit', $resource) }}"
                                       class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-sky-700 transition hover:border-sky-200 hover:bg-sky-50">
                                        Editar
                                    </a>

                                    <form method="POST"
                                          action="{{ route('admin.resources.destroy', $resource) }}"
                                          onsubmit="return confirm('¿Seguro que deseas eliminar este recurso?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="rounded-lg border border-red-100 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-16 text-center">
                                <h3 class="text-base font-semibold text-slate-800">
                                    No hay recursos registrados
                                </h3>
                                <p class="mt-1 text-sm text-slate-500">
                                    Comienza agregando un recurso al inventario.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 bg-slate-50 px-6 py-4">
            <p class="text-xs text-slate-500">
                Mostrando {{ $totalResources }} recursos
            </p>
        </div>
    </div>
</div>

<!-- PANEL LATERAL -->
<div id="resourcePanel"
     class="fixed inset-0 z-[100] hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="resourcePanelTitle">

    <div onclick="closeResourcePanel()"
         class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm">
    </div>

    <aside class="absolute inset-y-0 right-0 flex w-full max-w-lg flex-col bg-white shadow-2xl">

        <!-- CABECERA -->
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
            <div>
                <h2 id="resourcePanelTitle" class="text-xl font-bold text-slate-900">
                    {{ $isEditing ? 'Editar recurso' : 'Nuevo recurso' }}
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Información del inventario operativo.
                </p>
            </div>

            <button type="button"
                    onclick="closeResourcePanel()"
                    aria-label="Cerrar formulario"
                    class="rounded-lg p-2 text-slate-500 hover:bg-slate-100">
                ✕
            </button>
        </div>

        <!-- FORMULARIO -->
        <form id="resourceForm"
              method="POST"
              action="{{ $isEditing ? route('admin.resources.update', $editingId) : route('admin.resources.store') }}"
              class="flex min-h-0 flex-1 flex-col">

            @csrf
            @if ($isEditing)
                @method('PUT')
            @endif

            <div class="flex-1 space-y-5 overflow-y-auto px-6 py-6">

                <!-- NOMBRE -->
                <div>
                    <label for="resource_name" class="{{ $labelClass }}">
                        Nombre del recurso
                    </label>

                    <input id="resource_name"
                           type="text"
                           name="name"
                           required
                           value="{{ old('name', $name) }}"
                           placeholder="Ej. Tanque principal"
                           class="{{ $inputClass }}">

                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- TIPO -->
                <div>
                    <label for="resource_type" class="{{ $labelClass }}">
                        Tipo de recurso
                    </label>

                    <input id="resource_type"
                           type="text"
                           name="resource_type"
                           required
                           value="{{ old('resource_type', $resource_type) }}"
                           placeholder="Tanque, bomba, pozo, fuente..."
                           class="{{ $inputClass }}">

                    @error('resource_type')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- CAPACIDAD -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="capacity" class="{{ $labelClass }}">
                            Capacidad
                        </label>

                        <input id="capacity"
                               type="number"
                               min="0"
                               step="any"
                               name="capacity"
                               value="{{ old('capacity', $capacity) }}"
                               placeholder="Ej. 5000"
                               class="{{ $inputClass }}">

                        @error('capacity')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="capacity_unit" class="{{ $labelClass }}">
                            Unidad
                        </label>

                        <input id="capacity_unit"
                               type="text"
                               name="capacity_unit"
                               value="{{ old('capacity_unit', $capacity_unit) }}"
                               placeholder="Litros, m³..."
                               class="{{ $inputClass }}">

                        @error('capacity_unit')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- UBICACIÓN -->
                <div>
                    <label for="resource_location" class="{{ $labelClass }}">
                        Ubicación
                    </label>

                    <input id="resource_location"
                           type="text"
                           name="location_description"
                           value="{{ old('location_description', $location_description) }}"
                           placeholder="Ej. Zona norte, junto al pozo"
                           class="{{ $inputClass }}">

                    @error('location_description')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- SECTORES -->
                <div>
                    <label class="{{ $labelClass }}">
                        Sectores donde puede utilizarse
                    </label>

                    <p class="mt-1 text-xs text-slate-500">
                        Selecciona uno o varios sectores asociados.
                    </p>

                    <div class="mt-3 max-h-52 space-y-2 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-3">
                        @forelse ($sectors as $sector)
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg bg-white p-3 transition hover:bg-sky-50">
                                <input type="checkbox"
                                       name="sectorIds[]"
                                       value="{{ $sector->id }}"
                                       @checked(in_array($sector->id, (array) old('sectorIds', $sectorIds)))
                                       class="h-4 w-4 rounded border-slate-300 text-sky-700 focus:ring-sky-500">

                                <span class="text-sm font-medium text-slate-700">
                                    {{ $sector->name }}
                                </span>
                            </label>
                        @empty
                            <p class="py-3 text-center text-sm text-slate-500">
                                No hay sectores registrados.
                            </p>
                        @endforelse
                    </div>

                    @error('sectorIds')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror

                    @error('sectorIds.*')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- DESCRIPCIÓN -->
                <div>
                    <label for="resource_description" class="{{ $labelClass }}">
                        Descripción
                    </label>

                    <textarea id="resource_description"
                              name="description"
                              rows="3"
                              placeholder="Información adicional del recurso..."
                              class="{{ $inputClass }}">{{ old('description', $description) }}</textarea>

                    @error('description')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- ESTADO -->
                <div>
                    <label for="resource_status" class="{{ $labelClass }}">
                        Estado operativo
                    </label>

                    <select id="resource_status"
                            name="status"
                            class="{{ $inputClass }}">
                        <option value="available"
                            @selected(old('status', $status) === 'available')>
                            Disponible
                        </option>

                        <option value="maintenance"
                            @selected(old('status', $status) === 'maintenance')>
                            En mantenimiento
                        </option>

                        <option value="inactive"
                            @selected(old('status', $status) === 'inactive')>
                            Inactivo
                        </option>
                    </select>
                </div>
            </div>

            <!-- ACCIONES -->
            <div class="flex gap-3 border-t border-slate-200 bg-white px-6 py-5">
                @if ($isEditing)
                    <a href="{{ route('admin.resources') }}"
                       class="flex-1 rounded-xl border border-slate-300 px-4 py-3 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </a>
                @else
                    <button type="button"
                            onclick="closeResourcePanel()"
                            class="flex-1 rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </button>
                @endif

                <button type="submit"
                        class="flex-1 rounded-xl bg-sky-700 px-4 py-3 text-sm font-semibold text-white hover:bg-sky-800">
                    {{ $isEditing ? 'Guardar cambios' : 'Crear recurso' }}
                </button>
            </div>
        </form>
    </aside>
</div>

<script>
    const resourcePanel = document.getElementById('resourcePanel');
    const resourceForm = document.getElementById('resourceForm');
    const resourceSearch = document.getElementById('resourceSearch');

    const resourceIsEditing = @json($isEditing);
    const resourceIndexUrl = @json(route('admin.resources'));

    let resourcePreviousFocus = null;

    function openResourcePanel() {
        resourcePreviousFocus = document.activeElement;

        resourcePanel.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        resourcePanel.querySelector('#resource_name')?.focus();
    }

    function closeResourcePanel() {
        // Si estamos editando, volver al listado.
        if (resourceIsEditing) {
            window.location.href = resourceIndexUrl;
            return;
        }

        // Si estamos creando, cerrar y limpiar.
        resourcePanel.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');

        resourceForm.reset();

        resourceForm.querySelectorAll('input:not([type="hidden"]), textarea').forEach(field => {
            if (field.type === 'checkbox' || field.type === 'radio') {
                field.checked = false;
            } else if (field.type !== 'submit') {
                field.value = '';
            }
        });

        const status = resourceForm.querySelector('[name="status"]');
        if (status) status.value = 'available';

        resourcePreviousFocus?.focus();
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' &&
            !resourcePanel.classList.contains('hidden')) {
            closeResourcePanel();
        }
    });

    // BÚSQUEDA LOCAL
    resourceSearch?.addEventListener('input', function () {
        const query = this.value.toLowerCase().trim();

        document.querySelectorAll('.resource-row').forEach(row => {
            row.classList.toggle(
                'hidden',
                !row.textContent.toLowerCase().includes(query)
            );
        });
    });

    // ABRIR EN EDICIÓN O TRAS ERROR DE VALIDACIÓN
    @if ($isEditing || $hasErrors)
        document.addEventListener('DOMContentLoaded', openResourcePanel);
    @endif
</script>
@endsection
