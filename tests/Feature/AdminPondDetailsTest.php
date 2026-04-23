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

        $this->assertNotEquals(999.0, data_get($response->json(), 'harvest.summary.totalHarvestedKg'));
    }
}
