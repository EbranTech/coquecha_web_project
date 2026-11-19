<?php

namespace Tests\Feature;

use App\Models\Resource;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/sectors')->assertUnauthorized();
        $this->getJson('/api/v1/resources')->assertUnauthorized();
    }

    public function test_an_authenticated_user_receives_only_active_sectors(): void
    {
        Sanctum::actingAs($this->admin());
        Sector::query()->create(['name' => 'Sector activo', 'status' => 'active']);
        Sector::query()->create(['name' => 'Sector inactivo', 'status' => 'inactive']);

        $this->getJson('/api/v1/sectors')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Sector activo'])
            ->assertJsonMissing(['name' => 'Sector inactivo']);
    }

    public function test_an_authenticated_user_can_filter_resources_by_status_and_type(): void
    {
        Sanctum::actingAs($this->admin());
        Resource::query()->create(['name' => 'Tanque central', 'resource_type' => 'Tanque', 'status' => 'available']);
        Resource::query()->create(['name' => 'Bomba norte', 'resource_type' => 'Bomba', 'status' => 'maintenance']);

        $this->getJson('/api/v1/resources?status=available&type=Tanque')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Tanque central');
    }

    private function admin(): User
    {
        $this->seed();

        return User::query()->where('username', 'admin')->firstOrFail();
    }
}
