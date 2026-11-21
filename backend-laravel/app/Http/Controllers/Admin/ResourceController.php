<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveResourceRequest;
use App\Models\Resource as WaterResource;
use App\Models\Sector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ResourceController extends Controller
{
    public function index(): View
    {
        return $this->form();
    }

    public function edit(WaterResource $resource): View
    {
        return $this->form($resource);
    }

    private function form(?WaterResource $record = null): View
    {
        return view('pages.admin.resources', [
            'resources' => WaterResource::query()->orderBy('name')->orderBy('id')->get(),
            'sectors' => Sector::query()->where('status', 'active')->orderBy('name')->get(),
            'title' => 'Administrar recursos',
            'editingId' => $record?->id,
            'name' => $record?->name ?? '',
            'resource_type' => $record?->resource_type ?? '',
            'description' => $record?->description ?? '',
            'capacity' => $record?->capacity ?? '',
            'capacity_unit' => $record?->capacity_unit ?? '',
            'location_description' => $record?->location_description ?? '',
            'status' => $record?->status ?? 'available',
            'sectorIds' => $record?->sectors()->pluck('sectors.id')->all() ?? [],
        ]);
    }

    public function store(SaveResourceRequest $request): RedirectResponse
    {
        return $this->persist($request);
    }

    public function update(SaveResourceRequest $request, WaterResource $resource): RedirectResponse
    {
        return $this->persist($request, $resource);
    }

    private function persist(SaveResourceRequest $request, ?WaterResource $record = null): RedirectResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($data, $record): void {
            $attributes = collect($data)->except('sectorIds')->all();
            $attributes['capacity'] = $attributes['capacity'] ?? null;
            $resource = WaterResource::query()->updateOrCreate(['id' => $record?->id], $attributes);
            $resource->sectors()->sync($data['sectorIds']);
        });

        return to_route('admin.resources')->with('success', $record ? 'Recurso actualizado.' : 'Recurso creado.');
    }

    public function destroy(WaterResource $resource): RedirectResponse
    {
        if ($resource->shifts()->exists()) {
            return to_route('admin.resources')->with('error', 'No se puede eliminar un recurso utilizado por turnos. Márcalo como inactivo para conservar el historial.');
        }

        $resource->delete();

        return to_route('admin.resources')->with('success', 'Recurso eliminado.');
    }
}
