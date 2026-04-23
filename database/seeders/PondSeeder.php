<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Pond;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PondSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Pond::insert([
            [
                'user_id' => 1,
                'hectares' => 1.5,
                'fish_type' => json_encode([
                    'Tilapia (General)'
                ]),
                'hatching_started_at' => Carbon::parse('2025-01-15'),
                'harvest_date' => Carbon::parse('2025-05-15'),
                'species_data' => null,
                'harvest_data' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 1,
                'hectares' => 2.0,
                'fish_type' => json_encode([
                    'Penaeid Shrimp (General)'
                ]),
                'hatching_started_at' => Carbon::parse('2025-02-01'),
                'harvest_date' => Carbon::parse('2025-06-01'),
                'species_data' => null,
                'harvest_data' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 2,
                'hectares' => 1.2,
                'fish_type' => json_encode([
                    'Bangus (Milkfish)',
                    'Tilapia (General)'
                ]),
                'hatching_started_at' => Carbon::parse('2025-01-20'),
                'harvest_date' => Carbon::parse('2025-05-20'),
                'species_data' => null,
                'harvest_data' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 2,
                'hectares' => 0.8,
                'fish_type' => json_encode([
                    'Atlantic Blue Crab (Callinectes sapidus)'
                ]),
                'hatching_started_at' => Carbon::parse('2025-03-01'),
                'harvest_date' => Carbon::parse('2025-07-01'),
                'species_data' => null,
                'harvest_data' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

    }
}


