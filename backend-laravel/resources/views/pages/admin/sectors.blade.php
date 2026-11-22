
@extends('layouts.app')

@section('content')
@php
    $totalSectors = $sectors->count();
    $activeSectors = $sectors->where('status', 'active')->count();
    $inactiveSectors = $sectors->where('status', 'inactive')->count();

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
                Catálogo operativo
            </p>

            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">
                Sectores
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                Administra las zonas de distribución comunitaria.
            </p>
        </div>

        @if ($isEditing)
            <a href="{{ route('admin.sectors') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-700 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-800">
                + Nuevo sector
            </a>
        @else
            <button type="button"
                    onclick="openSectorPanel()"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-700 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-800">
                + Nuevo sector
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
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Total sectores</p>
            <p class="mt-3 text-3xl font-bold text-slate-900">
                {{ $totalSectors }}
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Sectores activos</p>
            <p class="mt-3 text-3xl font-bold text-emerald-600">
                {{ $activeSectors }}
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Sectores inactivos</p>
            <p class="mt-3 text-3xl font-bold text-slate-500">
                {{ $inactiveSectors }}
            </p>
        </div>
    </div>

    <!-- TABLA -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">
                    Sectores registrados
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Consulta y administra los sectores disponibles.
                </p>
            </div>

            <input id="sectorSearch"
                   type="search"
                   placeholder="Buscar sector..."
                   aria-label="Buscar sector"
                   class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 sm:w-64">
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-4">Sector</th>
                        <th class="px-6 py-4">Ubicación</th>
                        <th class="px-6 py-4">Estado</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($sectors as $sector)
                        <tr class="sector-row transition hover:bg-slate-50">

                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-900">
                                    {{ $sector->name }}
                                </p>
                                <p class="mt-1 max-w-xs text-xs leading-relaxed text-slate-500">
                                    {{ $sector->description ?: 'Sin descripción' }}
                                </p>
                            </td>

                            <td class="px-6 py-4 text-slate-600">
                                {{ $sector->location_description ?: 'No especificada' }}
                            </td>

                            <td class="px-6 py-4">
                                @if ($sector->status === 'active')
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Activo
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
                                    <a href="{{ route('admin.sectors.edit', $sector) }}"
                                       class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-sky-700 transition hover:border-sky-200 hover:bg-sky-50">
                                        Editar
                                    </a>

                                    <form method="POST"
                                          action="{{ route('admin.sectors.destroy', $sector) }}"
                                          onsubmit="return confirm('¿Seguro que deseas eliminar este sector?')">
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
                                    No hay sectores registrados
                                </h3>
                                <p class="mt-1 text-sm text-slate-500">
                                    Comienza creando tu primer sector.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 bg-slate-50 px-6 py-4">
            <p class="text-xs text-slate-500">
                Mostrando {{ $totalSectors }} sectores
            </p>
        </div>
    </div>
</div>

