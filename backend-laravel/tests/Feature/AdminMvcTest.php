<?php

namespace Tests\Feature;

use App\Models\Resource;
use App\Models\Sector;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AdminMvcTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['dashboard'])]
    #[TestWith(['sectors'])]
    #[TestWith(['resources'])]
    #[TestWith(['users'])]
    #[TestWith(['shifts'])]
    public function test_administrative_pages_render_as_blade_views(string $page): void
    {
        $admin = $this->admin();

        $this->withoutVite()->actingAs($admin)->get(route('admin.'.$page))
            ->assertOk()->assertViewIs('pages.admin.'.$page);
    }

    #[TestWith(['sectors'])]
    #[TestWith(['resources'])]
    #[TestWith(['users'])]
    public function test_edit_pages_submit_to_the_update_route(string $page): void
    {
        $admin = $this->admin();
        $record = match ($page) {
            'sectors' => Sector::query()->firstOrFail(),
            'resources' => Resource::query()->firstOrFail(),
            'users' => User::query()->where('username', 'vecino')->firstOrFail(),
        };

        $this->withoutVite()->actingAs($admin)->get(route('admin.'.$page.'.edit', $record))
            ->assertOk()->assertViewHas('editingId', $record->id)
            ->assertSee(route('admin.'.$page.'.update', $record), false)
            ->assertSee('name="_method" value="PUT"', false);
    }

    public function test_non_coordinators_cannot_be_assigned_to_a_sector(): void
    {
        $admin = $this->admin();
        $neighbor = User::query()->where('username', 'vecino')->firstOrFail();

        $this->actingAs($admin)->from(route('admin.sectors'))->post(route('admin.sectors.store'), [
            'name' => 'Sector rechazado', 'status' => 'active', 'coordinatorIds' => [$neighbor->id],
        ])->assertRedirect(route('admin.sectors'))->assertSessionHasErrors('coordinatorIds');

        $this->assertDatabaseMissing('sectors', ['name' => 'Sector rechazado']);
    }

    public function test_unchecking_all_coordinators_removes_the_assignments(): void
    {
        $admin = $this->admin();
        $sector = Sector::query()->where('name', 'Sector Norte')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.sectors.update', $sector), [
            'name' => $sector->name, 'status' => 'active',
        ])->assertRedirect(route('admin.sectors'))->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('sector_responsibles', ['sector_id' => $sector->id]);
    }

    public function test_an_inactive_administrator_cannot_write_to_the_panel(): void
    {
        $admin = $this->admin();
        $admin->update(['is_active' => false]);

        $this->actingAs($admin)->post(route('admin.sectors.store'), [
            'name' => 'Sector sin permiso', 'status' => 'active',
        ])->assertForbidden();

        $this->assertDatabaseMissing('sectors', ['name' => 'Sector sin permiso']);
    }

    public function test_a_coordinator_cannot_delete_a_resource(): void
    {
        $this->seed();
        $coordinator = User::query()->where('username', 'coordinador')->firstOrFail();
        $resource = Resource::query()->firstOrFail();

        $this->actingAs($coordinator)->delete(route('admin.resources.destroy', $resource))->assertForbidden();

        $this->assertModelExists($resource);
    }

    public function test_a_guest_cannot_create_a_user(): void
    {
        $this->post(route('admin.users.store'), [])->assertRedirect(route('login'));

        $this->assertDatabaseCount('user_accounts', 0);
    }

    public function test_resources_can_be_created_without_an_optional_capacity(): void
    {
        $admin = $this->admin();
        $sector = Sector::query()->firstOrFail();

        $this->actingAs($admin)->post(route('admin.resources.store'), [
            'name' => 'Pozo sin capacidad', 'resource_type' => 'Pozo', 'status' => 'available', 'sectorIds' => [$sector->id],
        ])->assertRedirect(route('admin.resources'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('resources', ['name' => 'Pozo sin capacidad', 'capacity' => null]);
    }

    public function test_turns_are_created_and_overlapping_reservations_return_form_errors(): void
    {
        $admin = $this->admin();
        $payload = $this->shiftPayload();

        $this->actingAs($admin)->post(route('admin.shifts.store'), $payload)
            ->assertRedirect(route('admin.shifts'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('shifts', ['sector_id' => $payload['sectorId'], 'status' => 'planned']);
        $this->assertDatabaseHas('shift_resources', ['resource_id' => $payload['resourceIds'][0]]);

        $this->from(route('admin.shifts'))->post(route('admin.shifts.store'), $payload)
            ->assertRedirect(route('admin.shifts'))->assertSessionHasErrors('resourceIds');

        $this->assertDatabaseCount('shifts', 1);
        $this->assertDatabaseHas('resource_conflict_logs', ['resource_id' => $payload['resourceIds'][0]]);
    }

    public function test_resources_from_another_sector_return_visible_form_errors(): void
    {
        $admin = $this->admin();
        $payload = $this->shiftPayload();
        $otherSector = Sector::query()->create(['name' => 'Sin recursos asociados', 'status' => 'active']);
        $payload['sectorId'] = $otherSector->id;

        $this->actingAs($admin)->from(route('admin.shifts'))->post(route('admin.shifts.store'), $payload)
            ->assertRedirect(route('admin.shifts'))->assertSessionHasErrors('resourceIds');

        $this->assertDatabaseCount('shifts', 0);
    }

    public function test_the_sector_filter_loads_available_resources(): void
    {
        $admin = $this->admin();
        $payload = $this->shiftPayload();

        $this->withoutVite()->actingAs($admin)->get(route('admin.shifts', ['sectorId' => $payload['sectorId']]))
            ->assertOk()->assertSee('name="resourceIds[]"', false)->assertSee('Bomba de agua');
    }

    public function test_a_turn_cannot_skip_directly_to_completed(): void
    {
        $admin = $this->admin();
        $shift = $this->createShift();

        $this->actingAs($admin)->from(route('admin.shifts'))->patch(route('admin.shifts.status', $shift), [
            'status' => 'completed',
        ])->assertRedirect(route('admin.shifts'))->assertSessionHasErrors('status');

        $this->assertDatabaseHas('shifts', ['id' => $shift->id, 'status' => 'planned']);
    }

    public function test_an_administrator_can_start_and_complete_a_turn(): void
    {
        $admin = $this->admin();
        $shift = $this->createShift();

        $this->actingAs($admin)->patch(route('admin.shifts.status', $shift), ['status' => 'in_progress'])
            ->assertRedirect(route('admin.shifts'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('shifts', ['id' => $shift->id, 'status' => 'in_progress']);

        $this->patch(route('admin.shifts.status', $shift), ['status' => 'completed'])
            ->assertRedirect(route('admin.shifts'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('shifts', ['id' => $shift->id, 'status' => 'completed']);
    }

    public function test_sectors_and_resources_with_turns_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $shift = $this->createShift();
        $resource = $shift->resources()->firstOrFail();
        $sector = $shift->sector;

        $this->actingAs($admin)->delete(route('admin.sectors.destroy', $sector))
            ->assertRedirect(route('admin.sectors'))->assertSessionHas('error');
        $this->assertModelExists($sector);

        $this->delete(route('admin.resources.destroy', $resource))
            ->assertRedirect(route('admin.resources'))->assertSessionHas('error');
        $this->assertModelExists($resource);
    }

    public function test_invalid_user_data_does_not_create_a_person_or_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'firstName' => 'Prueba', 'lastName' => 'Inválida', 'documentNumber' => 'INVALID-MVC',
            'username' => 'invalido.mvc', 'password' => 'corta', 'roleName' => 'admin', 'isActive' => true,
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('people', ['document_number' => 'INVALID-MVC']);
        $this->assertDatabaseMissing('user_accounts', ['username' => 'invalido.mvc']);
    }

    private function admin(): User
    {
        $this->seed();

        return User::query()->where('username', 'admin')->firstOrFail();
    }

    private function shiftPayload(): array
    {
        $resource = Resource::query()->where('name', 'Bomba de agua')->firstOrFail();

        return [
            'sectorId' => $resource->sectors()->firstOrFail()->id,
            'resourceIds' => [$resource->id],
            'startsAt' => '2026-12-01T08:00', 'endsAt' => '2026-12-01T10:00',
        ];
    }

    private function createShift(): Shift
    {
        $payload = $this->shiftPayload();
        $shift = Shift::query()->create([
            'sector_id' => $payload['sectorId'], 'starts_at' => $payload['startsAt'],
            'ends_at' => $payload['endsAt'], 'status' => 'planned',
        ]);
        $shift->resources()->sync($payload['resourceIds']);

        return $shift;
    }
}
