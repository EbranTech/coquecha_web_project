
@extends('layouts.app')

@section('content')

@php
    $totalShifts = $shifts->count();
    $plannedShifts = $shifts->where('status', 'planned')->count();
    $progressShifts = $shifts->where('status', 'in_progress')->count();
    $completedShifts = $shifts->where('status', 'completed')->count();

    $inputClass = 'mt-2 w-full min-w-0 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100';
    $labelClass = 'block text-sm font-semibold text-slate-700';
@endphp

<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <p class="text-sm font-semibold text-sky-700">
                Planificación operativa
            </p>

            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">
                Turnos de distribución
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                Programa y administra los horarios de distribución comunitaria.
            </p>
        </div>

        <button type="button"
                onclick="openShiftPanel()"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-700 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-800">

            <svg class="h-5 w-5" fill="none" stroke="currentColor"
                 viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M12 4v16m8-8H4"/>
            </svg>

            Programar turno
        </button>
    </div>

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

    @if ($errors->has('status'))
        <div role="alert"
             class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-800">
            {{ $errors->first('status') }}
        </div>
    @endif

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">
                Total de turnos
            </p>
            <p class="mt-3 text-3xl font-bold text-slate-900">
                {{ $totalShifts }}
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">
                Planeados
            </p>
            <p class="mt-3 text-3xl font-bold text-sky-600">
                {{ $plannedShifts }}
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">
                En curso
            </p>
            <p class="mt-3 text-3xl font-bold text-amber-600">
                {{ $progressShifts }}
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">
                Completados
            </p>
            <p class="mt-3 text-3xl font-bold text-emerald-600">
                {{ $completedShifts }}
            </p>
        </div>

    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">

            <div>
                <h2 class="text-lg font-semibold text-slate-900">
                    Turnos registrados
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Consulta los horarios y controla el estado de distribución.
                </p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">

                <input id="shiftSearch"
                       type="search"
                       placeholder="Buscar turno..."
                       aria-label="Buscar turno"
                       class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 sm:w-56">

                <select id="shiftStatusFilter"
                        aria-label="Filtrar por estado"
                        class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 outline-none focus:border-sky-500">

                    <option value="">Todos los estados</option>
                    <option value="planned">Planeados</option>
                    <option value="in_progress">En curso</option>
                    <option value="completed">Completados</option>
                    <option value="cancelled">Cancelados</option>
                </select>

            </div>
        </div>

        <div class="overflow-x-auto">

            <table class="min-w-full text-left text-sm">

                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-4">Sector</th>
                        <th class="px-6 py-4">Horario</th>
                        <th class="px-6 py-4">Recursos</th>
                        <th class="px-6 py-4">Estado</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">

                    @forelse ($shifts as $shift)

                        <tr class="shift-row transition hover:bg-slate-50"
                            data-status="{{ $shift->status }}">

                            <td class="px-6 py-4">

                                <p class="font-semibold text-slate-900">
                                    {{ $shift->sector->name }}
                                </p>

                                @if ($shift->responsible)
                                    <p class="mt-1 text-xs text-slate-500">
                                        Responsable:
                                        {{ $shift->responsible->display_name }}
                                    </p>
                                @else
                                    <p class="mt-1 text-xs text-slate-400">
                                        Sin responsable asignado
                                    </p>
                                @endif

                            </td>

                            <td class="px-6 py-4">

                                <p class="font-medium text-slate-800">
                                    {{ $shift->starts_at->format('d/m/Y') }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $shift->starts_at->format('H:i') }}
                                    –
                                    {{ $shift->ends_at->format('d/m/Y H:i') }}
                                </p>

                            </td>

                            <td class="px-6 py-4">

                                @forelse ($shift->resources as $resource)
                                    <span class="mb-1 mr-1 inline-flex rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                        {{ $resource->name }}
                                    </span>
                                @empty
                                    <span class="text-xs text-slate-400">
                                        Sin recursos
                                    </span>
                                @endforelse

                            </td>

                            <td class="px-6 py-4">

                                @switch($shift->status)

                                    @case('planned')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-sky-500"></span>
                                            Planeado
                                        </span>
                                        @break

                                    @case('in_progress')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                            En curso
                                        </span>
                                        @break

                                    @case('completed')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Completado
                                        </span>
                                        @break

                                    @case('cancelled')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                            Cancelado
                                        </span>
                                        @break

                                @endswitch

                            </td>

                            <td class="px-6 py-4">

                                <div class="flex items-center justify-end gap-2">

                                    @if ($shift->status === 'planned')

                                        <form method="POST"
                                              action="{{ route('admin.shifts.status', $shift) }}">
                                            @csrf
                                            @method('PATCH')

                                            <input type="hidden"
                                                   name="status"
                                                   value="in_progress">

                                            <button type="submit"
                                                    class="rounded-lg border border-sky-200 px-3 py-2 text-xs font-semibold text-sky-700 transition hover:bg-sky-50">
                                                Iniciar
                                            </button>
                                        </form>

                                        <form method="POST"
                                              action="{{ route('admin.shifts.status', $shift) }}"
                                              onsubmit="return confirm('¿Cancelar este turno?')">
                                            @csrf
                                            @method('PATCH')

                                            <input type="hidden"
                                                   name="status"
                                                   value="cancelled">

                                            <button type="submit"
                                                    class="rounded-lg border border-red-100 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50">
                                                Cancelar
                                            </button>
                                        </form>

                                    @elseif ($shift->status === 'in_progress')

                                        <form method="POST"
                                              action="{{ route('admin.shifts.status', $shift) }}">
                                            @csrf
                                            @method('PATCH')

                                            <input type="hidden"
                                                   name="status"
                                                   value="completed">

                                            <button type="submit"
                                                    class="rounded-lg border border-emerald-200 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50">
                                                Completar
                                            </button>
                                        </form>

                                    @else
                                        <span class="text-xs text-slate-400">
                                            Sin acciones
                                        </span>
                                    @endif

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">

                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">

                                    <svg class="h-7 w-7 text-slate-400"
                                         fill="none"
                                         stroke="currentColor"
                                         viewBox="0 0 24 24"
                                         stroke-width="1.5">
                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 012 2v13a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/>
                                    </svg>

                                </div>

                                <h3 class="mt-4 text-base font-semibold text-slate-800">
                                    No hay turnos programados
                                </h3>

                                <p class="mt-1 text-sm text-slate-500">
                                    Comienza programando un turno de distribución.
                                </p>

                            </td>
                        </tr>

                    @endforelse

                </tbody>
            </table>

        </div>

        <div class="border-t border-slate-200 bg-slate-50 px-6 py-4">
            <p class="text-xs text-slate-500">
                Mostrando {{ $totalShifts }} turnos
            </p>
        </div>

    </div>
