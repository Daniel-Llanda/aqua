<?php

namespace Tests\Feature;

use App\Models\Pond;
use App\Models\PondCycle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDashboardHarvestComparisonTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_hides_harvest_comparison_until_a_pond_is_selected(): void
    {
        $user = User::factory()->create();

        Pond::create([
            'user_id' => $user->id,
            'hectares' => 1.25,
            'fish_type' => ['Tilapia'],
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $this->assertNull($response->viewData('selectedPond'));
        $this->assertNull($response->viewData('harvestComparison'));
        $response->assertDontSee('Previous vs Latest Harvest');
    }

    public function test_dashboard_harvest_comparison_uses_only_the_selected_users_pond_cycles(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $selectedPond = Pond::create([
            'user_id' => $user->id,
            'hectares' => 1.25,
            'fish_type' => ['Tilapia', 'Catfish'],
        ]);

        $otherUserPond = Pond::create([
            'user_id' => $otherUser->id,
            'hectares' => 2.25,
            'fish_type' => ['Shrimp'],
        ]);

        PondCycle::create([
            'pond_id' => $selectedPond->id,
            'user_id' => $user->id,
            'cycle_number' => 1,
            'status' => 'completed',
            'harvest_date' => now()->subMonths(3)->toDateString(),
            'completed_at' => now()->subMonths(3)->toDateString(),
            'harvest_data' => [
                ['species' => 'Tilapia', 'harvest_kg' => 100, 'unit' => 'kg'],
                ['species' => 'Catfish', 'harvest_kg' => 20.5, 'unit' => 'kg'],
            ],
        ]);

        PondCycle::create([
            'pond_id' => $selectedPond->id,
            'user_id' => $user->id,
            'cycle_number' => 2,
            'status' => 'completed',
            'harvest_date' => now()->subMonth()->toDateString(),
            'completed_at' => now()->subMonth()->toDateString(),
            'harvest_data' => [
                ['species' => 'Tilapia', 'harvest_kg' => 140.25, 'unit' => 'kg'],
                ['species' => 'Bangus', 'harvest_kg' => 35, 'unit' => 'kg'],
            ],
        ]);

        PondCycle::create([
            'pond_id' => $otherUserPond->id,
            'user_id' => $otherUser->id,
            'cycle_number' => 2,
            'status' => 'completed',
            'harvest_date' => now()->subMonth()->toDateString(),
            'completed_at' => now()->subMonth()->toDateString(),
            'harvest_data' => [
                ['species' => 'Shrimp', 'harvest_kg' => 999, 'unit' => 'kg'],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('dashboard', [
            'pond_id' => $selectedPond->id,
        ]));

        $response->assertOk();
        $response->assertSee('Previous vs Latest Harvest');

        $comparison = $response->viewData('harvestComparison');

        $this->assertTrue($comparison['hasComparison']);
        $this->assertSame(['Tilapia', 'Catfish', 'Bangus'], $comparison['labels']);
        $this->assertSame([100.0, 20.5, 0.0], $comparison['previousData']);
        $this->assertSame([140.25, 0.0, 35.0], $comparison['latestData']);
        $this->assertSame(1, $comparison['previousCycle']['cycleNumber']);
        $this->assertSame(2, $comparison['latestCycle']['cycleNumber']);
        $this->assertContains('Catfish was only present in the previous cycle.', $comparison['notes']);
        $this->assertContains('Bangus was only present in the latest cycle.', $comparison['notes']);
        $this->assertNotContains('Shrimp', $comparison['labels']);
    }

    public function test_dashboard_harvest_comparison_shows_empty_state_with_fewer_than_two_completed_cycles(): void
    {
        $user = User::factory()->create();

        $pond = Pond::create([
            'user_id' => $user->id,
            'hectares' => 1.25,
            'fish_type' => ['Tilapia'],
        ]);

        PondCycle::create([
            'pond_id' => $pond->id,
            'user_id' => $user->id,
            'cycle_number' => 1,
            'status' => 'completed',
            'harvest_date' => now()->subMonth()->toDateString(),
            'completed_at' => now()->subMonth()->toDateString(),
            'harvest_data' => [
                ['species' => 'Tilapia', 'harvest_kg' => 100, 'unit' => 'kg'],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('dashboard', [
            'pond_id' => $pond->id,
        ]));

        $response->assertOk();

        $comparison = $response->viewData('harvestComparison');

        $this->assertFalse($comparison['hasComparison']);
        $this->assertSame('Not enough completed harvest cycles to compare yet.', $comparison['message']);
        $response->assertSee('Not enough completed harvest cycles to compare yet.');
    }
}
