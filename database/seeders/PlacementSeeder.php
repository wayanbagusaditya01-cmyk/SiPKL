<?php

namespace Database\Seeders;

use App\Models\Placement;
use Illuminate\Database\Seeder;

class PlacementSeeder extends Seeder
{
    public function run(): void
    {
        Placement::factory(10)->create();
    }
}
