<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveSectorRequest;
use App\Models\Role;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SectorController extends Controller
{
    public function index(): View
    {
        return $this->form();
    }

    public function edit(Sector $sector): View
    {
        return $this->form($sector);
    }

    private function form(?Sector $record = null): View
    {
        return view('pages.admin.sectors', [
            'sectors' => Sector::query()->orderBy('name')->get(),
            'coordinators' => User::query()->with('person')->where('is_active', true)->whereHas('role', fn ($query) => $query->where('name', Role::COORDINATOR))->orderBy('username')->get(),
            'title' => 'Administrar sectores',
            'editingId' => $record?->id,
            'name' => $record?->name ?? '',
            'description' => $record?->description ?? '',
            'location_description' => $record?->location_description ?? '',
            'status' => $record?->status ?? 'active',
            'coordinatorIds' => $record?->responsibleUsers()->pluck('user_accounts.id')->all() ?? [],
        ]);
    }

    public function store(SaveSectorRequest $request): RedirectResponse
    {
        return $this->persist($request);
    }

    public function update(SaveSectorRequest $request, Sector $sector): RedirectResponse
    {
        return $this->persist($request, $sector);
    }

    private function persist(SaveSectorRequest $request, ?Sector $record = null): RedirectResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($data, $record): void {
            $coordinatorIds = array_map('intval', ($data['coordinatorIds'] ?? []));
            $validCoordinators = User::query()
                ->whereKey($coordinatorIds)
                ->whereHas('role', fn ($query) => $query->where('name', Role::COORDINATOR))
                ->count();

            if ($validCoordinators !== count(array_unique($coordinatorIds))) {
                throw ValidationException::withMessages(['coordinatorIds' => 'Solo se pueden asignar usuarios con rol de coordinador.']);
            }

            unset($data['coordinatorIds']);
            $sector = Sector::query()->updateOrCreate(['id' => $record?->id], $data);
            $sector->responsibleUsers()->sync($coordinatorIds);
        });

        return to_route('admin.sectors')->with('success', $record ? 'Sector actualizado.' : 'Sector creado.');
    }

    public function destroy(Sector $sector): RedirectResponse
    {
        if ($sector->shifts()->exists() || $sector->reports()->exists() || $sector->serviceInterruptions()->exists()) {
            return to_route('admin.sectors')->with('error', 'No se puede eliminar un sector que tiene turnos, reportes o interrupciones. Márcalo como inactivo para conservar el historial.');
        }

        $sector->delete();

        return to_route('admin.sectors')->with('success', 'Sector eliminado.');
    }
}
