<?php

namespace Database\Seeders;

use App\Models\PklPeriod;
use Illuminate\Database\Seeder;

class PklPeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PklPeriod::factory(2)->create();
    }
}