</div>

<div id="shiftPanel"
     class="fixed inset-0 z-[100] hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="shiftPanelTitle">

    <div onclick="closeShiftPanel()"
         class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm">
    </div>

    <aside class="absolute inset-y-0 right-0 flex w-full max-w-xl flex-col bg-white shadow-2xl">

        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">

            <div>
                <h2 id="shiftPanelTitle" class="text-xl font-bold text-slate-900">
                    Programar turno
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Configura el horario y los recursos de distribución.
                </p>
            </div>

            <button type="button"
                    onclick="closeShiftPanel()"
                    aria-label="Cerrar formulario"
                    class="rounded-lg p-2 text-slate-500 hover:bg-slate-100">
                ✕
            </button>

        </div>

        <div class="min-h-0 flex-1 space-y-6 overflow-y-auto px-6 py-6">

            <form method="GET"
                  action="{{ route('admin.shifts') }}"
                  class="rounded-xl border border-sky-100 bg-sky-50 p-4">

                <label for="sector-filter" class="{{ $labelClass }}">
                    Sector de distribución
                </label>

                <p class="mt-1 text-xs text-slate-500">
                    Selecciona un sector para cargar sus recursos.
                </p>

                <div class="mt-3 flex flex-col gap-2 sm:flex-row">

                    <select id="sector-filter"
                            name="sectorId"
                            required
                            class="w-full min-w-0 rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-sky-500">

                        <option value="">Selecciona un sector</option>

                        @foreach ($sectors as $sector)
                            <option value="{{ $sector->id }}"
                                @selected((string) $sectorId === (string) $sector->id)>
                                {{ $sector->name }}
                            </option>
                        @endforeach

                    </select>

                    <button type="submit"
                            class="shrink-0 rounded-xl bg-sky-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-800">
                        Cargar
                    </button>

                </div>

            </form>

            <form id="shiftForm"
                  method="POST"
                  action="{{ route('admin.shifts.store') }}"
                  class="space-y-5">

                @csrf

                <div>
                    <label class="{{ $labelClass }}">
                        Sector seleccionado
                    </label>

                    <input type="hidden"
                           name="sectorId"
                           value="{{ $sectorId }}">

                    <div class="mt-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                        {{ $selectedSectorName ?: 'Ningún sector seleccionado' }}
                    </div>

                    @error('sectorId')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="responsibleUserId" class="{{ $labelClass }}">
                        Responsable
                    </label>

                    <select id="responsibleUserId"
                            name="responsibleUserId"
                            class="{{ $inputClass }}">

                        <option value="">Sin asignar</option>

                        @foreach ($users as $user)
                            <option value="{{ $user->id }}"
                                @selected((string) old('responsibleUserId', $responsibleUserId) === (string) $user->id)>
                                {{ $user->display_name }}
                                ({{ $user->username }})
                            </option>
                        @endforeach

                    </select>

                    @error('responsibleUserId')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">

                    <div class="mb-4">
                        <h3 class="text-sm font-semibold text-slate-800">
                            Horario de distribución
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Selecciona fechas futuras y un horario válido.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                        <div>
                            <label for="startsAt" class="{{ $labelClass }}">
                                Fecha y hora de inicio
                            </label>

                            <input id="startsAt"
                                   type="datetime-local"
                                   name="startsAt"
                                   step="60"
                                   required
                                   value="{{ old('startsAt', $startsAt) }}"
                                   class="{{ $inputClass }}">

                            @error('startsAt')
                                <p class="mt-1 text-xs text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="endsAt" class="{{ $labelClass }}">
                                Fecha y hora de fin
                            </label>

                            <input id="endsAt"
                                   type="datetime-local"
                                   name="endsAt"
                                   step="60"
                                   required
                                   value="{{ old('endsAt', $endsAt) }}"
                                   class="{{ $inputClass }}">

                            @error('endsAt')
                                <p class="mt-1 text-xs text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                    </div>

                    <p id="dateValidationMessage"
                       role="alert"
                       class="mt-3 hidden text-xs font-medium text-red-600">
                    </p>

                    <div class="mt-4 rounded-lg border border-sky-100 bg-sky-50 p-3">
                        <p class="text-xs leading-relaxed text-sky-800">
                            El inicio debe ser igual o posterior al momento actual.
                            El fin debe ser posterior al inicio.
                            No se permiten horarios superpuestos para un mismo recurso.
                        </p>
                    </div>

                </div>

                <div>
                    <label class="{{ $labelClass }}">
                        Recursos disponibles del sector
                    </label>

                    <p class="mt-1 text-xs text-slate-500">
                        Selecciona los recursos que se utilizarán durante el turno.
                    </p>

                    <div class="mt-3 max-h-56 space-y-2 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-3">

                        @forelse ($sectorResources as $resource)

                            <label class="flex cursor-pointer items-center gap-3 rounded-lg bg-white p-3 transition hover:bg-sky-50">

                                <input type="checkbox"
                                       name="resourceIds[]"
                                       value="{{ $resource->id }}"
                                       @checked(in_array($resource->id, (array) old('resourceIds', $resourceIds)))
                                       class="h-4 w-4 rounded border-slate-300 text-sky-700 focus:ring-sky-500">

                                <div>
                                    <p class="text-sm font-medium text-slate-800">
                                        {{ $resource->name }}
                                    </p>

                                    <p class="text-xs text-slate-500">
                                        {{ $resource->resource_type }}
                                    </p>
                                </div>

                            </label>

                        @empty

                            <p class="py-4 text-center text-sm text-slate-500">
                                Selecciona un sector con recursos disponibles.
                            </p>

                        @endforelse

                    </div>

                    @error('resourceIds')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                    @error('resourceIds.*')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="notes" class="{{ $labelClass }}">
                        Notas adicionales
                    </label>

                    <textarea id="notes"
                              name="notes"
                              rows="3"
                              placeholder="Indicaciones o información adicional del turno..."
                              class="{{ $inputClass }}">{{ old('notes', $notes) }}</textarea>

                    @error('notes')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

            </form>
        </div>
        <div class="flex gap-3 border-t border-slate-200 bg-white px-6 py-5">

            <button type="button"
                    onclick="closeShiftPanel()"
                    class="flex-1 rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Cancelar
            </button>

            <button type="submit"
                    form="shiftForm"
                    class="flex-1 rounded-xl bg-sky-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-sky-800">
                Programar turno
            </button>

        </div>

    </aside>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const panel = document.getElementById('shiftPanel');
    const form = document.getElementById('shiftForm');

    const startInput = document.getElementById('startsAt');
    const endInput = document.getElementById('endsAt');

    const validationMessage = document.getElementById('dateValidationMessage');

    const searchInput = document.getElementById('shiftSearch');
    const statusFilter = document.getElementById('shiftStatusFilter');

    const indexUrl = @json(route('admin.shifts'));
    const hasSector = @json(!empty($sectorId));
    const hasFormErrors = @json(
        $errors->has('sectorId') ||
        $errors->has('responsibleUserId') ||
        $errors->has('startsAt') ||
        $errors->has('endsAt') ||
        $errors->has('resourceIds') ||
        $errors->has('resourceIds.*') ||
        $errors->has('notes')
    );

    let previousFocus = null;

    function formatLocalDate(date) {
        const pad = number => String(number).padStart(2, '0');

        return (
            date.getFullYear() + '-' +
            pad(date.getMonth() + 1) + '-' +
            pad(date.getDate()) + 'T' +
            pad(date.getHours()) + ':' +
            pad(date.getMinutes())
        );
    }

    function getMinimumStart() {
        const now = new Date();


        now.setSeconds(0, 0);
        now.setMinutes(now.getMinutes() + 1);

        return formatLocalDate(now);
    }

    function addMinutes(value, minutes) {
        const date = new Date(value);

        if (Number.isNaN(date.getTime())) {
            return '';
        }

        date.setMinutes(date.getMinutes() + minutes);

        return formatLocalDate(date);
    }

    function showDateError(message) {
        validationMessage.textContent = message;
        validationMessage.classList.remove('hidden');
    }

    function clearDateError() {
        validationMessage.textContent = '';
        validationMessage.classList.add('hidden');

        startInput.setCustomValidity('');
        endInput.setCustomValidity('');
    }

    function updateDateLimits() {

        const minimumStart = getMinimumStart();

        startInput.min = minimumStart;

        if (startInput.value) {
            endInput.min = addMinutes(startInput.value, 1);
        } else {
            endInput.min = addMinutes(minimumStart, 1);
        }

        clearDateError();
    }

    function validateDates() {

        clearDateError();

        const minimumStart = getMinimumStart();

        startInput.min = minimumStart;

        if (!startInput.value) {
            showDateError('Selecciona una fecha de inicio.');
            startInput.setCustomValidity('Selecciona una fecha de inicio.');
            return false;
        }

        if (startInput.value < minimumStart) {
            showDateError('La fecha de inicio no puede ser anterior al momento actual.');
            startInput.setCustomValidity('Selecciona una fecha futura.');
            return false;
        }

        const minimumEnd = addMinutes(startInput.value, 1);
        endInput.min = minimumEnd;

        if (!endInput.value) {
            showDateError('Selecciona una fecha de finalización.');
            endInput.setCustomValidity('Selecciona una fecha de finalización.');
            return false;
        }

        if (endInput.value < minimumEnd) {
            showDateError('La fecha de fin debe ser posterior a la fecha de inicio.');
            endInput.setCustomValidity('La fecha de fin debe ser posterior al inicio.');
            return false;
        }

        return true;
    }

    startInput.addEventListener('change', function () {

        updateDateLimits();

        if (startInput.value && endInput.value) {

            const minimumEnd = addMinutes(startInput.value, 1);

            if (endInput.value < minimumEnd) {
                endInput.value = '';
            }
        }
    });

    endInput.addEventListener('change', function () {
        updateDateLimits();
    });

    startInput.addEventListener('input', clearDateError);
    endInput.addEventListener('input', clearDateError);

    form.addEventListener('submit', function (event) {

        if (!validateDates()) {
            event.preventDefault();

            if (!startInput.validity.valid) {
                startInput.reportValidity();
                startInput.focus();
            } else {
                endInput.reportValidity();
                endInput.focus();
            }

            return;
        }

    });

    window.openShiftPanel = function () {
        previousFocus = document.activeElement;

        panel.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        updateDateLimits();

        document.getElementById('sector-filter')?.focus();
    };

    window.closeShiftPanel = function () {

        if (hasSector) {
            window.location.href = indexUrl;
            return;
        }

        panel.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');

        form.reset();

        startInput.value = '';
        endInput.value = '';

        form.querySelectorAll('input[type="checkbox"]').forEach(input => {
            input.checked = false;
        });

        clearDateError();
        updateDateLimits();

        previousFocus?.focus();
    };

    document.addEventListener('keydown', function (event) {

        if (event.key === 'Escape' &&
            !panel.classList.contains('hidden')) {

            closeShiftPanel();
        }
    });

    function filterShifts() {

        const query = searchInput.value.toLowerCase().trim();
        const status = statusFilter.value;

        document.querySelectorAll('.shift-row').forEach(row => {

            const matchesText =
                row.textContent.toLowerCase().includes(query);

            const matchesStatus =
                !status || row.dataset.status === status;

            row.classList.toggle(
                'hidden',
                !(matchesText && matchesStatus)
            );
        });
    }

    searchInput?.addEventListener('input', filterShifts);
    statusFilter?.addEventListener('change', filterShifts);

    updateDateLimits();
    if (hasSector || hasFormErrors) {
        openShiftPanel();
    }

});
</script>

@endsection
