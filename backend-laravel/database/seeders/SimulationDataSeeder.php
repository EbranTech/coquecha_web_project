<?php

namespace Database\Seeders;

use App\Models\Report;
use App\Models\Resource;
use App\Models\Sector;
use App\Models\ServiceInterruption;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SimulationDataSeeder extends Seeder
{
    private const RECORDS_PER_SECTOR = 15;

    /** Load deterministic, non-destructive data for the local QA environment. */
    public function run(): void
    {
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $neighbor = User::query()->where('username', 'vecino')->firstOrFail();
        $sectors = Sector::query()->where('status', 'active')->orderBy('id')->get();

        $categories = ['Fuga de agua', 'Baja presión', 'Tubería obstruida', 'Falla de bomba', 'Calidad del agua'];
        $priorities = ['low', 'normal', 'high', 'urgent'];
        $reportStatuses = ['pending', 'in_progress', 'resolved', 'closed'];
        $interruptionCauses = ['Mantenimiento preventivo', 'Reparación de tubería', 'Corte eléctrico', 'Limpieza de tanque', 'Fuga detectada'];
        $shiftStatuses = ['completed', 'completed', 'completed', 'planned', 'in_progress', 'cancelled'];
        $firstShift = now()->startOfDay()->subDays(8);

        foreach ($sectors as $sectorIndex => $sector) {
            $operator = $sector->responsibleUsers()->first() ?? $admin;
            $resources = Resource::query()
                ->where('status', 'available')
                ->whereHas('sectors', fn ($query) => $query->whereKey($sector->id))
                ->orderBy('id')
                ->get();

            if ($resources->isEmpty()) {
                $this->command?->warn("{$sector->name}: no tiene recursos disponibles; no se crearon turnos simulados.");
            }

            for ($index = 1; $index <= self::RECORDS_PER_SECTOR; $index++) {
                $marker = "[SIMULACION {$sector->id}-{$index}]";
                $status = $reportStatuses[($index - 1) % count($reportStatuses)];
                $creator = $index % 3 === 0 ? $admin : $neighbor;
                $createdAt = now()->subDays(self::RECORDS_PER_SECTOR - $index)->subHours($sectorIndex);

                $report = Report::query()->firstOrCreate(
                    ['description' => "$marker Reporte simulado para observar el flujo de atención en {$sector->name}."],
                    [
                        'sector_id' => $sector->id,
                        'created_by_user_id' => $creator->id,
                        'category' => $categories[($index - 1) % count($categories)],
                        'priority' => $priorities[($index - 1) % count($priorities)],
                        'status' => $status,
                        'resolved_at' => in_array($status, ['resolved', 'closed'], true) ? $createdAt->copy()->addHours(6) : null,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ],
                );

                if ($status !== 'pending') {
                    $report->followups()->firstOrCreate(
                        ['message' => "$marker Se realizó una revisión operativa del reporte."],
                        ['user_id' => $operator->id, 'new_status' => $status, 'created_at' => $createdAt->copy()->addHour(), 'updated_at' => $createdAt->copy()->addHour()],
                    );
                }

                if ($resources->isNotEmpty()) {
                    $startsAt = $firstShift->copy()->addHours((($sectorIndex * self::RECORDS_PER_SECTOR) + $index) * 4);
                    $shiftStatus = $shiftStatuses[($index - 1) % count($shiftStatuses)];
                    $shift = Shift::query()->firstOrCreate(
                        ['notes' => "$marker Turno de distribución simulado."],
                        [
                            'sector_id' => $sector->id,
                            'responsible_user_id' => $operator->id,
                            'starts_at' => $startsAt,
                            'ends_at' => $startsAt->copy()->addHours(2),
                            'status' => $shiftStatus,
                            'created_at' => $startsAt->copy()->subDay(),
                            'updated_at' => $startsAt,
                        ],
                    );
                    $shift->resources()->syncWithoutDetaching([$resources[($index - 1) % $resources->count()]->id]);

                    if ($shiftStatus === 'completed') {
                        $shift->result()->updateOrCreate([], [
                            'delivered_volume' => 850 + ($index * 75),
                            'volume_unit' => 'litros',
                            'notes' => "$marker Volumen de demostración.",
                            'recorded_by_user_id' => $operator->id,
                        ]);
                    }
                }

                $interruptionStart = Carbon::now()->subDays((self::RECORDS_PER_SECTOR - $index) * 2 + $sectorIndex)->setTime(8 + ($index % 6), 0);
                ServiceInterruption::query()->firstOrCreate(
                    ['description' => "$marker Interrupción simulada para pruebas visuales."],
                    [
                        'sector_id' => $sector->id,
                        'report_id' => $report->id,
                        'created_by_user_id' => $operator->id,
                        'cause' => $interruptionCauses[($index - 1) % count($interruptionCauses)],
                        'affected_households' => 8 + ($index * 3),
                        'started_at' => $interruptionStart,
                        'restored_at' => $index <= 12 ? $interruptionStart->copy()->addHours(3 + ($index % 4)) : null,
                    ],
                );
            }
        }
    }
}
