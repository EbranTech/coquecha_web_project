<?php

namespace Database\Seeders;

use App\Models\Person;
use App\Models\Report;
use App\Models\Resource;
use App\Models\Role;
use App\Models\Sector;
use App\Models\ServiceInterruption;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** Creates clearly marked, fictional records for production Q&A demonstrations. */
class ProductionDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('production') || ! filter_var(env('ALLOW_DEMO_SEEDING', false), FILTER_VALIDATE_BOOL)) {
            throw new \RuntimeException('Demo seeding requires production environment and ALLOW_DEMO_SEEDING=true.');
        }

        $password = env('DEMO_SEED_PASSWORD');
        if (! is_string($password) || strlen($password) < 16) {
            throw new \RuntimeException('DEMO_SEED_PASSWORD must be a temporary password of at least 16 characters.');
        }

        $credentials = [];
        DB::transaction(function () use ($password, &$credentials): void {
            $coordinatorRole = Role::query()->where('name', Role::COORDINATOR)->firstOrFail();
            $neighborRole = Role::query()->where('name', Role::NEIGHBOR)->firstOrFail();

            $sectors = collect([
                ['name' => 'Escuintla', 'location' => 'Escuintla · sector de demostración'],
                ['name' => 'Sanarate', 'location' => 'Sanarate · sector de demostración'],
                ['name' => 'Sector Demostración Coquechas', 'location' => 'Área comunitaria de demostración'],
            ])->map(fn (array $item): Sector => Sector::query()->firstOrCreate(
                ['name' => $item['name']],
                ['description' => 'Sector existente o de demostración; registros ficticios identificados con DEMO.', 'location_description' => $item['location'], 'status' => 'active'],
            ));

            foreach ($sectors as $sectorIndex => $sector) {
                $sectorKey = Str::slug($sector->name);
                $coordinator = $this->demoUser("qa_demo_coord_{$sectorKey}", 'Coordinador Demo', $sector->name, "DEMO-COORD-{$sector->id}", $coordinatorRole->id, $password, $credentials);
                $sector->responsibleUsers()->syncWithoutDetaching([$coordinator->id]);

                $neighbors = collect([1, 2])->map(fn (int $number): User => $this->demoUser(
                    "qa_demo_vecino_{$sectorKey}_{$number}",
                    'Vecino Demo '.$number,
                    $sector->name,
                    "DEMO-VEC-{$sector->id}-{$number}",
                    $neighborRole->id,
                    $password,
                    $credentials,
                ));

                $resource = Resource::query()->firstOrCreate(
                    ['name' => "DEMO · Tanque comunitario · {$sector->name}"],
                    ['resource_type' => 'Tanque', 'description' => 'Recurso ficticio de demostración.', 'capacity' => 5000 + ($sectorIndex * 1000), 'capacity_unit' => 'litros', 'status' => 'available', 'location_description' => $sector->location_description],
                );
                $sector->resources()->syncWithoutDetaching([$resource->id]);

                $reportCases = [
                    ['Fuga de agua', 'high', 'in_progress', 'Se observa una fuga de demostración en una conexión comunitaria.'],
                    ['Baja presión', 'normal', 'pending', 'Vecinos demo reportan baja presión en horario de distribución.'],
                    ['Limpieza de tanque', 'normal', 'resolved', 'Registro demo de limpieza preventiva del tanque comunitario.'],
                ];

                foreach ($reportCases as $index => [$category, $priority, $status, $description]) {
                    $marker = "[DEMO-QA sector={$sector->id} caso=".($index + 1).']';
                    $report = Report::query()->firstOrCreate(
                        ['description' => "{$marker} {$description}"],
                        ['sector_id' => $sector->id, 'created_by_user_id' => $neighbors[$index % 2]->id, 'category' => $category, 'priority' => $priority, 'status' => $status, 'resolved_at' => $status === 'resolved' ? now()->subHours(2) : null],
                    );

                    if ($status !== 'pending') {
                        $report->followups()->firstOrCreate(
                            ['message' => "{$marker} Revisión operativa ficticia registrada para mostrar el seguimiento."],
                            ['user_id' => $coordinator->id, 'new_status' => $status],
                        );
                    }

                    ServiceInterruption::query()->firstOrCreate(
                        ['description' => "{$marker} Aviso operativo ficticio."],
                        ['sector_id' => $sector->id, 'report_id' => $report->id, 'created_by_user_id' => $coordinator->id, 'cause' => $index === 0 ? 'Reparación demo de tubería' : 'Mantenimiento preventivo demo', 'affected_households' => 12 + ($sectorIndex * 7), 'started_at' => now()->subHours(6 + $index), 'restored_at' => $index === 0 ? null : now()->subHours(3)],
                    );
                }

                foreach ([['completed', now()->subDays(1)], ['planned', now()->addDay()]] as $shiftIndex => [$status, $start]) {
                    $shift = Shift::query()->firstOrCreate(
                        ['notes' => "[DEMO-QA sector={$sector->id} turno=".($shiftIndex + 1).'] Turno ficticio para pruebas.'],
                        ['sector_id' => $sector->id, 'responsible_user_id' => $coordinator->id, 'starts_at' => $start->copy()->setTime(9, 0), 'ends_at' => $start->copy()->setTime(11, 0), 'status' => $status],
                    );
                    $shift->resources()->syncWithoutDetaching([$resource->id]);

                    if ($status === 'completed') {
                        $shift->result()->firstOrCreate([], ['delivered_volume' => 3200 + ($sectorIndex * 400), 'volume_unit' => 'litros', 'notes' => 'Resultado ficticio de demostración.', 'recorded_by_user_id' => $coordinator->id]);
                    }
                }
            }
        });

        foreach ($credentials as $username => $created) {
            if ($created) {
                $this->command?->line("DEMO LOGIN {$username} / (temporary password supplied to deployment operator)");
            }
        }
        $this->command?->info('Production demo records are loaded. Existing non-demo records were preserved.');
    }

    /** @param array<string, bool> $credentials */
    private function demoUser(string $username, string $firstName, string $lastName, string $document, int $roleId, string $password, array &$credentials): User
    {
        $person = Person::query()->firstOrCreate(
            ['document_number' => $document],
            ['first_name' => $firstName, 'last_name' => $lastName, 'email' => $username.'@example.invalid', 'status' => 'active'],
        );

        $existing = User::query()->where('username', $username)->first();
        if ($existing) {
            $credentials[$username] = false;
            return $existing;
        }

        $credentials[$username] = true;
        return User::query()->create(['person_id' => $person->id, 'role_id' => $roleId, 'username' => $username, 'password_hash' => Hash::make($password), 'is_active' => true]);
    }
}
