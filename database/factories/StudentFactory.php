<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nis' => fake()->unique()->numerify('########'),
            'name' => fake()->name(),
            'class' => 'XII RPL 1',
            'major' => 'Rekayasa Perangkat Lunak',
            'email' => fake()->safeEmail(),
        ];
    }
}
