<?php

namespace Database\Factories;

use App\Models\Place;
use App\Models\PlaceHoliday;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlaceHoliday>
 */
class PlaceHolidayFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'place_id' => Place::factory(),
            'start_date' => '2026-10-15',
            'end_date' => '2026-10-15',
            'name' => 'Libur perusahaan',
        ];
    }
}
