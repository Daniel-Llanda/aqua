<?php

namespace App\Services;

use App\Models\Payload;
use App\Models\Pond;
use App\Models\PondCycle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class HarvestAiAnalysisService
{
    public function __construct(private readonly OpenRouterService $openRouterService) {}

    public function analyzePond(int $userId, Pond $pond): array
    {
        $prepared = $this->buildAnalysisContext($userId, $pond);

        if (! $prepared['canAnalyze']) {
            return [
                'ok' => false,
                'canAnalyze' => false,
                'message' => $prepared['message'],
                'analysis' => null,
                'cached' => false,
            ];
        }

        $cacheKey = $this->cacheKey($userId, $pond, $prepared['context']);
        $cachedAnalysis = Cache::get($cacheKey);

        if ($this->isUsableAnalysis($cachedAnalysis, $prepared['context'])) {
            return [
                'ok' => true,
                'canAnalyze' => true,
                'message' => null,
                'analysis' => $cachedAnalysis,
                'cached' => true,
            ];
        }

        $result = $this->openRouterService->generateHarvestAnalysis($prepared['context']);

        if (! $result['ok']) {
            $fallbackAnalysis = $this->buildFallbackAnalysis($prepared['context'], $result['message']);

            return [
                'ok' => true,
                'canAnalyze' => true,
                'message' => $result['message'],
                'analysis' => $fallbackAnalysis,
                'cached' => false,
                'fallback' => true,
            ];
        }

        if (! $this->isUsableAnalysis($result['analysis'], $prepared['context'])) {
            $fallbackAnalysis = $this->buildFallbackAnalysis($prepared['context'], 'OpenRouter returned an invalid or generic harvest analysis.');

            return [
                'ok' => true,
                'canAnalyze' => true,
                'message' => 'OpenRouter returned an invalid or generic harvest analysis.',
                'analysis' => $fallbackAnalysis,
                'cached' => false,
                'fallback' => true,
            ];
        }

        Cache::put($cacheKey, $result['analysis'], now()->addHours(12));

        return [
            'ok' => true,
            'canAnalyze' => true,
            'message' => null,
            'analysis' => $result['analysis'],
            'cached' => false,
            'fallback' => false,
        ];
    }

    private function buildAnalysisContext(int $userId, Pond $pond): array
    {
        $completedCycles = PondCycle::where('user_id', $userId)
            ->where('pond_id', $pond->id)
            ->where('status', 'completed')
            ->orderByDesc('cycle_number')
            ->limit(2)
            ->get();

        if ($completedCycles->count() < 2) {
            return [
                'canAnalyze' => false,
                'message' => 'Not enough completed harvest cycles to generate AI analysis yet.',
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
                'canAnalyze' => false,
                'message' => 'AI analysis cannot be generated because valid harvest quantity data is missing for the last two completed cycles.',
            ];
        }

        $previousTotal = round((float) $previousHarvest->sum(), 2);
        $latestTotal = round((float) $latestHarvest->sum(), 2);
        $difference = round($latestTotal - $previousTotal, 2);

        $context = [
            'pond' => [
                'id' => (int) $pond->id,
                'display_name' => 'Pond #'.$pond->id,
                'hectares' => $this->nullableNumber($pond->hectares),
                'registered_species' => is_array($pond->fish_type) ? array_values($pond->fish_type) : [],
            ],
            'comparison' => [
                'previous_cycle_number' => (int) $previousCycle->cycle_number,
                'latest_cycle_number' => (int) $latestCycle->cycle_number,
                'previous_total_harvest_kg' => $previousTotal,
                'latest_total_harvest_kg' => $latestTotal,
                'latest_minus_previous_kg' => $difference,
                'percent_change' => $previousTotal > 0 ? round(($difference / $previousTotal) * 100, 2) : null,
                'species_only_in_previous_cycle' => $previousHarvest->keys()->diff($latestHarvest->keys())->values()->all(),
                'species_only_in_latest_cycle' => $latestHarvest->keys()->diff($previousHarvest->keys())->values()->all(),
                'harvest_by_species_kg' => $speciesNames
                    ->map(fn (string $species) => [
                        'species' => $species,
                        'previous_cycle_kg' => round((float) ($previousHarvest->get($species) ?? 0), 2),
                        'latest_cycle_kg' => round((float) ($latestHarvest->get($species) ?? 0), 2),
                        'latest_minus_previous_kg' => round((float) ($latestHarvest->get($species) ?? 0) - (float) ($previousHarvest->get($species) ?? 0), 2),
                    ])
                    ->all(),
            ],
            'previous_cycle' => $this->buildCycleContext($previousCycle, $previousHarvest, $userId, (int) $pond->id),
            'latest_cycle' => $this->buildCycleContext($latestCycle, $latestHarvest, $userId, (int) $pond->id),
        ];

        return [
            'canAnalyze' => true,
            'message' => null,
            'context' => $context,
        ];
    }

    private function buildCycleContext(PondCycle $cycle, Collection $harvestBySpecies, int $userId, int $pondId): array
    {
        $speciesData = collect(is_array($cycle->species_data) ? $cycle->species_data : []);
        $speciesNames = $speciesData->pluck('species')
            ->merge($harvestBySpecies->keys())
            ->filter()
            ->unique()
            ->values();

        $hatchingTotal = 0.0;
        $expectedTotal = 0.0;

        $species = $speciesNames
            ->map(function (string $species) use ($speciesData, $harvestBySpecies, &$hatchingTotal, &$expectedTotal) {
                $speciesRecord = $speciesData->firstWhere('species', $species) ?? [];
                $hatchingKg = $this->nullableNumber($speciesRecord['hatching_kg'] ?? null);
                $expectedHarvestKg = $this->nullableNumber($speciesRecord['expected_harvest_kg'] ?? $speciesRecord['hatching_kg'] ?? null);
                $harvestKg = $this->nullableNumber($harvestBySpecies->get($species));

                $hatchingTotal += $hatchingKg ?? 0.0;
                $expectedTotal += $expectedHarvestKg ?? 0.0;

                return [
                    'species' => $species,
                    'hatching_kg' => $hatchingKg,
                    'expected_harvest_kg' => $expectedHarvestKg,
                    'actual_harvest_kg' => $harvestKg,
                    'actual_minus_expected_kg' => $harvestKg !== null && $expectedHarvestKg !== null
                        ? round($harvestKg - $expectedHarvestKg, 2)
                        : null,
                ];
            })
            ->all();

        $actualTotal = round((float) $harvestBySpecies->sum(), 2);

        return [
            'cycle_number' => (int) $cycle->cycle_number,
            'status' => $cycle->status,
            'hatching_started_at' => $cycle->hatching_started_at?->toDateString(),
            'harvest_date' => $cycle->harvest_date?->toDateString(),
            'completed_at' => $cycle->completed_at?->toDateString(),
            'duration_days' => $this->cycleDurationDays($cycle),
            'hatching_total_kg' => round($hatchingTotal, 2),
            'expected_harvest_total_kg' => round($expectedTotal, 2),
            'actual_harvest_total_kg' => $actualTotal,
            'actual_minus_expected_kg' => round($actualTotal - $expectedTotal, 2),
            'species' => $species,
            'telemetry_summary' => $this->summarizeTelemetryForCycle($cycle, $userId, $pondId),
        ];
    }

    private function summarizeTelemetryForCycle(PondCycle $cycle, int $userId, int $pondId): array
    {
        $start = ($cycle->hatching_started_at ?? $cycle->created_at)?->copy()->startOfDay();
        $endSource = $cycle->completed_at ?? $cycle->harvest_date ?? $cycle->updated_at ?? $cycle->created_at;
        $end = $endSource?->copy()->endOfDay();

        if (! $start || ! $end || $end->lt($start)) {
            return [
                'record_count' => 0,
                'date_range' => null,
                'ph' => $this->emptyMetricSummary(),
                'water_temperature_c' => $this->emptyMetricSummary(),
                'ammonia_ppm' => $this->emptyMetricSummary(),
            ];
        }

        $records = Payload::where('user_id', $userId)
            ->where('pond_id', $pondId)
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get(['payload', 'created_at']);

        return [
            'record_count' => $records->count(),
            'date_range' => [
                'from' => $start->toDateString(),
                'to' => $end->toDateString(),
            ],
            'ph' => $this->metricSummary($records, ['ph'], 2),
            'water_temperature_c' => $this->metricSummary($records, ['water_temp', 'temperature'], 1),
            'ammonia_ppm' => $this->metricSummary($records, ['mq_ratio', 'ammonia'], 3),
        ];
    }

    private function metricSummary(Collection $records, array $keys, int $precision): array
    {
        $values = [];

        foreach ($records as $record) {
            $payload = is_array($record->payload) ? $record->payload : [];

            foreach ($keys as $key) {
                $value = $this->nullableNumber($payload[$key] ?? null);

                if ($value !== null) {
                    $values[] = $value;
                    break;
                }
            }
        }

        if ($values === []) {
            return $this->emptyMetricSummary();
        }

        $first = $values[0];
        $last = $values[array_key_last($values)];

        return [
            'samples' => count($values),
            'avg' => round(array_sum($values) / count($values), $precision),
            'min' => round(min($values), $precision),
            'max' => round(max($values), $precision),
            'first' => round($first, $precision),
            'last' => round($last, $precision),
            'change' => round($last - $first, $precision),
        ];
    }

    private function emptyMetricSummary(): array
    {
        return [
            'samples' => 0,
            'avg' => null,
            'min' => null,
            'max' => null,
            'first' => null,
            'last' => null,
            'change' => null,
        ];
    }

    private function harvestQuantitiesBySpecies(mixed $harvestData): Collection
    {
        if (! is_array($harvestData)) {
            return collect();
        }

        return collect($harvestData)->reduce(function (Collection $totals, mixed $item) {
            if (! is_array($item)) {
                return $totals;
            }

            $species = trim((string) ($item['species'] ?? ''));
            $harvestKg = $this->nullableNumber($item['harvest_kg'] ?? null);

            if ($species === '' || $harvestKg === null || $harvestKg < 0) {
                return $totals;
            }

            $totals->put($species, round((float) ($totals->get($species) ?? 0) + $harvestKg, 2));

            return $totals;
        }, collect());
    }

    private function cycleDurationDays(PondCycle $cycle): ?int
    {
        $start = $cycle->hatching_started_at;
        $end = $cycle->completed_at ?? $cycle->harvest_date;

        if (! $start || ! $end || $end->lt($start)) {
            return null;
        }

        return $start->diffInDays($end) + 1;
    }

    private function nullableNumber(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function cacheKey(int $userId, Pond $pond, array $context): string
    {
        return 'harvest-ai-analysis:v5:user:'.$userId.':pond:'.$pond->id.':'.sha1(json_encode($context) ?: '');
    }

    private function isUsableAnalysis(mixed $analysis, ?array $context = null): bool
    {
        if (! is_string($analysis) || trim($analysis) === '') {
            return false;
        }

        $analysis = trim($analysis);
        $lowerAnalysis = mb_strtolower($analysis);

        if ($this->sentenceCount($analysis) > 3) {
            return false;
        }

        if (collect([
            'AI harvest analysis returned an empty response.',
            'The analysis should',
            'The output must',
            'Use this format',
            'This sentence should',
            'Write 2 to 3 sentences',
            'Format: Summary',
            'Here is the analysis',
            'Based on the provided data',
            'Based on the provided JSON',
            'Summary:',
            'Reason:',
            'Recommendation:',
            'For the next cycle',
            'the question says',
            'the question asks',
            'the user said',
            'the user asked',
            'format question',
            'wait,',
        ])->contains(fn (string $needle) => str_contains($lowerAnalysis, mb_strtolower($needle)))) {
            return false;
        }

        if ($context === null) {
            return true;
        }

        return $this->answersHarvestComparison($analysis)
            && $this->hasPondSpecificEvidence($analysis, $context);
    }

    private function buildFallbackAnalysis(array $context, ?string $reason): string
    {
        $comparison = $context['comparison'];
        $previousTotal = (float) $comparison['previous_total_harvest_kg'];
        $latestTotal = (float) $comparison['latest_total_harvest_kg'];
        $difference = (float) $comparison['latest_minus_previous_kg'];
        $sentences = [
            $this->fallbackResultSentence($previousTotal, $latestTotal, $difference, $comparison['percent_change']),
            $this->fallbackFactorSentence($context),
        ];

        return implode(' ', array_filter($sentences));
    }

    private function fallbackResultSentence(float $previousTotal, float $latestTotal, float $difference, mixed $percentChange): string
    {
        $percent = $percentChange !== null
            ? ' ('.number_format(abs((float) $percentChange), 2).'%)'
            : '';

        if ($difference > 0) {
            return "The latest cycle had a better harvest, producing {$this->formatKg($latestTotal)} versus {$this->formatKg($previousTotal)} in the previous cycle, a {$this->formatKg(abs($difference))}{$percent} increase.";
        }

        if ($difference < 0) {
            return "The previous cycle had a better harvest, producing {$this->formatKg($previousTotal)} versus {$this->formatKg($latestTotal)} in the latest cycle, a {$this->formatKg(abs($difference))}{$percent} advantage.";
        }

        return "Neither cycle had a better harvest because both produced {$this->formatKg($latestTotal)}.";
    }

    private function fallbackFactorSentence(array $context): string
    {
        $factors = array_values(array_filter([
            $this->fallbackSpeciesChangePhrase($context),
            $this->fallbackHatchingPhrase($context),
            $this->fallbackWaterQualityPhrase($context),
            $this->fallbackExpectedHarvestPhrase($context),
            $this->fallbackDurationPhrase($context),
        ]));

        if ($factors === []) {
            return 'The available records do not point to one clear driver beyond the recorded harvest totals by species.';
        }

        return 'The difference may be tied to '.$this->joinPhrases(array_slice($factors, 0, 3)).'.';
    }

    private function fallbackSpeciesChangePhrase(array $context): ?string
    {
        $difference = (float) $context['comparison']['latest_minus_previous_kg'];
        $latestWon = $difference > 0;
        $previousWon = $difference < 0;
        $winnerOnlySpecies = $latestWon
            ? ($context['comparison']['species_only_in_latest_cycle'] ?? [])
            : ($context['comparison']['species_only_in_previous_cycle'] ?? []);

        $changes = collect($context['comparison']['harvest_by_species_kg'] ?? [])
            ->filter(fn (array $species) => (float) ($species['latest_minus_previous_kg'] ?? 0) !== 0.0);

        if ($changes->isEmpty()) {
            return null;
        }

        if ($winnerOnlySpecies !== []) {
            $onlySpeciesChange = $changes
                ->filter(fn (array $species) => in_array($species['species'] ?? null, $winnerOnlySpecies, true))
                ->sortByDesc(fn (array $species) => max(
                    (float) ($species['previous_cycle_kg'] ?? 0),
                    (float) ($species['latest_cycle_kg'] ?? 0)
                ))
                ->first();

            if (is_array($onlySpeciesChange)) {
                $speciesName = trim((string) ($onlySpeciesChange['species'] ?? ''));
                $previousKg = (float) ($onlySpeciesChange['previous_cycle_kg'] ?? 0);
                $latestKg = (float) ($onlySpeciesChange['latest_cycle_kg'] ?? 0);

                if ($latestWon && $speciesName !== '') {
                    return "{$speciesName} adding {$this->formatKg($latestKg)} in the latest cycle";
                }

                if ($previousWon && $speciesName !== '') {
                    return "{$speciesName} dropping from {$this->formatKg($previousKg)} to no recorded latest harvest";
                }
            }
        }

        $alignedChanges = $changes->filter(function (array $species) use ($latestWon, $previousWon) {
            $speciesDifference = (float) ($species['latest_minus_previous_kg'] ?? 0);

            return ($latestWon && $speciesDifference > 0) || ($previousWon && $speciesDifference < 0);
        });

        $topChange = ($alignedChanges->isNotEmpty() ? $alignedChanges : $changes)
            ->sortByDesc(fn (array $species) => abs((float) ($species['latest_minus_previous_kg'] ?? 0)))
            ->first();

        if (! is_array($topChange)) {
            return null;
        }

        $speciesName = trim((string) ($topChange['species'] ?? ''));
        $previousKg = (float) ($topChange['previous_cycle_kg'] ?? 0);
        $latestKg = (float) ($topChange['latest_cycle_kg'] ?? 0);

        if ($speciesName === '') {
            return null;
        }

        if ($previousKg <= 0 && $latestKg > 0) {
            return "{$speciesName} adding {$this->formatKg($latestKg)} in the latest cycle";
        }

        if ($latestKg <= 0 && $previousKg > 0) {
            return "{$speciesName} dropping from {$this->formatKg($previousKg)} to no recorded latest harvest";
        }

        $trend = $latestKg >= $previousKg ? 'rising' : 'falling';

        return "{$speciesName} harvest {$trend} from {$this->formatKg($previousKg)} to {$this->formatKg($latestKg)}";
    }

    private function fallbackHatchingPhrase(array $context): ?string
    {
        $previousHatching = (float) $context['previous_cycle']['hatching_total_kg'];
        $latestHatching = (float) $context['latest_cycle']['hatching_total_kg'];

        if ($previousHatching <= 0 && $latestHatching <= 0) {
            return null;
        }

        if ($previousHatching === $latestHatching) {
            return null;
        }

        $trend = $latestHatching >= $previousHatching ? 'increasing' : 'falling';

        return "hatching quantity {$trend} from {$this->formatKg($previousHatching)} to {$this->formatKg($latestHatching)}";
    }

    private function fallbackExpectedHarvestPhrase(array $context): ?string
    {
        $previousExpected = (float) $context['previous_cycle']['expected_harvest_total_kg'];
        $latestExpected = (float) $context['latest_cycle']['expected_harvest_total_kg'];

        if ($previousExpected <= 0 || $latestExpected <= 0) {
            return null;
        }

        $previousGap = (float) $context['previous_cycle']['actual_minus_expected_kg'];
        $latestGap = (float) $context['latest_cycle']['actual_minus_expected_kg'];

        if ($previousGap === $latestGap) {
            return null;
        }

        $trend = $latestGap >= $previousGap ? 'improving' : 'slipping';

        return 'actual harvest versus expected '.$trend.' from '.$this->formatSignedKg($previousGap).' to '.$this->formatSignedKg($latestGap);
    }

    private function fallbackDurationPhrase(array $context): ?string
    {
        $previousDuration = $context['previous_cycle']['duration_days'];
        $latestDuration = $context['latest_cycle']['duration_days'];

        if ($previousDuration === null || $latestDuration === null || (int) $previousDuration === (int) $latestDuration) {
            return null;
        }

        return 'cycle duration changing from '.$previousDuration.' days to '.$latestDuration.' days';
    }

    private function fallbackWaterQualityPhrase(array $context): ?string
    {
        $latestWon = (float) $context['comparison']['latest_minus_previous_kg'] >= 0;
        $telemetry = $latestWon
            ? $context['latest_cycle']['telemetry_summary']
            : $context['previous_cycle']['telemetry_summary'];
        $cycleLabel = $latestWon ? 'latest' : 'previous';

        if ((int) $telemetry['record_count'] === 0) {
            return null;
        }

        $metrics = [];

        if (($telemetry['ph']['avg'] ?? null) !== null) {
            $metrics[] = 'pH '.$this->formatMetricAverage($telemetry['ph']);
        }

        if (($telemetry['water_temperature_c']['avg'] ?? null) !== null) {
            $metrics[] = 'temperature '.$this->formatMetricAverage($telemetry['water_temperature_c'], ' C');
        }

        if (($telemetry['ammonia_ppm']['avg'] ?? null) !== null) {
            $metrics[] = 'ammonia '.$this->formatMetricAverage($telemetry['ammonia_ppm'], ' ppm');
        }

        if ($metrics === []) {
            return null;
        }

        return $cycleLabel.' water readings averaging '.$this->joinPhrases($metrics);
    }

    private function formatMetricAverage(array $metric, string $suffix = ''): string
    {
        if (($metric['avg'] ?? null) === null) {
            return 'not available';
        }

        return number_format((float) $metric['avg'], 2).$suffix;
    }

    private function formatKg(float $value): string
    {
        return number_format($value, 2).' kg';
    }

    private function formatSignedKg(float $value): string
    {
        return ($value >= 0 ? '+' : '').$this->formatKg($value);
    }

    private function sentenceCount(string $text): int
    {
        return count(preg_split('/(?<=[.!?])\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }

    private function answersHarvestComparison(string $analysis): bool
    {
        $lowerAnalysis = mb_strtolower($analysis);
        $mentionsCycle = str_contains($lowerAnalysis, 'latest')
            || str_contains($lowerAnalysis, 'previous')
            || str_contains($lowerAnalysis, 'cycle');
        $mentionsOutcome = collect([
            'better',
            'higher',
            'more',
            'harvest',
            'improved',
            'increased',
            'rose',
            'outperformed',
            'advantage',
            'lower',
            'less',
            'dropped',
            'fell',
            'declined',
        ])->contains(fn (string $needle) => str_contains($lowerAnalysis, $needle));

        return $mentionsCycle && $mentionsOutcome;
    }

    private function hasPondSpecificEvidence(string $analysis, array $context): bool
    {
        $lowerAnalysis = mb_strtolower($analysis);
        $hasSpecificNumber = (bool) preg_match('/\d+(?:\.\d+)?\s*(?:kg|days?|c|ppm|%)/i', $analysis);
        $mentionsNonHarvestFactor = collect([
            'hatching',
            'expected harvest',
            'expected',
            'duration',
            'days',
            'ph',
            'temperature',
            'ammonia',
            'species',
        ])->contains(fn (string $needle) => str_contains($lowerAnalysis, $needle));

        if ($hasSpecificNumber && $mentionsNonHarvestFactor) {
            return true;
        }

        $lowerAnalysis = mb_strtolower($analysis);
        $speciesNames = collect($context['comparison']['harvest_by_species_kg'] ?? [])
            ->pluck('species')
            ->merge($context['pond']['registered_species'] ?? [])
            ->filter()
            ->unique();

        if ($speciesNames->contains(fn (mixed $species) => is_string($species) && str_contains($lowerAnalysis, mb_strtolower($species)))) {
            return true;
        }

        return false;
    }

    private function joinPhrases(array $phrases): string
    {
        $phrases = array_values(array_filter($phrases, fn (string $phrase) => trim($phrase) !== ''));

        return match (count($phrases)) {
            0 => '',
            1 => $phrases[0],
            2 => $phrases[0].' and '.$phrases[1],
            default => implode(', ', array_slice($phrases, 0, -1)).', and '.$phrases[array_key_last($phrases)],
        };
    }
}
