<?php

namespace Tests\Feature;

use App\Models\Payload;
use App\Models\Pond;
use App\Models\User;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTelemetryReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_user_can_filter_telemetry_by_day_while_pagination_keeps_filter_query(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-01 12:00:00'));

        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $pond = $this->createPond($user);
        $otherPond = $this->createPond($otherUser);

        for ($index = 0; $index < 11; $index++) {
            $this->createPayload($user, $pond, [
                'ph' => '7.'.$index,
                'temperature' => 28 + $index,
                'mq_ratio' => '0.02',
            ], Carbon::parse('2026-05-01 08:00:00')->addMinutes($index));
        }

        $this->createPayload($user, $pond, [
            'ph' => '4.44',
            'temperature' => 21,
            'mq_ratio' => '0.08',
        ], Carbon::parse('2026-04-30 08:00:00'));

        $this->createPayload($otherUser, $otherPond, [
            'ph' => '12345.678',
            'temperature' => 35,
            'mq_ratio' => '0.11',
        ], Carbon::parse('2026-05-01 08:00:00'));

        $response = $this->actingAs($user)->get(route('telemetrylog', [
            'pond_id' => $pond->id,
            'period' => 'day',
            'filter_date' => '2026-05-01',
        ]));

        $response->assertOk();
        $response->assertSee('Showing Day records for Pond #'.$pond->id);
        $response->assertSee('May 01, 2026 - May 01, 2026');
        $response->assertSee('period=day', false);
        $response->assertSee('filter_date=2026-05-01', false);
        $response->assertDontSee('4.44');
        $response->assertDontSee('12345.678');
    }

    public function test_print_report_includes_only_logged_in_users_records_for_selected_period(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-01 12:00:00'));

        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $pond = $this->createPond($user, ['hectares' => 2.5, 'fish_type' => ['Tilapia', 'Bangus']]);
        $otherPond = $this->createPond($otherUser);

        $this->createPayload($user, $pond, [
            'ph' => '7.25',
            'water_temp' => '29.4',
            'ammonia' => '0.021',
        ], Carbon::parse('2026-04-15 09:30:00'));

        $this->createPayload($user, $pond, [
            'ph' => '3.33',
            'water_temp' => '20.0',
            'ammonia' => '0.200',
        ], Carbon::parse('2026-03-31 09:30:00'));

        $this->createPayload($otherUser, $otherPond, [
            'ph' => '12345.678',
            'water_temp' => '35.0',
            'ammonia' => '0.500',
        ], Carbon::parse('2026-04-15 09:30:00'));

        $response = $this->actingAs($user)->get(route('telemetrylog.report', [
            'pond_id' => $pond->id,
            'period' => 'month',
            'filter_date' => '2026-04-10',
        ]));

        $response->assertOk();
        $response->assertSee('Telemetry Report');
        $response->assertSee('Month');
        $response->assertSee('Apr 01, 2026 12:00 AM - Apr 30, 2026 11:59 PM');
        $response->assertSee('Pond #'.$pond->id.' - 2.50 ha - Tilapia, Bangus');
        $response->assertSee('7.25');
        $response->assertSee('29.4 deg C');
        $response->assertSee('0.021');
        $response->assertDontSee('3.33');
        $response->assertDontSee('12345.678');
    }

    public function test_user_cannot_export_another_users_pond_report(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherPond = $this->createPond($otherUser);

        $this->actingAs($user)->get(route('telemetrylog.report', [
            'pond_id' => $otherPond->id,
            'period' => 'day',
            'filter_date' => '2026-05-01',
        ]))->assertNotFound();
    }

    private function createPond(User $user, array $attributes = []): Pond
    {
        return Pond::create(array_merge([
            'user_id' => $user->id,
            'hectares' => 1.25,
            'fish_type' => ['Tilapia'],
        ], $attributes));
    }

    private function createPayload(User $user, Pond $pond, array $payload, DateTimeInterface $createdAt): Payload
    {
        $record = new Payload([
            'user_id' => $user->id,
            'pond_id' => $pond->id,
            'payload' => $payload,
        ]);
        $record->created_at = $createdAt;
        $record->updated_at = $createdAt;
        $record->save();

        return $record;
    }
}
