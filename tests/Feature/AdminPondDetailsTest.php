<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Pond;
use App\Models\PondCycle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPondDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_hides_removed_summary_cards(): void
    {
        $admin = Admin::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertDontSee('Verified Users');
        $response->assertDontSee('Telemetry Records');
        $response->assertSee('Active Ponds');
        $response->assertSee('Completed Cycles');
    }

    public function test_admin_pond_details_response_includes_only_selected_pond_harvest_data(): void
    {
        $admin = Admin::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $user = User::factory()->create();

        $selectedPond = Pond::create([
            'user_id' => $user->id,
            'hectares' => 1.25,
            'fish_type' => ['Tilapia'],
        ]);

        $otherPond = Pond::create([
            'user_id' => $user->id,
            'hectares' => 2.50,
            'fish_type' => ['Bangus'],
        ]);

        PondCycle::create([
            'pond_id' => $selectedPond->id,
            'user_id' => $user->id,
            'cycle_number' => 2,
            'status' => 'active',
            'hatching_started_at' => now()->toDateString(),
            'harvest_date' => now()->addDays(14)->toDateString(),
            'species_data' => [
                [
                    'species' => 'Tilapia',
                    'hatching_kg' => 50,
                    'expected_harvest_kg' => 50,
                    'unit' => 'kg',
                ],
            ],
        ]);

        PondCycle::create([
            'pond_id' => $selectedPond->id,
            'user_id' => $user->id,
            'cycle_number' => 1,
            'status' => 'completed',
            'hatching_started_at' => now()->subMonths(3)->toDateString(),
            'harvest_date' => now()->subMonths(2)->toDateString(),
            'completed_at' => now()->subMonths(2)->toDateString(),
            'species_data' => [
                [
                    'species' => 'Tilapia',
                    'hatching_kg' => 90,
                    'expected_harvest_kg' => 90,
                    'unit' => 'kg',
                ],
            ],
            'harvest_data' => [
                [
                    'species' => 'Tilapia',
                    'harvest_kg' => 92.5,
                    'unit' => 'kg',
                    'recorded_at' => now()->subMonths(2)->toDateString(),
                ],
            ],
        ]);

        PondCycle::create([
            'pond_id' => $otherPond->id,
            'user_id' => $user->id,
            'cycle_number' => 1,
            'status' => 'completed',
            'hatching_started_at' => now()->subMonths(4)->toDateString(),
            'harvest_date' => now()->subMonths(3)->toDateString(),
            'completed_at' => now()->subMonths(3)->toDateString(),
            'species_data' => [
                [
                    'species' => 'Bangus',
                    'hatching_kg' => 999,
                    'expected_harvest_kg' => 999,
                    'unit' => 'kg',
                ],
            ],
            'harvest_data' => [
                [
                    'species' => 'Bangus',
                    'harvest_kg' => 999,
                    'unit' => 'kg',
                    'recorded_at' => now()->subMonths(3)->toDateString(),
                ],
            ],
        ]);

        $response = $this->actingAs($admin, 'admin')->getJson(route('admin.users.ponds.telemetry', [
            'user' => $user,
            'pond' => $selectedPond,
        ]));

        $response->assertOk();
        $response->assertJsonPath('harvest.summary.completedCycles', 1);
        $response->assertJsonPath('harvest.summary.totalHarvestedKg', 92.5);
        $response->assertJsonPath('harvest.activeCycle.cycleNumber', 2);
        $response->assertJsonPath('harvest.activeCycle.expectedTotalKg', 50);
        $response->assertJsonPath('harvest.latestHarvest.cycleNumber', 1);
        $response->assertJsonPath('harvest.latestHarvest.actualTotalKg', 92.5);
        $response->assertJsonPath('harvest.latestHarvest.speciesBreakdown.0.species', 'Tilapia');
        $response->assertJsonPath('harvest.comparison.hasComparison', false);
        $response->assertJsonPath('harvest.comparison.message', 'Not enough completed harvest cycles to compare yet.');

        $this->assertNotEquals(999.0, data_get($response->json(), 'harvest.summary.totalHarvestedKg'));
    }

    public function test_admin_pond_details_response_includes_selected_pond_harvest_comparison_only(): void
    {
        $admin = Admin::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $selectedPond = Pond::create([
            'user_id' => $user->id,
            'hectares' => 1.25,
            'fish_type' => ['Tilapia', 'Catfish'],
        ]);

        $sameUserOtherPond = Pond::create([
            'user_id' => $user->id,
            'hectares' => 2.50,
            'fish_type' => ['Shrimp'],
        ]);

        $otherUserPond = Pond::create([
            'user_id' => $otherUser->id,
            'hectares' => 3.50,
            'fish_type' => ['Crab'],
        ]);

        PondCycle::create([
            'pond_id' => $selectedPond->id,
            'user_id' => $user->id,
            'cycle_number' => 1,
            'status' => 'completed',
            'harvest_date' => now()->subMonths(4)->toDateString(),
            'completed_at' => now()->subMonths(4)->toDateString(),
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
            'harvest_date' => now()->subMonths(2)->toDateString(),
            'completed_at' => now()->subMonths(2)->toDateString(),
            'harvest_data' => [
                ['species' => 'Tilapia', 'harvest_kg' => 140.25, 'unit' => 'kg'],
                ['species' => 'Bangus', 'harvest_kg' => 35, 'unit' => 'kg'],
            ],
        ]);

        PondCycle::create([
            'pond_id' => $sameUserOtherPond->id,
            'user_id' => $user->id,
            'cycle_number' => 2,
            'status' => 'completed',
            'harvest_date' => now()->subMonth()->toDateString(),
            'completed_at' => now()->subMonth()->toDateString(),
            'harvest_data' => [
                ['species' => 'Shrimp', 'harvest_kg' => 777, 'unit' => 'kg'],
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
                ['species' => 'Crab', 'harvest_kg' => 999, 'unit' => 'kg'],
            ],
        ]);

        $response = $this->actingAs($admin, 'admin')->getJson(route('admin.users.ponds.telemetry', [
            'user' => $user,
            'pond' => $selectedPond,
        ]));

        $response->assertOk();

        $comparison = $response->json('harvest.comparison');

        $this->assertTrue($comparison['hasComparison']);
        $this->assertSame(['Tilapia', 'Catfish', 'Bangus'], $comparison['labels']);
        $this->assertSame([100, 20.5, 0], $comparison['previousData']);
        $this->assertSame([140.25, 0, 35], $comparison['latestData']);
        $this->assertSame(1, $comparison['previousCycle']['cycleNumber']);
        $this->assertSame(2, $comparison['latestCycle']['cycleNumber']);
        $this->assertContains('Catfish was only present in the previous cycle.', $comparison['notes']);
        $this->assertContains('Bangus was only present in the latest cycle.', $comparison['notes']);
        $this->assertNotContains('Shrimp', $comparison['labels']);
        $this->assertNotContains('Crab', $comparison['labels']);
    }
}
