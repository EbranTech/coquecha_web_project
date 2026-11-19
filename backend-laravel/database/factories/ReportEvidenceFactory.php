<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\ReportEvidence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportEvidence>
 */
class ReportEvidenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'report_id' => Report::factory(),
            'uploaded_by_user_id' => User::factory(),
            'original_name' => 'evidencia.pdf',
            'storage_key' => 'reports/'.fake()->uuid().'/evidencia.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
        ];
    }
}
