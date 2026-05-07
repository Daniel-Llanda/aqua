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
            //
            $table->renameColumn('hatching_date','hatching_started_at');
            $table->json('species_data');
            $table->json('harvest_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ponds', function (Blueprint $table) {
            //
            Schema::dropIfExists('ponds');
        });
    }
};
