<?php

namespace Tests\Feature;

use App\Exceptions\ShiftConflictException;
use App\Models\Resource;
use App\Models\Sector;
use App\Models\User;
use App\Services\ShiftConflictService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftConflictTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_shift_can_reserve_a_resource_associated_with_its_sector(): void
    {
        [$sector, $resource, $admin] = $this->shiftSetup();

        $shift = app(ShiftConflictService::class)->create([
            'sector_id' => $sector->id,
            'responsible_user_id' => $admin->id,
            'starts_at' => '2026-12-01 08:00:00',
            'ends_at' => '2026-12-01 10:00:00',
            'status' => 'planned',
        ], [$resource->id], $admin->id);

        $this->assertDatabaseHas('shifts', ['id' => $shift->id, 'sector_id' => $sector->id]);
        $this->assertDatabaseHas('shift_resources', ['shift_id' => $shift->id, 'resource_id' => $resource->id]);
    }

    public function test_an_overlapping_resource_reservation_is_rejected_and_logged(): void
    {
        [$sector, $resource, $admin] = $this->shiftSetup();
        $service = app(ShiftConflictService::class);

        $service->create([
            'sector_id' => $sector->id,
            'responsible_user_id' => $admin->id,
            'starts_at' => '2026-12-01 08:00:00',
            'ends_at' => '2026-12-01 10:00:00',
            'status' => 'planned',
        ], [$resource->id], $admin->id);

        try {
            $service->create([
                'sector_id' => $sector->id,
                'responsible_user_id' => $admin->id,
                'starts_at' => '2026-12-01 09:00:00',
                'ends_at' => '2026-12-01 11:00:00',
                'status' => 'planned',
            ], [$resource->id], $admin->id);
            $this->fail('Se esperaba un conflicto por solapamiento.');
        } catch (ShiftConflictException) {
            $this->assertDatabaseCount('shifts', 1);
            $this->assertDatabaseHas('resource_conflict_logs', ['resource_id' => $resource->id, 'attempted_by_user_id' => $admin->id]);
        }
    }

    /** @return array{Sector, resource, User} */
    private function shiftSetup(): array
    {
        $this->seed();
        $sector = Sector::query()->create(['name' => 'Sector de prueba', 'status' => 'active']);
        $resource = Resource::query()->create(['name' => 'Tanque de prueba', 'resource_type' => 'Tanque', 'status' => 'available']);
        $resource->sectors()->attach($sector);

        return [$sector, $resource, User::query()->where('username', 'admin')->firstOrFail()];
    }
}
