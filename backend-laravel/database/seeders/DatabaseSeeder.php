<?php

namespace Database\Seeders;

use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = collect([
            Role::ADMIN => 'Administración completa de la plataforma.',
            Role::COORDINATOR => 'Operación de sectores, turnos y reportes asignados.',
            Role::NEIGHBOR => 'Consulta de horarios y registro de reportes propios.',
        ])->mapWithKeys(fn (string $description, string $name): array => [
            $name => Role::query()->firstOrCreate(['name' => $name], ['description' => $description]),
        ]);

        $accounts = [
            ['admin', 'Administrador', 'Coquechas', 'ADMIN-001', Role::ADMIN],
            ['coordinador', 'Coordinador', 'Coquechas', 'COORD-001', Role::COORDINATOR],
            ['vecino', 'Vecino', 'Coquechas', 'VECINO-001', Role::NEIGHBOR],
        ];

        foreach ($accounts as [$username, $firstName, $lastName, $document, $role]) {
            $person = Person::query()->firstOrCreate(
                ['document_number' => $document],
                ['first_name' => $firstName, 'last_name' => $lastName, 'status' => 'active'],
            );

            User::query()->updateOrCreate(
                ['username' => $username],
                [
                    'person_id' => $person->id,
                    'role_id' => $roles[$role]->id,
                    'password_hash' => Hash::make('password'),
                    'is_active' => true,
                ],
            );
        }
    }
}
