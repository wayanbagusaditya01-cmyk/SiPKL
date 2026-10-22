<?php

namespace Database\Factories;

use App\Models\Mentor;
use App\Models\Place;
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

    /**
     * Pembina Sekolah dengan akun ber-role pembina_sekolah.
     */
    public function school(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory()->state(['role' => 'pembina_sekolah', 'status' => true]),
            'type' => Mentor::SCHOOL,
            'place_id' => null,
        ]);
    }

    /**
     * Pembina Industri dengan akun ber-role pembina_industri pada tempat PKL tertentu.
     */
    public function industry(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory()->state(['role' => 'pembina_industri', 'status' => true]),
            'type' => Mentor::INDUSTRY,
            'place_id' => Place::factory(),
            'nip' => null,
            'position' => fake()->jobTitle(),
        ]);
    }
}
