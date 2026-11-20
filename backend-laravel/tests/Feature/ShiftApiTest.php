<?php

namespace Tests\Feature;

use App\Models\Resource;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShiftApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_shift_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/shifts')->assertUnauthorized();
        $this->postJson('/api/v1/shifts', [])->assertUnauthorized();
    }

    public function test_an_assigned_coordinator_can_create_a_shift(): void
    {
        [$sector, $resource] = $this->sectorWithResource();
        $coordinator = $this->user('coordinador');
        $sector->responsibleUsers()->attach($coordinator);
        Sanctum::actingAs($coordinator);

        $this->postJson('/api/v1/shifts', $this->shiftPayload($sector->id, $resource->id))
            ->assertCreated()
            ->assertJsonPath('data.sector.id', $sector->id)
            ->assertJsonPath('data.resources.0.id', $resource->id)
            ->assertJsonPath('data.status', 'planned');
    }

    public function test_a_coordinator_cannot_create_a_shift_in_an_unassigned_sector(): void
    {
        [$sector, $resource] = $this->sectorWithResource();
        Sanctum::actingAs($this->user('coordinador'));

        $this->postJson('/api/v1/shifts', $this->shiftPayload($sector->id, $resource->id))
            ->assertForbidden();
    }

    public function test_overlapping_resource_reservations_return_a_conflict_response(): void
    {
        [$sector, $resource] = $this->sectorWithResource();
        $admin = $this->user('admin');
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/shifts', $this->shiftPayload($sector->id, $resource->id))
            ->assertCreated();

        $this->postJson('/api/v1/shifts', [
            ...$this->shiftPayload($sector->id, $resource->id),
            'starts_at' => '2026-12-01 09:00:00',
            'ends_at' => '2026-12-01 11:00:00',
        ])
            ->assertConflict()
            ->assertJsonPath('code', 'RESOURCE_TIME_CONFLICT')
            ->assertJsonPath('resource_ids.0', $resource->id);
    }

    public function test_an_assigned_coordinator_can_start_a_planned_shift(): void
    {
        [$sector, $resource] = $this->sectorWithResource();
        $coordinator = $this->user('coordinador');
        $sector->responsibleUsers()->attach($coordinator);
        Sanctum::actingAs($coordinator);

        $shift = $this->postJson('/api/v1/shifts', $this->shiftPayload($sector->id, $resource->id))
            ->assertCreated()
            ->json('data');

        $this->patchJson("/api/v1/shifts/{$shift['id']}/status", ['status' => 'in_progress'])
            ->assertOk()
            ->assertJsonPath('data.status', 'in_progress');
    }

    public function test_an_assigned_coordinator_can_edit_a_planned_shift(): void
    {
        [$sector, $resource] = $this->sectorWithResource();
        $coordinator = $this->user('coordinador');
        $sector->responsibleUsers()->attach($coordinator);
        Sanctum::actingAs($coordinator);

        $shift = $this->postJson('/api/v1/shifts', $this->shiftPayload($sector->id, $resource->id))->json('data');

        $this->putJson("/api/v1/shifts/{$shift['id']}", [
            ...$this->shiftPayload($sector->id, $resource->id),
            'starts_at' => '2026-12-01 12:00:00',
            'ends_at' => '2026-12-01 14:00:00',
            'notes' => 'Horario actualizado por coordinación.',
        ])
            ->assertOk()
            ->assertJsonPath('data.notes', 'Horario actualizado por coordinación.');
    }

    public function test_editing_a_shift_rejects_a_resource_time_conflict(): void
    {
        [$sector, $resource] = $this->sectorWithResource();
        $admin = $this->user('admin');
        Sanctum::actingAs($admin);

        $first = $this->postJson('/api/v1/shifts', $this->shiftPayload($sector->id, $resource->id))->json('data');
        $second = $this->postJson('/api/v1/shifts', [
            ...$this->shiftPayload($sector->id, $resource->id),
            'starts_at' => '2026-12-01 12:00:00',
            'ends_at' => '2026-12-01 14:00:00',
        ])->json('data');

        $this->putJson("/api/v1/shifts/{$second['id']}", $this->shiftPayload($sector->id, $resource->id))
            ->assertConflict()
            ->assertJsonPath('code', 'RESOURCE_TIME_CONFLICT');

        $this->assertDatabaseCount('resource_conflict_logs', 1);
        $this->assertDatabaseHas('shifts', ['id' => $first['id'], 'starts_at' => '2026-12-01 08:00:00']);
    }

    public function test_an_assigned_coordinator_can_record_the_volume_of_a_completed_shift(): void
    {
        [$sector, $resource] = $this->sectorWithResource();
        $coordinator = $this->user('coordinador');
        $sector->responsibleUsers()->attach($coordinator);
        Sanctum::actingAs($coordinator);

        $shift = $this->postJson('/api/v1/shifts', $this->shiftPayload($sector->id, $resource->id))->json('data');
        $this->patchJson("/api/v1/shifts/{$shift['id']}/status", ['status' => 'in_progress']);
        $this->patchJson("/api/v1/shifts/{$shift['id']}/status", ['status' => 'completed']);

        $this->putJson("/api/v1/shifts/{$shift['id']}/result", ['delivered_volume' => 1250, 'volume_unit' => 'litros'])
            ->assertOk()
            ->assertJsonPath('data.result.delivered_volume', '1250.00')
            ->assertJsonPath('data.result.volume_unit', 'litros');
    }

    /** @return array{Sector, resource} */
    private function sectorWithResource(): array
    {
        $sector = Sector::query()->create(['name' => 'Sector de turnos', 'status' => 'active']);
        $resource = Resource::query()->create(['name' => 'Tanque de turnos', 'resource_type' => 'Tanque', 'status' => 'available']);
        $resource->sectors()->attach($sector);

        return [$sector, $resource];
    }

    /** @return array{sector_id: int, starts_at: string, ends_at: string, notes: string, resource_ids: array<int, int>} */
    private function shiftPayload(int $sectorId, int $resourceId): array
    {
        return [
            'sector_id' => $sectorId,
            'starts_at' => '2026-12-01 08:00:00',
            'ends_at' => '2026-12-01 10:00:00',
            'notes' => 'Turno de distribución de prueba.',
            'resource_ids' => [$resourceId],
        ];
    }

    private function user(string $username): User
    {
        $this->seed();

        return User::query()->where('username', $username)->firstOrFail();
    }
}
