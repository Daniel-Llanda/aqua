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
        Schema::create('pond_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pond_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('cycle_number');
            $table->string('status')->default('active');
            $table->date('hatching_started_at')->nullable();
            $table->date('harvest_date')->nullable();
            $table->json('species_data')->nullable();
            $table->json('harvest_data')->nullable();
            $table->date('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['pond_id', 'cycle_number']);
            $table->index(['pond_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pond_cycles');
    }
};
