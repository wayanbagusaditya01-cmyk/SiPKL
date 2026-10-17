<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
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
            'attendance_date' => '2026-10-12',
            'status' => 'Hadir',
            'time_in' => '08:00',
            'time_out' => '16:00',
            'note' => null,
        ];
    }
}
