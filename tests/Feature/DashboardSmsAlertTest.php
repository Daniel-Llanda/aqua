<?php

namespace Tests\Feature;

use App\Models\Pond;
use App\Models\SmsAlertCooldown;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DashboardSmsAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_sms_alert_is_sent_for_dangerous_readings_when_phone_is_verified(): void
    {
        $user = User::factory()->create([
            'phone' => '09123456789',
            'phone_verified' => true,
        ]);

        $pond = Pond::create([
            'user_id' => $user->id,
            'hectares' => 1.25,
            'fish_type' => ['Tilapia'],
        ]);

        Http::fake([
            'https://api.semaphore.co/api/v4/messages' => Http::response([['message_id' => 1]], 200),
        ]);

        $response = $this->actingAs($user)->postJson('/dashboard/alerts/sms', [
            'pond_id' => $pond->id,
            'temp' => 33.2,
            'ph' => 8.7,
            'ammonia' => 0.06,
            'ai_text' => 'AI says water is unsafe. Increase aeration and reduce feed.',
            'issues' => ['High water temperature', 'High pH (alkaline water)', 'Elevated ammonia level'],
            'actions' => ['Increase aeration and provide shade.'],
        ]);

        $response->assertOk()->assertJson([
            'sent' => true,
        ]);

        Http::assertSentCount(1);
        $this->assertDatabaseCount('sms_alert_cooldowns', 3);
    }

    public function test_sms_alert_is_skipped_when_phone_is_not_verified(): void
    {
        $user = User::factory()->create([
            'phone' => '09123456789',
            'phone_verified' => false,
        ]);

        $pond = Pond::create([
            'user_id' => $user->id,
            'hectares' => 1.25,
            'fish_type' => ['Tilapia'],
        ]);

        Http::fake();

        $this->actingAs($user)->postJson('/dashboard/alerts/sms', [
            'pond_id' => $pond->id,
            'temp' => 33.2,
            'ph' => 8.7,
            'ammonia' => 0.06,
            'ai_text' => 'test',
        ])->assertOk()->assertJson([
            'sent' => false,
            'reason' => 'phone_not_verified',
        ]);

        Http::assertNothingSent();
        $this->assertDatabaseCount('sms_alert_cooldowns', 0);
    }

    public function test_sms_alert_uses_condition_cooldown_to_prevent_spam(): void
    {
        $user = User::factory()->create([
            'phone' => '09123456789',
            'phone_verified' => true,
        ]);

        $pond = Pond::create([
            'user_id' => $user->id,
            'hectares' => 1.25,
            'fish_type' => ['Tilapia'],
        ]);

        Http::fake([
            'https://api.semaphore.co/api/v4/messages' => Http::response([['message_id' => 1]], 200),
        ]);

        $payload = [
            'pond_id' => $pond->id,
            'temp' => 33.2,
            'ph' => 8.7,
            'ammonia' => 0.06,
            'ai_text' => 'AI says water is unsafe.',
            'issues' => ['High water temperature', 'High pH (alkaline water)', 'Elevated ammonia level'],
            'actions' => ['Increase aeration and provide shade.'],
        ];

        $this->actingAs($user)->postJson('/dashboard/alerts/sms', $payload)
            ->assertOk()
            ->assertJson(['sent' => true]);

        $this->actingAs($user)->postJson('/dashboard/alerts/sms', $payload)
            ->assertOk()
            ->assertJson([
                'sent' => false,
                'reason' => 'cooldown_active',
            ]);

        Http::assertSentCount(1);
        $this->assertDatabaseCount('sms_alert_cooldowns', 3);
    }

    public function test_sms_failure_does_not_break_dashboard_flow(): void
    {
        $user = User::factory()->create([
            'phone' => '09123456789',
            'phone_verified' => true,
        ]);

        $pond = Pond::create([
            'user_id' => $user->id,
            'hectares' => 1.25,
            'fish_type' => ['Tilapia'],
        ]);

        Http::fake([
            'https://api.semaphore.co/api/v4/messages' => Http::response(['error' => 'downstream error'], 500),
        ]);

        $this->actingAs($user)->postJson('/dashboard/alerts/sms', [
            'pond_id' => $pond->id,
            'temp' => 33.2,
            'ph' => 8.7,
            'ammonia' => 0.06,
            'ai_text' => 'AI says water is unsafe.',
        ])->assertOk()->assertJson([
            'sent' => false,
            'reason' => 'sms_failed',
        ]);

        $this->assertDatabaseCount('sms_alert_cooldowns', 0);
        $this->assertSame(0, SmsAlertCooldown::count());
    }
}
