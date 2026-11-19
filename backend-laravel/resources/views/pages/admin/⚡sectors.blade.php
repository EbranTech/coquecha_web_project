<?php

use App\Models\Role;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Administrar sectores')] class extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public string $location_description = '';

    public string $status = 'active';

    /** @var list<int> */
    public array $coordinatorIds = [];

    public function save(): void
    {
        $this->ensureAdmin();

        $data = $this->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('sectors', 'name')->ignore($this->editingId)],
            'description' => ['nullable', 'string', 'max:2000'],
            'location_description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'coordinatorIds' => ['array'],
            'coordinatorIds.*' => ['integer', Rule::exists('user_accounts', 'id')],
        ]);

        $coordinatorIds = array_map('intval', $data['coordinatorIds']);
        $validCoordinators = User::query()
            ->whereKey($coordinatorIds)
            ->whereHas('role', fn ($query) => $query->where('name', Role::COORDINATOR))
            ->count();

        if ($validCoordinators !== count(array_unique($coordinatorIds))) {
            throw ValidationException::withMessages(['coordinatorIds' => 'Solo se pueden asignar usuarios con rol de coordinador.']);
        }

        unset($data['coordinatorIds']);
        $sector = Sector::query()->updateOrCreate(['id' => $this->editingId], $data);
        $sector->responsibleUsers()->sync($coordinatorIds);
        session()->flash('success', $this->editingId ? 'Sector actualizado.' : 'Sector creado.');
        $this->resetForm();
    }

    public function edit(int $sectorId): void
    {
        $this->ensureAdmin();
        $sector = Sector::query()->findOrFail($sectorId);
        $this->editingId = $sector->id;
        $this->name = $sector->name;
        $this->description = $sector->description ?? '';
        $this->location_description = $sector->location_description ?? '';
        $this->status = $sector->status;
        $this->coordinatorIds = $sector->responsibleUsers()->pluck('user_accounts.id')->all();
    }

    public function delete(int $sectorId): void
    {
        $this->ensureAdmin();
        $sector = Sector::query()->findOrFail($sectorId);

        if ($sector->shifts()->exists() || $sector->reports()->exists() || $sector->serviceInterruptions()->exists()) {
            session()->flash('error', 'No se puede eliminar un sector que tiene turnos, reportes o interrupciones. Márcalo como inactivo para conservar el historial.');

            return;
        }

        $sector->delete();
        session()->flash('success', 'Sector eliminado.');

        if ($this->editingId === $sectorId) {
            $this->resetForm();
        }
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'name', 'description', 'location_description', 'coordinatorIds');
        $this->status = 'active';
        $this->resetValidation();
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->is_active && auth()->user()?->hasRole(Role::ADMIN), 403);
    }

    public function render()
    {
        return $this->view([
            'sectors' => Sector::query()->orderBy('name')->get(),
            'coordinators' => User::query()->with('person')->where('is_active', true)->whereHas('role', fn ($query) => $query->where('name', Role::COORDINATOR))->orderBy('username')->get(),
        ]);
    }
};
?>

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
                        <tr wire:key="sector-{{ $sector->id }}"><td class="px-4 py-3 font-medium">{{ $sector->name }}<p class="mt-1 font-normal text-slate-500">{{ $sector->description }}</p></td><td class="px-4 py-3 text-slate-600">{{ $sector->location_description ?: '—' }}</td><td class="px-4 py-3"><span class="rounded-full px-2 py-1 text-xs {{ $sector->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">{{ $sector->status === 'active' ? 'Activo' : 'Inactivo' }}</span></td><td class="space-x-2 px-4 py-3 text-right"><button class="text-sky-700 hover:underline" wire:click="edit({{ $sector->id }})" type="button">Editar</button><button class="text-red-700 hover:underline" wire:click="delete({{ $sector->id }})" wire:confirm="¿Eliminar este sector?" type="button">Eliminar</button></td></tr>
                    @empty
                        <tr><td class="px-4 py-6 text-slate-500" colspan="4">Aún no hay sectores registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <aside class="h-fit rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <h2 class="text-lg font-semibold">{{ $editingId ? 'Editar sector' : 'Nuevo sector' }}</h2>
        <form class="mt-4 space-y-4" wire:submit="save">
            <div><label class="block text-sm font-medium">Nombre</label><input class="mt-1 w-full rounded border-slate-300" wire:model="name"><p class="mt-1 text-sm text-red-600">@error('name') {{ $message }} @enderror</p></div>
            <div><label class="block text-sm font-medium">Descripción</label><textarea class="mt-1 w-full rounded border-slate-300" rows="3" wire:model="description"></textarea><p class="mt-1 text-sm text-red-600">@error('description') {{ $message }} @enderror</p></div>
            <div><label class="block text-sm font-medium">Ubicación</label><input class="mt-1 w-full rounded border-slate-300" wire:model="location_description"><p class="mt-1 text-sm text-red-600">@error('location_description') {{ $message }} @enderror</p></div>
            <div><label class="block text-sm font-medium">Estado</label><select class="mt-1 w-full rounded border-slate-300" wire:model="status"><option value="active">Activo</option><option value="inactive">Inactivo</option></select></div>
            <div><p class="block text-sm font-medium">Coordinadores responsables</p><div class="mt-1 space-y-2 rounded border border-slate-300 p-3">@forelse($coordinators as $coordinator)<label class="flex items-center gap-2 text-sm"><input type="checkbox" value="{{ $coordinator->id }}" wire:model="coordinatorIds">{{ $coordinator->display_name }} ({{ $coordinator->username }})</label>@empty<p class="text-sm text-slate-500">No hay coordinadores activos.</p>@endforelse</div><p class="mt-1 text-sm text-red-600">@error('coordinatorIds') {{ $message }} @enderror</p></div>
            <div class="flex gap-3"><button class="rounded bg-sky-700 px-4 py-2 font-medium text-white hover:bg-sky-800" type="submit" wire:loading.attr="disabled">{{ $editingId ? 'Guardar cambios' : 'Crear sector' }}</button>@if ($editingId)<button class="text-sm text-slate-600 hover:underline" wire:click="cancel" type="button">Cancelar</button>@endif</div>
        </form>
    </aside>
</div>
