<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $ponds = DB::table('ponds')->get();

        foreach ($ponds as $pond) {
            $hasCycle = DB::table('pond_cycles')->where('pond_id', $pond->id)->exists();
            if ($hasCycle) {
                continue;
            }

            $hasLegacyData = !is_null($pond->hatching_started_at)
                || !is_null($pond->harvest_date)
                || !is_null($pond->species_data)
                || !is_null($pond->harvest_data);

            if (!$hasLegacyData) {
                continue;
            }

            DB::table('pond_cycles')->insert([
                'pond_id' => $pond->id,
                'user_id' => $pond->user_id,
                'cycle_number' => 1,
                'status' => is_null($pond->harvest_data) ? 'active' : 'completed',
                'hatching_started_at' => $pond->hatching_started_at,
                'harvest_date' => $pond->harvest_date,
                'species_data' => $pond->species_data,
                'harvest_data' => $pond->harvest_data,
                'completed_at' => is_null($pond->harvest_data) ? null : now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left blank to avoid deleting user data.
    }
};
