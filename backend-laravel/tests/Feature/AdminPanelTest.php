<?php

namespace Tests\Feature;

use App\Models\Sector;
use App\Models\Resource as WaterResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_access_the_sector_panel(): void
    {
        $this->seed();
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $this->withoutVite()->actingAs($admin)->get(route('admin.sectors'))->assertOk();
    }

    public function test_a_coordinator_cannot_access_the_administration_panel(): void
    {
        $this->seed();
        $coordinator = User::query()->where('username', 'coordinador')->firstOrFail();

        $this->actingAs($coordinator)->get(route('admin.sectors'))->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_the_login_page(): void
    {
        $this->get(route('admin.resources'))->assertRedirect(route('login'));
    }

    public function test_an_administrator_can_create_a_sector(): void
    {
        $this->seed();
        $admin = User::query()->where('username', 'admin')->firstOrFail();

        $this->actingAs($admin);

        Livewire::test('pages::admin.sectors')
            ->set('name', 'Sector Norte')
            ->set('description', 'Zona cercana al nacimiento de agua.')
            ->set('location_description', 'Aldea Coquechas')
            ->set('status', 'active')
            ->call('save');

        $this->assertDatabaseHas('sectors', [
            'name' => 'Sector Norte',
            'status' => 'active',
        ]);
    }

    public function test_an_administrator_can_assign_a_coordinator_to_a_sector(): void
    {
        $this->seed();
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $coordinator = User::query()->where('username', 'coordinador')->firstOrFail();

        $this->actingAs($admin);

        Livewire::test('pages::admin.sectors')
            ->set('name', 'Sector con responsable')
            ->set('status', 'active')
            ->set('coordinatorIds', [$coordinator->id])
            ->call('save');

        $sector = Sector::query()->where('name', 'Sector con responsable')->firstOrFail();
        $this->assertTrue($sector->responsibleUsers()->whereKey($coordinator->id)->exists());
    }

    public function test_an_administrator_can_create_a_resource(): void
    {
        $this->seed();
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $sector = Sector::query()->firstOrCreate(['name' => 'Sector para recursos'], ['status' => 'active']);

        $this->actingAs($admin);

        Livewire::test('pages::admin.resources')
            ->set('name', 'Tanque comunitario')
            ->set('resource_type', 'Tanque')
            ->set('capacity', '2500')
            ->set('capacity_unit', 'litros')
            ->set('status', 'available')
            ->set('sectorIds', [$sector->id])
            ->call('save');

        $this->assertDatabaseHas('resources', [
            'name' => 'Tanque comunitario',
            'resource_type' => 'Tanque',
            'status' => 'available',
        ]);
    }

    public function test_an_administrator_can_create_a_coordinator_and_assign_sectors(): void
    {
        $this->seed();
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $sector = Sector::query()->firstOrFail();

        Livewire::actingAs($admin)->test('pages::admin.users')
            ->set('firstName', 'Ana')->set('lastName', 'Prueba')->set('documentNumber', 'QA-USER-001')
            ->set('username', 'ana.coordinadora')->set('password', 'password-segura')
            ->set('roleName', 'coordinator')->set('sectorIds', [$sector->id])->call('save');

        $user = User::query()->where('username', 'ana.coordinadora')->firstOrFail();
        $this->assertTrue($user->hasRole('coordinator'));
        $this->assertTrue($user->managedSectors()->whereKey($sector->id)->exists());
    }

    public function test_an_administrator_can_edit_and_deactivate_a_user(): void
    {
        $this->seed();
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $neighbor = User::query()->where('username', 'vecino')->firstOrFail();

        Livewire::actingAs($admin)->test('pages::admin.users')
            ->call('edit', $neighbor->id)
            ->set('phone', '5555-0101')
            ->set('isActive', false)
            ->call('save');

        $this->assertDatabaseHas('user_accounts', ['id' => $neighbor->id, 'is_active' => false]);
        $this->assertDatabaseHas('people', ['id' => $neighbor->person_id, 'phone' => '5555-0101', 'status' => 'inactive']);
    }

    public function test_an_administrator_can_edit_and_delete_an_unused_sector(): void
    {
        $this->seed();
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $sector = Sector::query()->create(['name' => 'Sector temporal', 'status' => 'active']);

        Livewire::actingAs($admin)->test('pages::admin.sectors')
            ->call('edit', $sector->id)
            ->set('name', 'Sector actualizado')
            ->set('status', 'inactive')
            ->call('save');

        $this->assertDatabaseHas('sectors', ['id' => $sector->id, 'name' => 'Sector actualizado', 'status' => 'inactive']);

        Livewire::actingAs($admin)->test('pages::admin.sectors')->call('delete', $sector->id);
        $this->assertDatabaseMissing('sectors', ['id' => $sector->id]);
    }

    public function test_an_administrator_can_edit_and_delete_an_unused_resource(): void
    {
        $this->seed();
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $sector = Sector::query()->firstOrFail();
        $resource = WaterResource::query()->create(['name' => 'Recurso temporal', 'resource_type' => 'Bomba', 'capacity' => 100, 'capacity_unit' => 'litros', 'status' => 'available']);
        $resource->sectors()->attach($sector);

        Livewire::actingAs($admin)->test('pages::admin.resources')
            ->call('edit', $resource->id)
            ->set('name', 'Recurso actualizado')
            ->set('status', 'maintenance')
            ->call('save');

        $this->assertDatabaseHas('resources', ['id' => $resource->id, 'name' => 'Recurso actualizado', 'status' => 'maintenance']);

        Livewire::actingAs($admin)->test('pages::admin.resources')->call('delete', $resource->id);
        $this->assertDatabaseMissing('resources', ['id' => $resource->id]);
    }
}
