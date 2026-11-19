<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShiftRequest;
use App\Http\Requests\StoreShiftResultRequest;
use App\Http\Requests\UpdateShiftStatusRequest;
use App\Http\Resources\ShiftResource;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Services\ShiftConflictService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ShiftController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'sector_id' => ['nullable', 'integer', 'exists:sectors,id'],
            'status' => ['nullable', 'in:planned,in_progress,completed,cancelled'],
        ]);

        return ShiftResource::collection(
            Shift::query()
                ->with(['sector', 'resources', 'responsible.person', 'result'])
                ->when($filters['sector_id'] ?? null, fn ($query, int $sectorId) => $query->where('sector_id', $sectorId))
                ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
                ->orderBy('starts_at')
                ->get(),
        );
    }

    public function store(StoreShiftRequest $request, ShiftConflictService $shiftConflictService): JsonResponse
    {
        $data = $request->validated();
        $this->ensureCanManageSector($request->user(), (int) $data['sector_id']);

        $shift = $shiftConflictService->create(
            [
                'sector_id' => $data['sector_id'],
                'responsible_user_id' => $data['responsible_user_id'] ?? null,
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
                'status' => 'planned',
                'notes' => $data['notes'] ?? null,
            ],
            $data['resource_ids'],
            $request->user()->id,
        );

        return (new ShiftResource($shift->loadMissing(['sector', 'resources', 'responsible.person'])))
            ->response()
            ->setStatusCode(201);
    }

    public function updateStatus(UpdateShiftStatusRequest $request, Shift $shift): ShiftResource
    {
        $this->ensureCanManageSector($request->user(), $shift->sector_id);
        $status = $request->validated('status');

        if (! $this->isValidTransition($shift->status, $status)) {
            throw ValidationException::withMessages(['status' => ['La transición de estado solicitada no es válida.']]);
        }

        $shift->update(['status' => $status]);

        return new ShiftResource($shift->fresh()->load(['sector', 'resources', 'responsible.person', 'result']));
    }

    public function storeResult(StoreShiftResultRequest $request, Shift $shift): ShiftResource
    {
        $this->ensureCanManageSector($request->user(), $shift->sector_id);
        abort_unless($shift->status === 'completed', 422, 'Solo se pueden registrar resultados de turnos completados.');

        $shift->result()->updateOrCreate([], [
            ...$request->validated(),
            'recorded_by_user_id' => $request->user()->id,
        ]);

        return new ShiftResource($shift->fresh()->load(['sector', 'resources', 'responsible.person', 'result']));
    }

    private function ensureCanManageSector(User $user, int $sectorId): void
    {
        if ($user->hasRole(Role::ADMIN)) {
            return;
        }

        abort_unless(
            $user->hasRole(Role::COORDINATOR) && $user->managedSectors()->whereKey($sectorId)->exists(),
            403,
        );
    }

    private function isValidTransition(string $currentStatus, string $nextStatus): bool
    {
        return match ($currentStatus) {
            'planned' => in_array($nextStatus, ['in_progress', 'cancelled'], true),
            'in_progress' => in_array($nextStatus, ['completed', 'cancelled'], true),
            default => false,
        };
    }
}
