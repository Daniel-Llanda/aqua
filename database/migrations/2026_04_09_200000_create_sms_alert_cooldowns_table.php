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
        Schema::create('sms_alert_cooldowns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('pond_id');
            $table->string('condition_key', 32);
            $table->timestamp('last_sent_at');
            $table->timestamps();

            $table->unique(['user_id', 'pond_id', 'condition_key'], 'sms_alert_cooldowns_unique');
            $table->index(['user_id', 'pond_id', 'last_sent_at'], 'sms_alert_cooldowns_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_alert_cooldowns');
    }
};
