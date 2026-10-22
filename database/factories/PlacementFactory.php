<?php

namespace Database\Factories;

use App\Models\Place;
use App\Models\Placement;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Placement>
 */
class PlacementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'place_id' => Place::factory(),
            'status' => 'Aktif',
        ];
    }
}
