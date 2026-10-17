<?php

namespace Database\Factories;

use App\Models\JournalEntry;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalEntry>
 */
class JournalEntryFactory extends Factory
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
            'activity_date' => '2026-10-12',
            'title' => fake()->sentence(4),
            'activity' => fake()->paragraph(),
            'documentation_url' => null,
            'status' => 'Draf',
            'mentor_note' => null,
        ];
    }
}
