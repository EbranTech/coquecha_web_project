<?php

namespace Tests\Feature;

use App\Models\Sector;
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
}
