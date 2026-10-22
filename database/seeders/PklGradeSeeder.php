<?php

namespace Database\Seeders;

use App\Models\PklGrade;
use Illuminate\Database\Seeder;

class PklGradeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PklGrade::factory(10)->create();
    }
}
