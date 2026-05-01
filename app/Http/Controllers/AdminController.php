<?php

namespace App\Http\Controllers;

use App\Models\Payload;
use App\Models\Pond;
use App\Models\PondCycle;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function index()
    {
        $dashboardMetrics = [
            [
                'label' => 'Total Users',
                'value' => User::count(),
                'description' => 'Registered accounts',
            ],
            [
                'label' => 'Total Ponds',
                'value' => Pond::count(),
                'description' => 'Ponds registered in the system',
            ],
            [
                'label' => 'Active Ponds',
                'value' => PondCycle::where('status', 'active')->distinct('pond_id')->count('pond_id'),
                'description' => 'Ponds with an active cycle',
            ],
            [
                'label' => 'Active Cycles',
                'value' => PondCycle::where('status', 'active')->count(),
                'description' => 'Ongoing production cycles',
            ],
            [
                'label' => 'Completed Cycles',
                'value' => PondCycle::where('status', 'completed')->count(),
                'description' => 'Archived harvest cycles',
            ],
        ];

        return view('admin.dashboard', compact('dashboardMetrics'));
    }

    private function toFloat(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }

    public function user()
    {
        $users = User::with('ponds')->get();
        $pondTelemetryUrlTemplate = route('admin.users.ponds.telemetry', [
            'user' => '__USER__',
            'pond' => '__POND__',
        ], false);

        return view('admin.user', compact('users', 'pondTelemetryUrlTemplate'));
    }

    public function userPondTelemetry(User $user, Pond $pond): JsonResponse
    {
        if ((int) $pond->user_id !== (int) $user->id) {
            abort(404);
        }

        $payloads = Payload::where('user_id', $user->id)
            ->where('pond_id', $pond->id)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            ...$this->buildTelemetrySeries($payloads),
            'harvest' => $this->buildPondHarvestSummary($pond),
        ]);
    }

    private function buildTelemetrySeries($payloads): array
    {
        $labels = [];
        $phData = [];
        $tempData = [];
        $ammoniaData = [];

        foreach ($payloads as $data) {
            $decoded = $data->payload;

            if (!$decoded || !is_array($decoded)) {
                continue;
            }

            $labels[] = $data->created_at
                ? $data->created_at->format('M d, H:i')
                : '';
            $phData[] = $this->toFloat($decoded['ph'] ?? null);
            $tempData[] = $this->toFloat($decoded['water_temp'] ?? $decoded['temperature'] ?? null);
            $ammoniaData[] = $this->toFloat($decoded['mq_ratio'] ?? $decoded['ammonia'] ?? null);
        }

        $hasTelemetry = count($labels) > 0;

        return [
            'labels' => $hasTelemetry ? $labels : ['No readings yet'],
            'phData' => $hasTelemetry ? $phData : [0],
            'tempData' => $hasTelemetry ? $tempData : [0],
            'ammoniaData' => $hasTelemetry ? $ammoniaData : [0],
            'hasTelemetry' => $hasTelemetry,
        ];
    }

    private function buildPondHarvestSummary(Pond $pond): array
    {
        $cycles = PondCycle::where('pond_id', $pond->id)
            ->where('user_id', $pond->user_id)
            ->latest('cycle_number')
            ->get();

        $activeCycle = $cycles->firstWhere('status', 'active');
        $completedCycles = $cycles->where('status', 'completed')->values();
        $latestHarvestCycle = $cycles->first(function (PondCycle $cycle) {
            return !empty($cycle->harvest_data) && is_array($cycle->harvest_data);
        });

        $totalHarvestedKg = (float) $completedCycles->sum(function (PondCycle $cycle) {
            return collect($cycle->harvest_data ?? [])->sum(function ($item) {
                return (float) ($item['harvest_kg'] ?? 0);
            });
        });

        return [
            'hasData' => $cycles->isNotEmpty(),
            'activeCycle' => $activeCycle ? $this->buildCycleHarvestSnapshot($activeCycle) : null,
            'latestHarvest' => $latestHarvestCycle ? $this->buildCycleHarvestSnapshot($latestHarvestCycle) : null,
            'summary' => [
                'completedCycles' => $completedCycles->count(),
                'totalHarvestedKg' => round($totalHarvestedKg, 2),
                'latestCompletedAt' => $completedCycles->first()?->completed_at?->format('M d, Y'),
            ],
            'recentHistory' => $completedCycles
                ->take(3)
                ->map(fn (PondCycle $cycle) => $this->buildCycleHistorySummary($cycle))
                ->values()
                ->all(),
            'comparison' => $this->buildHarvestComparison($completedCycles),
        ];
    }

    private function buildHarvestComparison(Collection $completedCycles): array
    {
        if ($completedCycles->count() < 2) {
            return [
                'hasComparison' => false,
                'message' => 'Not enough completed harvest cycles to compare yet.',
                'labels' => [],
                'previousData' => [],
                'latestData' => [],
                'notes' => [],
                'previousCycle' => null,
                'latestCycle' => null,
            ];
        }

        $latestCycle = $completedCycles->first();
        $previousCycle = $completedCycles->skip(1)->first();
        $previousHarvest = $this->harvestQuantitiesBySpecies($previousCycle->harvest_data);
        $latestHarvest = $this->harvestQuantitiesBySpecies($latestCycle->harvest_data);
        $speciesNames = $previousHarvest->keys()
            ->merge($latestHarvest->keys())
            ->unique()
            ->values();

        if ($speciesNames->isEmpty()) {
            return [
                'hasComparison' => false,
                'message' => 'No valid harvest quantity data is available for the last two completed cycles.',
                'labels' => [],
                'previousData' => [],
                'latestData' => [],
                'notes' => [],
                'previousCycle' => $this->formatHarvestComparisonCycle($previousCycle),
                'latestCycle' => $this->formatHarvestComparisonCycle($latestCycle),
            ];
        }

        $notes = $speciesNames
            ->map(function (string $species) use ($previousHarvest, $latestHarvest) {
                if (!$latestHarvest->has($species)) {
                    return "{$species} was only present in the previous cycle.";
                }

                if (!$previousHarvest->has($species)) {
                    return "{$species} was only present in the latest cycle.";
                }

                return null;
            })
            ->filter()
            ->values()
            ->all();

        return [
            'hasComparison' => true,
            'message' => null,
            'labels' => $speciesNames->all(),
            'previousData' => $speciesNames
                ->map(fn (string $species) => round((float) ($previousHarvest->get($species) ?? 0), 2))
                ->all(),
            'latestData' => $speciesNames
                ->map(fn (string $species) => round((float) ($latestHarvest->get($species) ?? 0), 2))
                ->all(),
            'notes' => $notes,
            'previousCycle' => $this->formatHarvestComparisonCycle($previousCycle),
            'latestCycle' => $this->formatHarvestComparisonCycle($latestCycle),
        ];
    }

    private function harvestQuantitiesBySpecies(mixed $harvestData): Collection
    {
        if (!is_array($harvestData)) {
            return collect();
        }

        return collect($harvestData)->reduce(function (Collection $totals, mixed $item) {
            if (!is_array($item)) {
                return $totals;
            }

            $species = trim((string) ($item['species'] ?? ''));
            $harvestKg = $this->nullableHarvestNumber($item['harvest_kg'] ?? null);

            if ($species === '' || $harvestKg === null || $harvestKg < 0) {
                return $totals;
            }

            $totals->put($species, round((float) ($totals->get($species) ?? 0) + $harvestKg, 2));

            return $totals;
        }, collect());
    }

    private function nullableHarvestNumber(mixed $value): ?float
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function formatHarvestComparisonCycle(PondCycle $cycle): array
    {
        return [
            'cycleNumber' => (int) $cycle->cycle_number,
            'completedAt' => $cycle->completed_at?->format('M d, Y'),
            'harvestDate' => $cycle->harvest_date?->format('M d, Y'),
        ];
    }

    private function buildCycleHarvestSnapshot(PondCycle $cycle): array
    {
        $speciesData = collect($cycle->species_data ?? []);
        $harvestData = collect($cycle->harvest_data ?? []);
        $harvestBySpecies = $harvestData->keyBy('species');
        $expectedTotalKg = (float) $speciesData->sum(function ($item) {
            return (float) ($item['expected_harvest_kg'] ?? $item['hatching_kg'] ?? 0);
        });
        $actualTotalKg = (float) $harvestData->sum(function ($item) {
            return (float) ($item['harvest_kg'] ?? 0);
        });
        $hasHarvestData = $harvestData->isNotEmpty();

        return [
            'cycleNumber' => (int) $cycle->cycle_number,
            'status' => $cycle->status,
            'harvestStatus' => $this->getHarvestStatusLabel($cycle, $hasHarvestData),
            'harvestDate' => $cycle->harvest_date?->format('M d, Y'),
            'completedAt' => $cycle->completed_at?->format('M d, Y'),
            'expectedTotalKg' => round($expectedTotalKg, 2),
            'actualTotalKg' => $hasHarvestData ? round($actualTotalKg, 2) : null,
            'varianceKg' => $hasHarvestData ? round($actualTotalKg - $expectedTotalKg, 2) : null,
            'speciesBreakdown' => $this->buildSpeciesHarvestBreakdown($speciesData, $harvestBySpecies),
        ];
    }

    private function buildSpeciesHarvestBreakdown($speciesData, $harvestBySpecies): array
    {
        return $speciesData->pluck('species')
            ->merge($harvestBySpecies->keys())
            ->filter()
            ->unique()
            ->values()
            ->map(function ($species) use ($speciesData, $harvestBySpecies) {
                $speciesRecord = $speciesData->firstWhere('species', $species) ?? [];
                $harvestRecord = $harvestBySpecies->get($species, []);
                $expectedKg = array_key_exists('expected_harvest_kg', $speciesRecord)
                    ? (float) $speciesRecord['expected_harvest_kg']
                    : (array_key_exists('hatching_kg', $speciesRecord) ? (float) $speciesRecord['hatching_kg'] : null);
                $actualKg = array_key_exists('harvest_kg', $harvestRecord)
                    ? (float) $harvestRecord['harvest_kg']
                    : null;

                return [
                    'species' => $species,
                    'expectedHarvestKg' => $expectedKg !== null ? round($expectedKg, 2) : null,
                    'harvestKg' => $actualKg !== null ? round($actualKg, 2) : null,
                    'varianceKg' => $expectedKg !== null && $actualKg !== null ? round($actualKg - $expectedKg, 2) : null,
                    'recordedAt' => $harvestRecord['recorded_at'] ?? null,
                ];
            })
            ->all();
    }

    private function buildCycleHistorySummary(PondCycle $cycle): array
    {
        $speciesData = collect($cycle->species_data ?? []);
        $harvestData = collect($cycle->harvest_data ?? []);
        $expectedTotalKg = (float) $speciesData->sum(function ($item) {
            return (float) ($item['expected_harvest_kg'] ?? $item['hatching_kg'] ?? 0);
        });
        $actualTotalKg = (float) $harvestData->sum(function ($item) {
            return (float) ($item['harvest_kg'] ?? 0);
        });

        return [
            'cycleNumber' => (int) $cycle->cycle_number,
            'harvestDate' => $cycle->harvest_date?->format('M d, Y'),
            'completedAt' => $cycle->completed_at?->format('M d, Y'),
            'actualTotalKg' => round($actualTotalKg, 2),
            'varianceKg' => round($actualTotalKg - $expectedTotalKg, 2),
        ];
    }

    private function getHarvestStatusLabel(PondCycle $cycle, bool $hasHarvestData): string
    {
        if ($hasHarvestData && $cycle->status === 'completed') {
            return 'Completed';
        }

        if ($hasHarvestData) {
            return 'Harvest data recorded';
        }

        if (!$cycle->harvest_date) {
            return 'Harvest date not set';
        }

        if (now()->startOfDay()->greaterThanOrEqualTo($cycle->harvest_date->copy()->startOfDay())) {
            return 'Harvest window open';
        }

        return 'Awaiting harvest date';
    }

    public function telemetry()
    {
        $payloads = Payload::latest()->get();
        $latestPayload = Payload::latest()->first();

        return view('admin.telemetry', compact('payloads', 'latestPayload'));
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('admin.users')->with('success', 'User created successfully.');

    }

}