<!-- PANEL LATERAL -->
<div id="sectorPanel"
     class="fixed inset-0 z-[100] hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="sectorPanelTitle">

    <!-- FONDO -->
    <div onclick="closeSectorPanel()"
         class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm">
    </div>

    <aside class="absolute inset-y-0 right-0 flex w-full max-w-lg flex-col bg-white shadow-2xl">

        <!-- CABECERA -->
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
            <div>
                <h2 id="sectorPanelTitle" class="text-xl font-bold text-slate-900">
                    {{ $isEditing ? 'Editar sector' : 'Nuevo sector' }}
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Completa la información del sector.
                </p>
            </div>

            <button type="button"
                    onclick="closeSectorPanel()"
                    aria-label="Cerrar formulario"
                    class="rounded-lg p-2 text-slate-500 hover:bg-slate-100">
                ✕
            </button>
        </div>

        <!-- FORMULARIO -->
        <form id="sectorForm"
              method="POST"
              action="{{ $isEditing ? route('admin.sectors.update', $editingId) : route('admin.sectors.store') }}"
              class="flex min-h-0 flex-1 flex-col">

            @csrf
            @if ($isEditing)
                @method('PUT')
            @endif

            <div class="flex-1 space-y-5 overflow-y-auto px-6 py-6">

                <!-- NOMBRE -->
                <div>
                    <label for="sector_name" class="{{ $labelClass }}">
                        Nombre del sector
                    </label>

                    <input id="sector_name"
                           type="text"
                           name="name"
                           required
                           value="{{ old('name', $name) }}"
                           placeholder="Ej. Sector Central"
                           class="{{ $inputClass }}">

                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- DESCRIPCIÓN -->
                <div>
                    <label for="sector_description" class="{{ $labelClass }}">
                        Descripción
                    </label>

                    <textarea id="sector_description"
                              name="description"
                              rows="3"
                              placeholder="Describe brevemente este sector..."
                              class="{{ $inputClass }}">{{ old('description', $description) }}</textarea>

                    @error('description')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- UBICACIÓN -->
                <div>
                    <label for="sector_location" class="{{ $labelClass }}">
                        Ubicación
                    </label>

                    <input id="sector_location"
                           type="text"
                           name="location_description"
                           value="{{ old('location_description', $location_description) }}"
                           placeholder="Ej. Zona norte de la comunidad"
                           class="{{ $inputClass }}">

                    @error('location_description')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- ESTADO -->
                <div>
                    <label for="sector_status" class="{{ $labelClass }}">
                        Estado
                    </label>

                    <select id="sector_status"
                            name="status"
                            class="{{ $inputClass }}">
                        <option value="active"
                            @selected(old('status', $status) === 'active')>
                            Activo
                        </option>
                        <option value="inactive"
                            @selected(old('status', $status) === 'inactive')>
                            Inactivo
                        </option>
                    </select>
                </div>

                <!-- COORDINADORES -->
                <div>
                    <label class="{{ $labelClass }}">
                        Coordinadores responsables
                    </label>

                    <p class="mt-1 text-xs text-slate-500">
                        Selecciona los usuarios responsables del sector.
                    </p>

                    <div class="mt-3 max-h-52 space-y-2 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-3">
                        @forelse ($coordinators as $coordinator)
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg bg-white p-3 transition hover:bg-sky-50">
                                <input type="checkbox"
                                       name="coordinatorIds[]"
                                       value="{{ $coordinator->id }}"
                                       @checked(in_array($coordinator->id, (array) old('coordinatorIds', $coordinatorIds)))
                                       class="h-4 w-4 rounded border-slate-300 text-sky-700 focus:ring-sky-500">

                                <div>
                                    <p class="text-sm font-medium text-slate-800">
                                        {{ $coordinator->display_name }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        {{ $coordinator->username }}
                                    </p>
                                </div>
                            </label>
                        @empty
                            <p class="py-3 text-center text-sm text-slate-500">
                                No hay coordinadores activos.
                            </p>
                        @endforelse
                    </div>

                    @error('coordinatorIds')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror

                    @error('coordinatorIds.*')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- ACCIONES -->
            <div class="flex gap-3 border-t border-slate-200 bg-white px-6 py-5">
                @if ($isEditing)
                    <a href="{{ route('admin.sectors') }}"
                       class="flex-1 rounded-xl border border-slate-300 px-4 py-3 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </a>
                @else
                    <button type="button"
                            onclick="closeSectorPanel()"
                            class="flex-1 rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </button>
                @endif

                <button type="submit"
                        class="flex-1 rounded-xl bg-sky-700 px-4 py-3 text-sm font-semibold text-white hover:bg-sky-800">
                    {{ $isEditing ? 'Guardar cambios' : 'Crear sector' }}
                </button>
            </div>
        </form>
    </aside>
</div>

<script>
    const sectorPanel = document.getElementById('sectorPanel');
    const sectorForm = document.getElementById('sectorForm');
    const sectorSearch = document.getElementById('sectorSearch');

    const sectorIsEditing = @json($isEditing);
    const sectorIndexUrl = @json(route('admin.sectors'));

    let sectorPreviousFocus = null;

    function openSectorPanel() {
        sectorPreviousFocus = document.activeElement;

        sectorPanel.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        sectorPanel.querySelector('#sector_name')?.focus();
    }

    function closeSectorPanel() {
        // Si estamos editando, regresar al listado.
        if (sectorIsEditing) {
            window.location.href = sectorIndexUrl;
            return;
        }

        // Si estamos creando, cerrar y limpiar.
        sectorPanel.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');

        // Limpiar también valores recuperados por old().
        sectorForm.reset();

        sectorForm.querySelectorAll('input:not([type="hidden"]), textarea').forEach(field => {
            if (field.type === 'checkbox' || field.type === 'radio') {
                field.checked = false;
            } else if (field.type !== 'submit') {
                field.value = '';
            }
        });

        const status = sectorForm.querySelector('[name="status"]');
        if (status) status.value = 'active';

        sectorPreviousFocus?.focus();
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' &&
            !sectorPanel.classList.contains('hidden')) {
            closeSectorPanel();
        }
    });

    // BUSCADOR
    sectorSearch?.addEventListener('input', function () {
        const query = this.value.toLowerCase().trim();

        document.querySelectorAll('.sector-row').forEach(row => {
            row.classList.toggle(
                'hidden',
                !row.textContent.toLowerCase().includes(query)
            );
        });
    });

    // ABRIR AL EDITAR O TRAS ERROR DE VALIDACIÓN
    @if ($isEditing || $hasErrors)
        document.addEventListener('DOMContentLoaded', openSectorPanel);
    @endif
</script>
@endsection
