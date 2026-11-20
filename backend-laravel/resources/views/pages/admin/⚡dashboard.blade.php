<?php

use App\Models\Resource;
use App\Models\Sector;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Resumen administrativo')] class extends Component
{
    public function render()
    {
        return $this->view([
            'activeSectors' => Sector::query()->where('status', 'active')->count(),
            'availableResources' => Resource::query()->where('status', 'available')->count(),
            'resourcesInMaintenance' => Resource::query()->where('status', 'maintenance')->count(),
        ]);
    }
};
?>

<div class="space-y-8">
    <div>
        <p class="text-sm font-medium text-sky-700">Gestión comunitaria del agua</p>
        <h1 class="mt-1 text-3xl font-bold tracking-tight">Resumen administrativo</h1>
        <p class="mt-2 text-slate-600">Controla la información operativa que se utilizará en los demás módulos.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm text-slate-600">Sectores activos</p>
            <p class="mt-2 text-3xl font-bold">{{ $activeSectors }}</p>
        </article>
        <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm text-slate-600">Recursos disponibles</p>
            <p class="mt-2 text-3xl font-bold text-emerald-700">{{ $availableResources }}</p>
        </article>
        <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm text-slate-600">En mantenimiento</p>
            <p class="mt-2 text-3xl font-bold text-amber-700">{{ $resourcesInMaintenance }}</p>
        </article>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <a class="rounded-xl p-6 text-white shadow-md ring-2 ring-slate-900 focus:outline-none focus:ring-4 focus:ring-sky-400" style="background-color: #0f4c5c" href="{{ route('admin.users') }}">
            <h2 class="text-lg font-semibold">Administrar usuarios</h2>
            <p class="mt-1 text-sm" style="color: #e0f2f1">Crear cuentas, roles y asignaciones de sectores.</p>
        </a>
        <a class="rounded-xl bg-sky-700 p-6 text-white shadow-sm hover:bg-sky-800" href="{{ route('admin.sectors') }}">
            <h2 class="text-lg font-semibold">Administrar sectores</h2>
            <p class="mt-1 text-sm text-sky-100">Crear y actualizar las áreas atendidas.</p>
        </a>
        <a class="rounded-xl bg-slate-800 p-6 text-white shadow-sm hover:bg-slate-900" href="{{ route('admin.resources') }}">
            <h2 class="text-lg font-semibold">Administrar recursos</h2>
            <p class="mt-1 text-sm text-slate-200">Registrar capacidad, ubicación y estado operativo.</p>
        </a>
    </div>
</div>
