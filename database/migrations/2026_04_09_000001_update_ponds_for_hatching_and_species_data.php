<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ponds', function (Blueprint $table) {
            $table->date('hatching_started_at')->nullable()->after('fish_type');
            $table->json('species_data')->nullable()->after('hatching_started_at');
            $table->dropColumn(['hatching_date', 'harvest_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ponds', function (Blueprint $table) {
            $table->date('hatching_date')->nullable()->after('species_data');
            $table->date('harvest_date')->nullable()->after('hatching_date');
            $table->dropColumn(['hatching_started_at', 'species_data']);
        });
    }
};