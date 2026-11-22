
@extends('layouts.app')

@section('content')
@php
    $input = 'mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-sky-500 focus:ring-sky-500';
    $label = 'block text-sm font-semibold text-slate-700';
    $selectedSectorId = old('sectorId', $sectorId);
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-sm font-medium text-sky-700">Planificación operativa</p>
            <h1 class="mt-1 text-3xl font-bold text-slate-900">Turnos de distribución</h1>
            <p class="mt-2 text-slate-500">Administra los horarios y recursos de distribución.</p>
        </div>
        <button type="button" onclick="openShiftPanel()"
            class="rounded-xl bg-sky-700 px-5 py-3 text-sm font-semibold text-white hover:bg-sky-800">
            + Programar turno
        </button>
    </div>

    @if(session('success'))
        <div class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error') || $errors->has('status'))
        <div class="rounded-xl bg-red-50 p-4 text-sm text-red-700">
            {{ session('error') ?: $errors->first('status') }}
        </div>
    @endif

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach([
            ['Total', $shifts->count(), 'text-slate-900'],
            ['Planeados', $shifts->where('status', 'planned')->count(), 'text-sky-700'],
            ['En curso', $shifts->where('status', 'in_progress')->count(), 'text-amber-600'],
            ['Completados', $shifts->where('status', 'completed')->count(), 'text-emerald-600']
        ] as [$title, $count, $color])
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">{{ $title }}</p>
                <p class="mt-2 text-3xl font-bold {{ $color }}">{{ $count }}</p>
            </div>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-5">
            <h2 class="text-lg font-semibold text-slate-900">Turnos registrados</h2>
            <input id="shiftSearch" type="search" placeholder="Buscar turno..."
                class="w-full rounded-xl border border-slate-300 px-4 py-2 text-sm sm:w-64">
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Sector</th>
                        <th class="px-5 py-4">Horario</th>
                        <th class="px-5 py-4">Recursos</th>
                        <th class="px-5 py-4">Estado</th>
                        <th class="px-5 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($shifts as $shift)
                        @php
                            $statuses = [
                                'planned' => ['Planeado', 'bg-sky-50 text-sky-700'],
                                'in_progress' => ['En curso', 'bg-amber-50 text-amber-700'],
                                'completed' => ['Completado', 'bg-emerald-50 text-emerald-700'],
                                'cancelled' => ['Cancelado', 'bg-red-50 text-red-700']
                            ];
                            [$statusText, $statusClass] = $statuses[$shift->status] ?? [$shift->status, 'bg-slate-100 text-slate-600'];
                        @endphp

                        <tr class="shift-row hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-900">{{ $shift->sector->name }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $shift->responsible?->display_name ?? 'Sin responsable' }}
                                </p>
                            </td>
                            <td class="px-5 py-4 text-slate-600">
                                <p>{{ $shift->starts_at->format('d/m/Y H:i') }}</p>
                                <p class="text-xs text-slate-400">Hasta {{ $shift->ends_at->format('d/m/Y H:i') }}</p>
                            </td>
                            <td class="px-5 py-4 text-slate-600">
                                {{ $shift->resources->pluck('name')->join(', ') ?: '—' }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                                    {{ $statusText }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    @if($shift->status === 'planned')
                                        <form method="POST" action="{{ route('admin.shifts.status', $shift) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="in_progress">
                                            <button class="rounded-lg border border-sky-200 px-3 py-2 text-xs font-semibold text-sky-700">
                                                Iniciar
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.shifts.status', $shift) }}"
                                              onsubmit="return confirm('¿Cancelar este turno?')">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="cancelled">
                                            <button class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600">
                                                Cancelar
                                            </button>
                                        </form>
                                    @elseif($shift->status === 'in_progress')
                                        <form method="POST" action="{{ route('admin.shifts.status', $shift) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="completed">
                                            <button class="rounded-lg border border-emerald-200 px-3 py-2 text-xs font-semibold text-emerald-700">
                                                Completar
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-slate-500">
                                No hay turnos programados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="shiftPanel" class="fixed inset-0 z-[100] hidden" role="dialog"
     aria-modal="true" aria-labelledby="shiftPanelTitle">

    <div class="absolute inset-0 bg-slate-950/50" onclick="closeShiftPanel()"></div>

    <div class="absolute inset-y-0 right-0 flex w-full max-w-xl flex-col bg-white shadow-2xl">

        <div class="flex items-center justify-between border-b border-slate-200 p-5">
            <div>
                <h2 id="shiftPanelTitle" class="text-xl font-bold">Programar turno</h2>
                <p class="mt-1 text-sm text-slate-500">Selecciona el sector, horario y recursos.</p>
            </div>
            <button type="button" onclick="closeShiftPanel()"
                class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" aria-label="Cerrar">
                ✕
            </button>
        </div>

        <div class="min-h-0 flex-1 space-y-5 overflow-y-auto p-5">

            <form id="sectorForm" method="GET" action="{{ route('admin.shifts') }}">
                <label for="sectorIdSelect" class="{{ $label }}">Sector de distribución</label>
                <select id="sectorIdSelect" name="sectorId" class="{{ $input }}" required
                        onchange="this.form.submit()">
                    <option value="">Selecciona un sector</option>
                    @foreach($sectors as $sector)
                        <option value="{{ $sector->id }}"
                            @selected((string) $selectedSectorId === (string) $sector->id)>
                            {{ $sector->name }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">
                    Al seleccionar un sector se cargarán sus recursos automáticamente.
                </p>
            </form>

            <form id="shiftForm" method="POST" action="{{ route('admin.shifts.store') }}"
                  class="space-y-5">
                @csrf

                <input type="hidden" name="sectorId" value="{{ $selectedSectorId }}">

                <div>
                    <label for="responsibleUserId" class="{{ $label }}">Responsable</label>
                    <select id="responsibleUserId" name="responsibleUserId" class="{{ $input }}">
                        <option value="">Sin asignar</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}"
                                @selected((string) old('responsibleUserId', $responsibleUserId) === (string) $user->id)>
                                {{ $user->display_name }} ({{ $user->username }})
                            </option>
                        @endforeach
                    </select>
                    @error('responsibleUserId')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="startsAt" class="{{ $label }}">Fecha y hora de inicio</label>
                        <input id="startsAt" name="startsAt" type="datetime-local" required
                            value="{{ old('startsAt', $startsAt) }}" class="{{ $input }}">
                        @error('startsAt')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="endsAt" class="{{ $label }}">Fecha y hora de fin</label>
                        <input id="endsAt" name="endsAt" type="datetime-local" required
                            value="{{ old('endsAt', $endsAt) }}" class="{{ $input }}">
                        @error('endsAt')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="{{ $label }}">Recursos del sector</label>
                    <div class="mt-2 max-h-52 space-y-2 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-3">
                        @forelse($sectorResources as $resource)
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg bg-white p-3 text-sm">
                                <input type="checkbox" name="resourceIds[]" value="{{ $resource->id }}"
                                    @checked(in_array($resource->id, (array) old('resourceIds', $resourceIds)))
                                    class="rounded border-slate-300 text-sky-700">
                                <span>
                                    <span class="font-medium text-slate-800">{{ $resource->name }}</span>
                                    <span class="text-slate-500">({{ $resource->resource_type }})</span>
                                </span>
                            </label>
                        @empty
                            <p class="py-3 text-center text-sm text-slate-500">
                                {{ $selectedSectorId ? 'Este sector no tiene recursos disponibles.' : 'Selecciona un sector para ver sus recursos.' }}
                            </p>
                        @endforelse
                    </div>
                    @error('sectorId')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    @error('resourceIds')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    @error('resourceIds.*')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="notes" class="{{ $label }}">Notas</label>
                    <textarea id="notes" name="notes" rows="3"
                        placeholder="Indicaciones adicionales..."
                        class="{{ $input }}">{{ old('notes', $notes) }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </form>
        </div>

        <div class="flex gap-3 border-t border-slate-200 p-5">
            <button type="button" onclick="closeShiftPanel()"
                class="flex-1 rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700">
                Cancelar
            </button>
            <button type="submit" form="shiftForm" @disabled(!$selectedSectorId)
                class="flex-1 rounded-xl bg-sky-700 px-4 py-3 text-sm font-semibold text-white hover:bg-sky-800 disabled:cursor-not-allowed disabled:opacity-50">
                Programar turno
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const panel = document.getElementById('shiftPanel');
    const form = document.getElementById('shiftForm');
    const start = document.getElementById('startsAt');
    const end = document.getElementById('endsAt');
    const indexUrl = @json(route('admin.shifts'));

    const formatDate = date => {
        const pad = n => String(n).padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
    };

    const minStart = () => {
        const date = new Date();
        date.setSeconds(0, 0);
        date.setMinutes(date.getMinutes() + 1);
        return formatDate(date);
    };

    const updateDates = () => {
        start.min = minStart();
        const base = start.value && start.value >= start.min
            ? start.value : start.min;
        const date = new Date(base);
        date.setMinutes(date.getMinutes() + 1);
        end.min = formatDate(date);
    };

    window.openShiftPanel = () => {
        panel.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        updateDates();
    };

    window.closeShiftPanel = () => {
        window.location.href = indexUrl;
    };

    start.addEventListener('change', () => {
        updateDates();
        if (end.value && end.value < end.min) end.value = '';
    });

    form.addEventListener('submit', event => {
        updateDates();

        if (start.value < start.min || end.value < end.min) {
            event.preventDefault();
            alert('El inicio debe ser futuro y el fin posterior al inicio.');
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !panel.classList.contains('hidden')) {
            closeShiftPanel();
        }
    });

    document.getElementById('shiftSearch').addEventListener('input', event => {
        const query = event.target.value.toLowerCase();
        document.querySelectorAll('.shift-row').forEach(row => {
            row.classList.toggle('hidden', !row.textContent.toLowerCase().includes(query));
        });
    });

    @if($selectedSectorId || $errors->any())
        openShiftPanel();
    @endif

    updateDates();
});
</script>
@endsection
