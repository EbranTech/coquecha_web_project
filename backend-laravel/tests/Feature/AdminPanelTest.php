<?php

namespace Tests\Feature;

use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
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

    public function test_an_administrator_can_create_a_resource(): void
    {
        $this->seed();
        $admin = User::query()->where('username', 'admin')->firstOrFail();

        $this->actingAs($admin);

        Livewire::test('pages::admin.resources')
            ->set('name', 'Tanque comunitario')
            ->set('resource_type', 'Tanque')
            ->set('capacity', '2500')
            ->set('capacity_unit', 'litros')
            ->set('status', 'available')
            ->call('save');

        $this->assertDatabaseHas('resources', [
            'name' => 'Tanque comunitario',
            'resource_type' => 'Tanque',
            'status' => 'available',
        ]);
    }
}
