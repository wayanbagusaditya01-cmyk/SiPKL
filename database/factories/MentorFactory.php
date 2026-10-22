<?php

namespace Database\Factories;

use App\Models\Mentor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mentor>
 */
class MentorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'pembina', 'status' => true]),
            'type' => 'Sekolah',
            'nip' => fake()->numerify('########'),
            'position' => null,
            'phone' => fake()->phoneNumber(),
        ];
    }
}
