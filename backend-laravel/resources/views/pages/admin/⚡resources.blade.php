<?php

use App\Models\Resource as WaterResource;
use App\Models\Role;
use App\Models\Sector;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Administrar recursos')] class extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $resource_type = '';

    public string $description = '';

    public string $capacity = '';

    public string $capacity_unit = '';

    public string $location_description = '';

    public string $status = 'available';

    /** @var array<int, int> */
    public array $sectorIds = [];

    public function save(): void
    {
        $this->ensureAdmin();

        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'resource_type' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:2000'],
            'capacity' => ['nullable', 'numeric', 'min:0'],
            'capacity_unit' => ['nullable', 'string', 'max:30'],
            'location_description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['available', 'maintenance', 'inactive'])],
            'sectorIds' => ['required', 'array', 'min:1'],
            'sectorIds.*' => ['integer', Rule::exists('sectors', 'id')],
        ]);

        $resource = WaterResource::query()->updateOrCreate(['id' => $this->editingId], collect($data)->except('sectorIds')->all());
        $resource->sectors()->sync($data['sectorIds']);
        session()->flash('success', $this->editingId ? 'Recurso actualizado.' : 'Recurso creado.');
        $this->resetForm();
    }

    public function edit(int $resourceId): void
    {
        $this->ensureAdmin();
        $resource = WaterResource::query()->findOrFail($resourceId);
        $this->editingId = $resource->id;
        $this->name = $resource->name;
        $this->resource_type = $resource->resource_type;
        $this->description = $resource->description ?? '';
        $this->capacity = $resource->capacity ?? '';
        $this->capacity_unit = $resource->capacity_unit ?? '';
        $this->location_description = $resource->location_description ?? '';
        $this->status = $resource->status;
        $this->sectorIds = $resource->sectors()->pluck('sectors.id')->all();
    }

    public function delete(int $resourceId): void
    {
        $this->ensureAdmin();
        $resource = WaterResource::query()->findOrFail($resourceId);

        if ($resource->shifts()->exists()) {
            session()->flash('error', 'No se puede eliminar un recurso utilizado por turnos. Márcalo como inactivo para conservar el historial.');

            return;
        }

        $resource->delete();
        session()->flash('success', 'Recurso eliminado.');

        if ($this->editingId === $resourceId) {
            $this->resetForm();
        }
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'name', 'resource_type', 'description', 'capacity', 'capacity_unit', 'location_description', 'sectorIds');
        $this->status = 'available';
        $this->resetValidation();
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->is_active && auth()->user()?->hasRole(Role::ADMIN), 403);
    }

    public function render()
    {
        return $this->view([
            'resources' => WaterResource::query()->orderBy('name')->orderBy('id')->get(),
            'sectors' => Sector::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }
};
?>

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
                        <tr wire:key="resource-{{ $resource->id }}"><td class="px-4 py-3 font-medium">{{ $resource->name }}<p class="mt-1 font-normal text-slate-500">{{ $resource->resource_type }} · {{ $resource->location_description ?: 'Sin ubicación' }}</p></td><td class="px-4 py-3 text-slate-600">{{ $resource->capacity !== null ? $resource->capacity.' '.$resource->capacity_unit : '—' }}</td><td class="px-4 py-3"><span class="rounded-full px-2 py-1 text-xs {{ $resource->status === 'available' ? 'bg-emerald-100 text-emerald-800' : ($resource->status === 'maintenance' ? 'bg-amber-100 text-amber-800' : 'bg-slate-200 text-slate-700') }}">{{ $resource->status === 'available' ? 'Disponible' : ($resource->status === 'maintenance' ? 'Mantenimiento' : 'Inactivo') }}</span></td><td class="space-x-2 px-4 py-3 text-right"><button class="text-sky-700 hover:underline" wire:click="edit({{ $resource->id }})" type="button">Editar</button><button class="text-red-700 hover:underline" wire:click="delete({{ $resource->id }})" wire:confirm="¿Eliminar este recurso?" type="button">Eliminar</button></td></tr>
                    @empty
                        <tr><td class="px-4 py-6 text-slate-500" colspan="4">Aún no hay recursos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <aside class="h-fit rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <h2 class="text-lg font-semibold">{{ $editingId ? 'Editar recurso' : 'Nuevo recurso' }}</h2>
        <form class="mt-4 space-y-4" wire:submit="save">
            <div><label class="block text-sm font-medium">Nombre</label><input class="mt-1 w-full rounded border-slate-300" wire:model="name"><p class="mt-1 text-sm text-red-600">@error('name') {{ $message }} @enderror</p></div>
            <div><label class="block text-sm font-medium">Tipo</label><input class="mt-1 w-full rounded border-slate-300" placeholder="Tanque, bomba, pozo..." wire:model="resource_type"><p class="mt-1 text-sm text-red-600">@error('resource_type') {{ $message }} @enderror</p></div>
            <div class="grid grid-cols-2 gap-3"><div><label class="block text-sm font-medium">Capacidad</label><input class="mt-1 w-full rounded border-slate-300" inputmode="decimal" wire:model="capacity"><p class="mt-1 text-sm text-red-600">@error('capacity') {{ $message }} @enderror</p></div><div><label class="block text-sm font-medium">Unidad</label><input class="mt-1 w-full rounded border-slate-300" placeholder="litros" wire:model="capacity_unit"></div></div>
            <div><label class="block text-sm font-medium">Ubicación</label><input class="mt-1 w-full rounded border-slate-300" wire:model="location_description"><p class="mt-1 text-sm text-red-600">@error('location_description') {{ $message }} @enderror</p></div>
            <div><label class="block text-sm font-medium">Sectores donde puede utilizarse</label><select class="mt-1 w-full rounded border-slate-300" multiple size="4" wire:model="sectorIds">@foreach ($sectors as $sector)<option value="{{ $sector->id }}">{{ $sector->name }}</option>@endforeach</select><p class="mt-1 text-xs text-slate-500">Usa Ctrl/Cmd para elegir varios sectores.</p><p class="mt-1 text-sm text-red-600">@error('sectorIds') {{ $message }} @enderror @error('sectorIds.*') {{ $message }} @enderror</p></div>
            <div><label class="block text-sm font-medium">Descripción</label><textarea class="mt-1 w-full rounded border-slate-300" rows="2" wire:model="description"></textarea></div>
            <div><label class="block text-sm font-medium">Estado</label><select class="mt-1 w-full rounded border-slate-300" wire:model="status"><option value="available">Disponible</option><option value="maintenance">En mantenimiento</option><option value="inactive">Inactivo</option></select></div>
            <div class="flex gap-3"><button class="rounded bg-sky-700 px-4 py-2 font-medium text-white hover:bg-sky-800" type="submit" wire:loading.attr="disabled">{{ $editingId ? 'Guardar cambios' : 'Crear recurso' }}</button>@if ($editingId)<button class="text-sm text-slate-600 hover:underline" wire:click="cancel" type="button">Cancelar</button>@endif</div>
        </form>
    </aside>
</div>
