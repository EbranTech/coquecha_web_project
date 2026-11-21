<?php

namespace Tests\Feature;

use App\Models\Resource as WaterResource;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->actingAs($admin)->post(route('admin.sectors.store'), [
            'name' => 'Sector de prueba MVC',
            'description' => 'Zona cercana al nacimiento de agua.',
            'location_description' => 'Aldea Coquechas',
            'status' => 'active',
        ])->assertRedirect(route('admin.sectors'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sectors', [
            'name' => 'Sector de prueba MVC',
            'status' => 'active',
        ]);
    }

    public function test_an_administrator_can_assign_a_coordinator_to_a_sector(): void
    {
        $this->seed();
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $coordinator = User::query()->where('username', 'coordinador')->firstOrFail();

        $this->actingAs($admin);

        $this->actingAs($admin)->post(route('admin.sectors.store'), [
            'name' => 'Sector con responsable',
            'status' => 'active',
            'coordinatorIds' => [$coordinator->id],
        ])->assertRedirect(route('admin.sectors'))->assertSessionHasNoErrors();

        $sector = Sector::query()->where('name', 'Sector con responsable')->firstOrFail();
        $this->assertTrue($sector->responsibleUsers()->whereKey($coordinator->id)->exists());
    }

    public function test_an_administrator_can_create_a_resource(): void
    {
        $this->seed();
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $sector = Sector::query()->firstOrCreate(['name' => 'Sector para recursos'], ['status' => 'active']);

        $this->actingAs($admin);

        $this->actingAs($admin)->post(route('admin.resources.store'), [
            'name' => 'Tanque comunitario',
            'resource_type' => 'Tanque',
            'capacity' => '2500',
            'capacity_unit' => 'litros',
            'status' => 'available',
            'sectorIds' => [$sector->id],
        ])->assertRedirect(route('admin.resources'))->assertSessionHasNoErrors();

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

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'firstName' => 'Ana',
            'lastName' => 'Prueba',
            'documentNumber' => 'QA-USER-001',
            'username' => 'ana.coordinadora',
            'password' => 'password-segura',
            'roleName' => 'coordinator',
            'sectorIds' => [$sector->id],
            'isActive' => true,
        ])->assertRedirect(route('admin.users'))->assertSessionHasNoErrors();

        $user = User::query()->where('username', 'ana.coordinadora')->firstOrFail();
        $this->assertTrue($user->hasRole('coordinator'));
        $this->assertTrue($user->managedSectors()->whereKey($sector->id)->exists());
    }

    public function test_an_administrator_can_edit_and_deactivate_a_user(): void
    {
        $this->seed();
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $neighbor = User::query()->where('username', 'vecino')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.users.update', $neighbor), [
            'firstName' => $neighbor->person->first_name, 'lastName' => $neighbor->person->last_name, 'documentNumber' => $neighbor->person->document_number, 'username' => $neighbor->username, 'roleName' => $neighbor->role->name, 'phone' => '5555-0101',
            'isActive' => false,
        ])->assertRedirect(route('admin.users'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_accounts', ['id' => $neighbor->id, 'is_active' => false]);
        $this->assertDatabaseHas('people', ['id' => $neighbor->person_id, 'phone' => '5555-0101', 'status' => 'inactive']);
    }

    public function test_an_administrator_can_edit_and_delete_an_unused_sector(): void
    {
        $this->seed();
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $sector = Sector::query()->create(['name' => 'Sector temporal', 'status' => 'active']);

        $this->actingAs($admin)->put(route('admin.sectors.update', $sector), [
            'name' => 'Sector actualizado',
            'status' => 'inactive',
        ])->assertRedirect(route('admin.sectors'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sectors', ['id' => $sector->id, 'name' => 'Sector actualizado', 'status' => 'inactive']);

        $this->actingAs($admin)->delete(route('admin.sectors.destroy', $sector))->assertRedirect(route('admin.sectors'));
        $this->assertDatabaseMissing('sectors', ['id' => $sector->id]);
    }

    public function test_an_administrator_can_edit_and_delete_an_unused_resource(): void
    {
        $this->seed();
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $sector = Sector::query()->firstOrFail();
        $resource = WaterResource::query()->create(['name' => 'Recurso temporal', 'resource_type' => 'Bomba', 'capacity' => 100, 'capacity_unit' => 'litros', 'status' => 'available']);
        $resource->sectors()->attach($sector);

        $this->actingAs($admin)->put(route('admin.resources.update', $resource), [
            'resource_type' => $resource->resource_type, 'sectorIds' => [$sector->id], 'name' => 'Recurso actualizado',
            'status' => 'maintenance',
        ])->assertRedirect(route('admin.resources'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('resources', ['id' => $resource->id, 'name' => 'Recurso actualizado', 'status' => 'maintenance']);

        $this->actingAs($admin)->delete(route('admin.resources.destroy', $resource))->assertRedirect(route('admin.resources'));
        $this->assertDatabaseMissing('resources', ['id' => $resource->id]);
    }
}
