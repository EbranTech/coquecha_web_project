<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sector_id' => Sector::factory(),
            'created_by_user_id' => User::factory(),
            'category' => fake()->randomElement(['Fuga', 'Sin servicio', 'Calidad del agua']),
            'description' => fake()->paragraph(),
            'priority' => 'normal',
            'status' => 'pending',
        ];
    }
}
