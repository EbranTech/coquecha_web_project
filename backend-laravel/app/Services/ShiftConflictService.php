<?php

namespace App\Services;

use App\Exceptions\ShiftConflictException;
use App\Models\Resource;
use App\Models\ResourceConflictLog;
use App\Models\Shift;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShiftConflictService
{
    /**
     * @param  array{sector_id: int, responsible_user_id?: int|null, starts_at: string, ends_at: string, status?: string, notes?: string|null}  $attributes
     * @param  array<int, int>  $resourceIds
     */
    public function create(array $attributes, array $resourceIds, ?int $actorId = null): Shift
    {
        $resourceIds = array_values(array_unique($resourceIds));

        if ($resourceIds === []) {
            throw ValidationException::withMessages(['resources' => ['Seleccione al menos un recurso.']]);
        }

        try {
            return DB::transaction(function () use ($attributes, $resourceIds): Shift {
                $resources = Resource::query()->whereKey($resourceIds)->lockForUpdate()->get();

                if ($resources->count() !== count($resourceIds)) {
                    throw ValidationException::withMessages(['resources' => ['Uno de los recursos seleccionados no existe.']]);
                }

                if ($resources->contains(fn (Resource $resource): bool => $resource->status !== 'available')) {
                    throw ValidationException::withMessages(['resources' => ['Solo se pueden asignar recursos disponibles.']]);
                }

                $associatedResourceIds = $resources
                    ->filter(fn (Resource $resource): bool => $resource->sectors()->whereKey($attributes['sector_id'])->exists())
                    ->pluck('id')
                    ->all();

                if (count($associatedResourceIds) !== count($resourceIds)) {
                    throw ValidationException::withMessages(['resources' => ['Cada recurso debe estar asociado al sector del turno.']]);
                }

                $conflictingShifts = Shift::query()
                    ->whereIn('status', ['planned', 'in_progress'])
                    ->where('starts_at', '<', $attributes['ends_at'])
                    ->where('ends_at', '>', $attributes['starts_at'])
                    ->whereHas('resources', fn ($query) => $query->whereIn('resources.id', $resourceIds))
                    ->with(['resources' => fn ($query) => $query->whereIn('resources.id', $resourceIds)])
                    ->lockForUpdate()
                    ->get();

                if ($conflictingShifts->isNotEmpty()) {
                    throw new ShiftConflictException($conflictingShifts->flatMap->resources->pluck('id')->unique()->values()->all());
                }

                $shift = Shift::query()->create($attributes);
                $shift->resources()->sync($resourceIds);

                return $shift->load(['sector', 'resources', 'responsible']);
            });
        } catch (ShiftConflictException $exception) {
            foreach ($exception->resourceIds as $resourceId) {
                ResourceConflictLog::query()->create([
                    'resource_id' => $resourceId,
                    'attempted_starts_at' => $attributes['starts_at'],
                    'attempted_ends_at' => $attributes['ends_at'],
                    'attempted_by_user_id' => $actorId,
                ]);
            }

            throw $exception;
        }
    }

    /** @param array{sector_id: int, responsible_user_id?: int|null, starts_at: string, ends_at: string, notes?: string|null} $attributes */
    public function update(Shift $shift, array $attributes, array $resourceIds, ?int $actorId = null): Shift
    {
        $resourceIds = array_values(array_unique($resourceIds));

        if ($resourceIds === []) {
            throw ValidationException::withMessages(['resources' => ['Seleccione al menos un recurso.']]);
        }

        try {
            return DB::transaction(function () use ($shift, $attributes, $resourceIds): Shift {
                $resources = Resource::query()->whereKey($resourceIds)->lockForUpdate()->get();

                if ($resources->count() !== count($resourceIds)) {
                    throw ValidationException::withMessages(['resources' => ['Uno de los recursos seleccionados no existe.']]);
                }

                if ($resources->contains(fn (Resource $resource): bool => $resource->status !== 'available')) {
                    throw ValidationException::withMessages(['resources' => ['Solo se pueden asignar recursos disponibles.']]);
                }

                $associatedResourceIds = $resources
                    ->filter(fn (Resource $resource): bool => $resource->sectors()->whereKey($attributes['sector_id'])->exists())
                    ->pluck('id')
                    ->all();

                if (count($associatedResourceIds) !== count($resourceIds)) {
                    throw ValidationException::withMessages(['resources' => ['Cada recurso debe estar asociado al sector del turno.']]);
                }

                $conflictingShifts = Shift::query()
                    ->whereKeyNot($shift->id)
                    ->whereIn('status', ['planned', 'in_progress'])
                    ->where('starts_at', '<', $attributes['ends_at'])
                    ->where('ends_at', '>', $attributes['starts_at'])
                    ->whereHas('resources', fn ($query) => $query->whereIn('resources.id', $resourceIds))
                    ->with(['resources' => fn ($query) => $query->whereIn('resources.id', $resourceIds)])
                    ->lockForUpdate()
                    ->get();

                if ($conflictingShifts->isNotEmpty()) {
                    throw new ShiftConflictException($conflictingShifts->flatMap->resources->pluck('id')->unique()->values()->all());
                }

                $shift->update([...$attributes, 'status' => $shift->status]);
                $shift->resources()->sync($resourceIds);

                return $shift->fresh()->load(['sector', 'resources', 'responsible']);
            });
        } catch (ShiftConflictException $exception) {
            foreach ($exception->resourceIds as $resourceId) {
                ResourceConflictLog::query()->create([
                    'resource_id' => $resourceId,
                    'attempted_starts_at' => $attributes['starts_at'],
                    'attempted_ends_at' => $attributes['ends_at'],
                    'attempted_by_user_id' => $actorId,
                ]);
            }

            throw $exception;
        }
    }
}
