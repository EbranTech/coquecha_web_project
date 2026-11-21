<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ShiftConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveShiftRequest;
use App\Http\Requests\UpdateShiftStatusRequest;
use App\Models\Resource;
use App\Models\Sector;
use App\Models\Shift;
use App\Models\User;
use App\Services\ShiftConflictService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['sectorId' => ['nullable', 'integer', 'exists:sectors,id']]);
        $sectorId = $request->old('sectorId', $filters['sectorId'] ?? '');
        $sectorId = filter_var($sectorId, FILTER_VALIDATE_INT) ?: '';
        $sector = $sectorId ? Sector::query()->find($sectorId) : null;

        return view('pages.admin.shifts', [
            'title' => 'Administrar turnos',
            'sectors' => Sector::query()->where('status', 'active')->orderBy('name')->get(),
            'users' => User::query()->with('person')->where('is_active', true)->orderBy('username')->get(),
            'shifts' => Shift::query()->with(['sector', 'resources', 'responsible.person'])->latest('starts_at')->get(),
            'sectorResources' => $sector ? Resource::query()->where('status', 'available')->whereHas('sectors', fn ($query) => $query->whereKey($sector->id))->orderBy('name')->get() : collect(),
            'sectorId' => $sectorId,
            'selectedSectorName' => $sector?->name ?? 'Selecciona un sector para cargar sus recursos.',
            'responsibleUserId' => '',
            'startsAt' => now()->addHour()->format('Y-m-d\TH:i'),
            'endsAt' => now()->addHours(2)->format('Y-m-d\TH:i'),
            'resourceIds' => [],
            'notes' => '',
        ]);
    }

    public function store(SaveShiftRequest $request, ShiftConflictService $service): RedirectResponse
    {
        $data = $request->validated();

        try {
            $service->create([
                'sector_id' => (int) $data['sectorId'],
                'responsible_user_id' => ! empty($data['responsibleUserId']) ? (int) $data['responsibleUserId'] : null,
                'starts_at' => Carbon::parse($data['startsAt'])->toDateTimeString(),
                'ends_at' => Carbon::parse($data['endsAt'])->toDateTimeString(),
                'status' => 'planned',
                'notes' => $data['notes'] ?? null,
            ], array_map('intval', $data['resourceIds']), $request->user()->id);
        } catch (ShiftConflictException) {
            throw ValidationException::withMessages(['resourceIds' => 'Uno o más recursos ya están reservados en ese horario.']);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(['resourceIds' => $exception->errors()['resources'] ?? ['La selección de recursos no es válida.']]);
        }

        return to_route('admin.shifts')->with('success', 'Turno programado correctamente.');
    }

    public function updateStatus(UpdateShiftStatusRequest $request, Shift $shift): RedirectResponse
    {
        $status = $request->validated('status');
        $allowed = match ($shift->status) {
            'planned' => ['in_progress', 'cancelled'],
            'in_progress' => ['completed', 'cancelled'],
            default => [],
        };

        if (! in_array($status, $allowed, true)) {
            throw ValidationException::withMessages(['status' => 'La transición de estado solicitada no es válida.']);
        }

        $shift->update(['status' => $status]);

        return to_route('admin.shifts')->with('success', 'Estado del turno actualizado.');
    }
}
