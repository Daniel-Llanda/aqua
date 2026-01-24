<?php

namespace Database\Seeders;

use App\Models\Payload;
use App\Models\Pond;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PayloadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $users = User::all();
        $ponds = Pond::all();

        foreach ($users as $user) {
            foreach ($ponds as $pond) {
                Payload::create([
                    'user_id' => $user->id,
                    'pond_id' => $pond->id,
                    'payload' => [
                        'ph' => round(mt_rand(65, 85) / 10, 1),          // 6.5 – 8.5
                        'water_temp' => mt_rand(26, 34),                // °C
                        'ammonia' => round(mt_rand(0, 30) / 100, 2),     // 0.00 – 0.30
                    ],
                ]);
            }
        }
    }
}
