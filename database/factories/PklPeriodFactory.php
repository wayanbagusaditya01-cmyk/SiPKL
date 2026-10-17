<?php

namespace Database\Factories;

use App\Models\PklPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PklPeriod>
 */
class PklPeriodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'academic_year' => '2026/2027',
            'start_date' => '2026-08-01',
            'end_date' => '2026-10-31',
            'status' => 'Aktif',
        ];
    }
}
