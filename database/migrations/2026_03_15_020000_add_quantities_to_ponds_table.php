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
            $table->unsignedInteger('quantity_of_hatching')->default(0)->after('hatching_date');
            $table->unsignedInteger('quantity_of_harvest')->default(0)->after('quantity_of_hatching');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ponds', function (Blueprint $table) {
            $table->dropColumn(['quantity_of_hatching', 'quantity_of_harvest']);
        });
    }
};
