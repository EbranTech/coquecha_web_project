<?php

use App\Exceptions\ShiftConflictException;
use App\Models\Resource;
use App\Models\Role;
use App\Models\Sector;
use App\Models\Shift;
use App\Models\User;
use App\Services\ShiftConflictService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Administrar turnos')] class extends Component
{
    public string $sectorId = '';
    public string $responsibleUserId = '';
    public string $startsAt = '';
    public string $endsAt = '';
    public string $notes = '';

    /** @var array<int, int> */
    public array $resourceIds = [];

    public function mount(): void
    {
        $this->startsAt = now()->addHour()->format('Y-m-d\\TH:i');
        $this->endsAt = now()->addHours(2)->format('Y-m-d\\TH:i');
    }

    #[Computed]
    public function sectorResources()
    {
        if (! $this->sectorId) {
            return collect();
        }

        return Resource::query()
            ->where('status', 'available')
            ->whereHas('sectors', fn ($query) => $query->whereKey($this->sectorId))
            ->orderBy('name')->get();
    }

    public function updatedSectorId(): void
    {
        $this->resourceIds = [];
        unset($this->sectorResources);
    }

    public function save(): void
    {
        $this->ensureAdmin();
        $data = $this->validate([
            'sectorId' => ['required', 'integer', Rule::exists('sectors', 'id')->where('status', 'active')],
            'responsibleUserId' => ['nullable', 'integer', Rule::exists('user_accounts', 'id')],
            'startsAt' => ['required', 'date'],
            'endsAt' => ['required', 'date', 'after:startsAt'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'resourceIds' => ['required', 'array', 'min:1'],
            'resourceIds.*' => ['integer', Rule::exists('resources', 'id')],
        ]);

        try {
            app(ShiftConflictService::class)->create([
                'sector_id' => (int) $data['sectorId'],
                'responsible_user_id' => $data['responsibleUserId'] ? (int) $data['responsibleUserId'] : null,
                'starts_at' => $data['startsAt'], 'ends_at' => $data['endsAt'],
                'status' => 'planned', 'notes' => $data['notes'] ?: null,
            ], array_map('intval', $data['resourceIds']), auth()->id());
        } catch (ShiftConflictException) {
            $this->addError('resourceIds', 'Uno o más recursos ya están reservados en ese horario.');
            return;
        }

        session()->flash('success', 'Turno programado correctamente.');
        $this->resetForm();
    }

    public function updateStatus(int $shiftId, string $status): void
    {
        $this->ensureAdmin();
        abort_unless(in_array($status, ['in_progress', 'completed', 'cancelled'], true), 422);
        Shift::query()->findOrFail($shiftId)->update(['status' => $status]);
        session()->flash('success', 'Estado del turno actualizado.');
    }

    public function cancel(): void { $this->resetForm(); }
    private function resetForm(): void { $this->reset('sectorId', 'responsibleUserId', 'notes', 'resourceIds'); $this->startsAt = now()->addHour()->format('Y-m-d\\TH:i'); $this->endsAt = now()->addHours(2)->format('Y-m-d\\TH:i'); $this->resetValidation(); }
    private function ensureAdmin(): void { abort_unless(auth()->user()?->is_active && auth()->user()?->hasRole(Role::ADMIN), 403); }
    public function render() { return $this->view(['sectors' => Sector::query()->where('status', 'active')->orderBy('name')->get(), 'users' => User::query()->with('person')->where('is_active', true)->orderBy('username')->get(), 'shifts' => Shift::query()->with(['sector', 'resources', 'responsible.person'])->latest('starts_at')->get()]); }
};
?>

<div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_25rem]">
 <section><p class="text-sm font-medium text-sky-700">Planificación operativa</p><h1 class="mt-1 text-3xl font-bold">Turnos de distribución</h1><p class="mt-2 text-slate-600">Programa el uso de recursos sin permitir horarios superpuestos.</p>
 @if (session('success'))<p class="mt-5 rounded bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</p>@endif
 <div class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200"><table class="min-w-full divide-y divide-slate-200 text-left text-sm"><thead class="bg-slate-50 text-slate-600"><tr><th class="px-4 py-3">Sector y horario</th><th class="px-4 py-3">Recursos</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3">Acciones</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse ($shifts as $shift)<tr wire:key="shift-{{ $shift->id }}"><td class="px-4 py-3 font-medium">{{ $shift->sector->name }}<p class="mt-1 font-normal text-slate-500">{{ $shift->starts_at->format('d/m/Y H:i') }} – {{ $shift->ends_at->format('H:i') }}@if($shift->responsible) · {{ $shift->responsible->display_name }}@endif</p></td><td class="px-4 py-3 text-slate-600">{{ $shift->resources->pluck('name')->join(', ') }}</td><td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2 py-1 text-xs text-slate-700">{{ ['planned' => 'Planeado', 'in_progress' => 'En curso', 'completed' => 'Completado', 'cancelled' => 'Cancelado'][$shift->status] }}</span></td><td class="space-x-2 px-4 py-3 text-right">@if($shift->status === 'planned')<button class="text-sky-700 hover:underline" wire:click="updateStatus({{ $shift->id }}, 'in_progress')" type="button">Iniciar</button><button class="text-red-700 hover:underline" wire:click="updateStatus({{ $shift->id }}, 'cancelled')" type="button">Cancelar</button>@elseif($shift->status === 'in_progress')<button class="text-emerald-700 hover:underline" wire:click="updateStatus({{ $shift->id }}, 'completed')" type="button">Completar</button>@endif</td></tr>@empty<tr><td class="px-4 py-6 text-slate-500" colspan="4">Aún no hay turnos programados.</td></tr>@endforelse</tbody></table></div></section>
 <aside class="h-fit rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><h2 class="text-lg font-semibold">Programar turno</h2><form class="mt-4 space-y-4" wire:submit="save"><div><label class="block text-sm font-medium">Sector</label><select class="mt-1 w-full rounded border-slate-300" wire:model.live="sectorId"><option value="">Selecciona un sector</option>@foreach($sectors as $sector)<option value="{{ $sector->id }}">{{ $sector->name }}</option>@endforeach</select><p class="mt-1 text-sm text-red-600">@error('sectorId') {{ $message }} @enderror</p></div><div><label class="block text-sm font-medium">Responsable</label><select class="mt-1 w-full rounded border-slate-300" wire:model="responsibleUserId"><option value="">Sin asignar</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->display_name }} ({{ $user->username }})</option>@endforeach</select><p class="mt-1 text-sm text-red-600">@error('responsibleUserId') {{ $message }} @enderror</p></div><div class="grid grid-cols-2 gap-3"><div><label class="block text-sm font-medium">Inicio</label><input class="mt-1 w-full rounded border-slate-300" type="datetime-local" wire:model="startsAt"><p class="mt-1 text-sm text-red-600">@error('startsAt') {{ $message }} @enderror</p></div><div><label class="block text-sm font-medium">Fin</label><input class="mt-1 w-full rounded border-slate-300" type="datetime-local" wire:model="endsAt"><p class="mt-1 text-sm text-red-600">@error('endsAt') {{ $message }} @enderror</p></div></div><div><label class="block text-sm font-medium">Recursos disponibles del sector</label><div class="mt-1 max-h-36 space-y-2 overflow-auto rounded border border-slate-300 p-3">@forelse($this->sectorResources as $resource)<label class="flex items-center gap-2 text-sm"><input type="checkbox" value="{{ $resource->id }}" wire:model="resourceIds">{{ $resource->name }} <span class="text-slate-500">({{ $resource->resource_type }})</span></label>@empty<p class="text-sm text-slate-500">Selecciona un sector con recursos disponibles.</p>@endforelse</div><p class="mt-1 text-sm text-red-600">@error('resourceIds') {{ $message }} @enderror @error('resourceIds.*') {{ $message }} @enderror</p></div><div><label class="block text-sm font-medium">Notas</label><textarea class="mt-1 w-full rounded border-slate-300" rows="2" wire:model="notes"></textarea><p class="mt-1 text-sm text-red-600">@error('notes') {{ $message }} @enderror</p></div><div class="flex gap-3"><button class="rounded bg-sky-700 px-4 py-2 font-medium text-white hover:bg-sky-800" type="submit" wire:loading.attr="disabled">Programar</button><button class="text-sm text-slate-600 hover:underline" wire:click="cancel" type="button">Limpiar</button></div></form></aside>
</div>
