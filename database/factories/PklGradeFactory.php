<?php

namespace Database\Factories;

use App\Models\PklGrade;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PklGrade>
 */
class PklGradeFactory extends Factory
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
            'score' => '85.00',
            'feedback' => 'Terus tingkatkan hasil belajarmu.',
            'status' => 'Draf',
            'graded_at' => '2026-10-30',
        ];
    }
}
