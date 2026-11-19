<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_neighbor_can_create_a_report_in_an_active_sector(): void
    {
        $neighbor = $this->user('vecino');
        $sector = Sector::query()->create(['name' => 'Sector de reporte norte', 'status' => 'active']);
        Sanctum::actingAs($neighbor);

        $this->postJson('/api/v1/reports', [
            'sector_id' => $sector->id,
            'category' => 'Fuga de agua',
            'description' => 'Hay una fuga continua frente a la escuela del sector.',
            'priority' => 'high',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.sector.id', $sector->id);

        $this->assertDatabaseHas('reports', [
            'sector_id' => $sector->id,
            'created_by_user_id' => $neighbor->id,
            'priority' => 'high',
        ]);
    }

    public function test_a_neighbor_only_receives_their_own_reports(): void
    {
        $sector = Sector::query()->create(['name' => 'Sector sur', 'status' => 'active']);
        $neighbor = $this->user('vecino');
        $otherUser = $this->user('admin');
        Report::query()->create($this->reportAttributes($sector, $neighbor));
        Report::query()->create($this->reportAttributes($sector, $otherUser));
        Sanctum::actingAs($neighbor);

        $this->getJson('/api/v1/reports')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.creator.id', $neighbor->id);
    }

    public function test_only_an_assigned_coordinator_can_update_a_report_and_add_followups(): void
    {
        $sector = Sector::query()->create(['name' => 'Sector central', 'status' => 'active']);
        $coordinator = $this->user('coordinador');
        $sector->responsibleUsers()->attach($coordinator);
        $report = Report::query()->create($this->reportAttributes($sector, $this->user('vecino')));
        Sanctum::actingAs($coordinator);

        $this->postJson("/api/v1/reports/{$report->id}/followups", [
            'message' => 'Se asignó una cuadrilla para revisar la fuga.',
            'new_status' => 'in_progress',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.followups.0.new_status', 'in_progress');

        $this->assertDatabaseHas('report_followups', ['report_id' => $report->id, 'user_id' => $coordinator->id]);
    }

    public function test_a_coordinator_cannot_update_a_report_from_an_unassigned_sector(): void
    {
        $sector = Sector::query()->create(['name' => 'Sector lejano', 'status' => 'active']);
        $report = Report::query()->create($this->reportAttributes($sector, $this->user('vecino')));
        Sanctum::actingAs($this->user('coordinador'));

        $this->patchJson("/api/v1/reports/{$report->id}", ['status' => 'resolved'])
            ->assertForbidden();
    }

    public function test_an_authorized_user_can_upload_a_pdf_evidence_to_private_storage(): void
    {
        Storage::fake('s3');
        $sector = Sector::query()->create(['name' => 'Sector oriente', 'status' => 'active']);
        $neighbor = $this->user('vecino');
        $report = Report::query()->create($this->reportAttributes($sector, $neighbor));
        Sanctum::actingAs($neighbor);

        $this->postJson("/api/v1/reports/{$report->id}/evidences", [
            'files' => [UploadedFile::fake()->create('fuga.pdf', 200, 'application/pdf')],
        ])->assertCreated();

        $evidence = $report->evidences()->firstOrFail();
        Storage::disk('s3')->assertExists($evidence->storage_key);
        $this->assertDatabaseHas('report_evidences', ['id' => $evidence->id, 'original_name' => 'fuga.pdf']);
    }

    public function test_evidence_rejects_files_outside_the_allowed_types(): void
    {
        Storage::fake('s3');
        $sector = Sector::query()->create(['name' => 'Sector poniente', 'status' => 'active']);
        $neighbor = $this->user('vecino');
        $report = Report::query()->create($this->reportAttributes($sector, $neighbor));
        Sanctum::actingAs($neighbor);

        $this->postJson("/api/v1/reports/{$report->id}/evidences", [
            'files' => [UploadedFile::fake()->create('archivo.exe', 200, 'application/octet-stream')],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('files.0');
    }

    private function user(string $username): User
    {
        $this->seed();

        return User::query()->where('username', $username)->firstOrFail();
    }

    /** @return array<string, int|string> */
    private function reportAttributes(Sector $sector, User $user): array
    {
        return [
            'sector_id' => $sector->id,
            'created_by_user_id' => $user->id,
            'category' => 'Fuga de agua',
            'description' => 'Hay una fuga continua frente a la escuela del sector.',
            'priority' => 'normal',
            'status' => 'pending',
        ];
    }
}
