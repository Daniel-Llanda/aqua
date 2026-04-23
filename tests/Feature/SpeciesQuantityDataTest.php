<?php

namespace Tests\Feature;

use App\Models\Pond;
use App\Models\PondCycle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpeciesQuantityDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_expected_harvest_quantity_field_is_read_only_in_the_species_form(): void
    {
        $user = User::factory()->create();

        $pond = Pond::create([
            'user_id' => $user->id,
            'hectares' => 1.5,
            'fish_type' => ['Tilapia'],
        ]);

        $cycle = PondCycle::create([
            'pond_id' => $pond->id,
            'user_id' => $user->id,
            'cycle_number' => 1,
            'status' => 'active',
            'hatching_started_at' => now()->toDateString(),
            'harvest_date' => now()->addDays(30)->toDateString(),
            'species_data' => [
                [
                    'species' => 'Tilapia',
                    'hatching_kg' => null,
                    'expected_harvest_kg' => null,
                    'unit' => 'kg',
                ],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('pond.cycle.species-data.form', [$pond, $cycle]));

        $response->assertOk();
        $response->assertSee('Expected Harvest Quantity (kg)');
        $response->assertSee('readonly', false);
        $response->assertSee('This value automatically matches the hatching quantity.');
    }

    public function test_expected_harvest_quantity_is_saved_from_hatching_quantity_only(): void
    {
        $user = User::factory()->create();

        $pond = Pond::create([
            'user_id' => $user->id,
            'hectares' => 1.5,
            'fish_type' => ['Tilapia'],
        ]);

        $cycle = PondCycle::create([
            'pond_id' => $pond->id,
            'user_id' => $user->id,
            'cycle_number' => 1,
            'status' => 'active',
            'hatching_started_at' => now()->toDateString(),
            'harvest_date' => now()->addDays(30)->toDateString(),
            'species_data' => [
                [
                    'species' => 'Tilapia',
                    'hatching_kg' => null,
                    'expected_harvest_kg' => null,
                    'unit' => 'kg',
                ],
            ],
        ]);

        $response = $this->actingAs($user)->post(route('pond.cycle.species-data.store', [$pond, $cycle]), [
            'species_data' => [
                [
                    'species' => 'Tilapia',
                    'hatching_kg' => '125.75',
                    'expected_harvest_kg' => '999.99',
                ],
            ],
        ]);

        $response->assertRedirect(route('pond-info'));

        $cycle->refresh();

        $this->assertEquals(125.75, $cycle->species_data[0]['hatching_kg']);
        $this->assertEquals(125.75, $cycle->species_data[0]['expected_harvest_kg']);
    }
}
