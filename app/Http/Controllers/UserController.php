<?php

namespace App\Http\Controllers;

use App\Models\Payload;
use App\Models\Pond;
use App\Models\PondCycle;
use App\Models\SmsAlertCooldown;
use App\Services\SemaphoreService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Show user dashboard
     */
    public function dashboard(Request $request)
    {
        $userId = Auth::id();

        // Get all ponds for this user
        $ponds = Pond::where('user_id', $userId)->latest()->get();
        $selectedPond = null;
        $payLoads = collect();

        if ($request->filled('pond_id')) {
            $selectedPond = $ponds->firstWhere('id', (int) $request->input('pond_id'));

            if ($selectedPond) {
                $payLoads = Payload::where('pond_id', $selectedPond->id)
                    ->where('user_id', $userId)
                    ->orderBy('created_at', 'asc')
                    ->get();
            }
        }

        $labels = [];
        $phData = [];
        $tempData = [];
        $ammoniaData = [];

        foreach ($payLoads as $data) {
            $decoded = $data->payload; // already array

            if (!$decoded || !is_array($decoded)) {
                continue;
            }

            $ph = $this->toFloat($decoded['ph'] ?? null);
            $temp = $this->toFloat($decoded['water_temp'] ?? $decoded['temperature'] ?? null);
            $ammonia = $this->toFloat($decoded['mq_ratio'] ?? $decoded['ammonia'] ?? null);

            $labels[] = $data->created_at 
                ? $data->created_at->format('H:i:s') 
                : '';
            $phData[] = $ph;
            $tempData[] = $temp;
            $ammoniaData[] = $ammonia;
        }

        // Latest payload for status
        $latest = $payLoads->last();
        $status = 'No Data';

        if ($latest) {
            $latestDecoded = $latest->payload; // already array

            $status = 'Normal';

            if (is_array($latestDecoded)) {
                $latestPh = $this->toFloat($latestDecoded['ph'] ?? null);
                $latestTemp = $this->toFloat($latestDecoded['water_temp'] ?? $latestDecoded['temperature'] ?? null);
                $latestAmmonia = $this->toFloat($latestDecoded['mq_ratio'] ?? $latestDecoded['ammonia'] ?? null);

                if (
                    $latestPh < 6 || $latestPh > 9 ||
                    $latestTemp > 35 ||
                    $latestAmmonia > 0.05
                ) {
                    $status = 'Warning';
                }

                if (
                    $latestPh < 5 || $latestPh > 10 ||
                    $latestTemp > 38 ||
                    $latestAmmonia > 0.1
                ) {
                    $status = 'Critical';
                }
            }
        }

        return view('dashboard', compact(
            'ponds',
            'selectedPond',
            'payLoads',
            'labels',
            'phData',
            'tempData',
            'ammoniaData',
            'status'
        ));
    }

    public function sendDashboardAlert(Request $request, SemaphoreService $semaphoreService): JsonResponse
    {
        $validated = $request->validate([
            'pond_id' => ['required', 'integer', 'exists:ponds,id'],
            'temp' => ['required', 'numeric'],
            'ph' => ['required', 'numeric'],
            'ammonia' => ['required', 'numeric'],
            'ai_text' => ['nullable', 'string', 'max:1500'],
            'issues' => ['nullable', 'array'],
            'issues.*' => ['string', 'max:255'],
            'actions' => ['nullable', 'array'],
            'actions.*' => ['string', 'max:255'],
        ]);

        $user = $request->user();
        $pond = Pond::where('id', $validated['pond_id'])
            ->where('user_id', $user->id)
            ->first();

        if (!$pond) {
            return response()->json([
                'sent' => false,
                'reason' => 'unauthorized_pond',
            ], 403);
        }

        $temp = (float) $validated['temp'];
        $ph = (float) $validated['ph'];
        $ammonia = (float) $validated['ammonia'];

        $triggeredConditions = $this->getTriggeredAlertConditions($temp, $ph, $ammonia);

        if (empty($triggeredConditions)) {
            return response()->json([
                'sent' => false,
                'reason' => 'no_dangerous_condition',
            ]);
        }

        if (!$user->phone || !$user->phone_verified) {
            return response()->json([
                'sent' => false,
                'reason' => 'phone_not_verified',
            ]);
        }

        $cooldownMinutes = 15;
        $cutoff = now()->subMinutes($cooldownMinutes);

        $recentConditions = SmsAlertCooldown::where('user_id', $user->id)
            ->where('pond_id', $pond->id)
            ->whereIn('condition_key', $triggeredConditions)
            ->where('last_sent_at', '>=', $cutoff)
            ->pluck('condition_key')
            ->all();

        $conditionsAllowedToSend = array_values(array_diff($triggeredConditions, $recentConditions));

        if (empty($conditionsAllowedToSend)) {
            return response()->json([
                'sent' => false,
                'reason' => 'cooldown_active',
                'cooldown_minutes' => $cooldownMinutes,
            ]);
        }

        $aiText = trim((string) ($validated['ai_text'] ?? ''));
        if ($aiText === '') {
            $issues = collect($validated['issues'] ?? [])->filter()->values()->all();
            $actions = collect($validated['actions'] ?? [])->filter()->values()->all();
            $aiText = $this->buildFallbackAiText($pond->id, $issues, $actions);
        }

        $message = $this->buildDashboardAlertMessage($pond->id, $aiText, $temp, $ph, $ammonia);

        try {
            $response = $semaphoreService->sendSms($user->phone, $message);
        } catch (\Throwable) {
            return response()->json([
                'sent' => false,
                'reason' => 'sms_exception',
            ]);
        }

        if (!$response->successful()) {
            return response()->json([
                'sent' => false,
                'reason' => 'sms_failed',
            ]);
        }

        foreach ($conditionsAllowedToSend as $conditionKey) {
            SmsAlertCooldown::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'pond_id' => $pond->id,
                    'condition_key' => $conditionKey,
                ],
                [
                    'last_sent_at' => now(),
                ]
            );
        }

        return response()->json([
            'sent' => true,
            'conditions' => $conditionsAllowedToSend,
        ]);
    }

    private function toFloat(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function getTriggeredAlertConditions(float $temp, float $ph, float $ammonia): array
    {
        $conditions = [];

        if ($temp < 24) {
            $conditions[] = 'temp_low';
        } elseif ($temp > 32) {
            $conditions[] = 'temp_high';
        }

        if ($ph < 6.5) {
            $conditions[] = 'ph_low';
        } elseif ($ph > 8.5) {
            $conditions[] = 'ph_high';
        }

        if ($ammonia > 0.05) {
            $conditions[] = 'ammonia_high';
        }

        return $conditions;
    }

    private function buildFallbackAiText(int $pondId, array $issues, array $actions): string
    {
        if (empty($issues)) {
            return "AI Assessment for Pond #{$pondId}. Water parameters need attention.";
        }

        $issuesText = implode('. ', $issues);
        $actionsText = empty($actions) ? '' : ' Recommended actions: '.implode(' ', $actions);

        return "AI Assessment for Pond #{$pondId}. {$issuesText}.{$actionsText}";
    }

    private function buildDashboardAlertMessage(
        int $pondId,
        string $aiText,
        float $temp,
        float $ph,
        float $ammonia
    ): string {
        $tempText = number_format($temp, 2, '.', '');
        $phText = number_format($ph, 2, '.', '');
        $ammoniaText = number_format($ammonia, 3, '.', '');

        $message = "Alert: Water condition issue detected in Pond #{$pondId}. ";
        $message .= "AI Analysis & Recommendation: {$aiText} ";
        $message .= "Current readings: Temp {$tempText}C, pH {$phText}, Ammonia {$ammoniaText} mg/L.";

        return mb_substr($message, 0, 900);
    }


    /**
     * Show fish pond information page
     */
    public function pondInfo()
    {
        $ponds = Pond::where('user_id', auth()->id())
            ->with(['cycles' => function ($query) {
                $query->latest('cycle_number');
            }])
            ->latest()
            ->get();

        return view('pond-info', compact('ponds'));
    }

    public function createPond()
    {
        return view('pond-create');
    }

    public function storePond(Request $request)
    {
        $request->validate([
            'hectares' => 'required|numeric|min:0.1',
            'fish_type' => 'required|array|min:1',
            'fish_type.*' => 'string',
        ]);

        $pond = Pond::create([
            'user_id' => auth()->id(),
            'hectares' => $request->hectares,
            'fish_type' => $request->fish_type,
        ]);

        return redirect()
            ->route('pond.cycles.new', $pond)
            ->with('success', 'Pond created. Start your first cycle.');
    }

    public function startNewCycleForm(Pond $pond)
    {
        if ($pond->user_id !== auth()->id()) {
            abort(403);
        }

        $activeCycle = $pond->cycles()->where('status', 'active')->first();

        if ($activeCycle) {
            return redirect()
                ->route('pond.cycle.species-data.form', [$pond, $activeCycle])
                ->with('warning', 'This pond already has an active cycle. Complete it before starting a new one.');
        }

        $lastCompletedCycle = $pond->cycles()
            ->where('status', 'completed')
            ->latest('cycle_number')
            ->first();

        $defaultSpecies = collect($lastCompletedCycle->species_data ?? [])->pluck('species')->all();
        if (empty($defaultSpecies)) {
            $defaultSpecies = $pond->fish_type ?? [];
        }

        return view('pond-cycle-start', [
            'pond' => $pond,
            'lastCompletedCycle' => $lastCompletedCycle,
            'defaultSpecies' => $defaultSpecies,
        ]);
    }

    public function startNewCycle(Request $request, Pond $pond)
    {
        if ($pond->user_id !== auth()->id()) {
            abort(403);
        }

        if ($pond->cycles()->where('status', 'active')->exists()) {
            return redirect()
                ->route('pond-info')
                ->with('warning', 'This pond already has an active cycle.');
        }

        $validated = $request->validate([
            'harvest_date' => 'required|date|after_or_equal:today',
            'fish_type' => 'required|array|min:1',
            'fish_type.*' => 'string',
            'reuse_previous_data' => 'nullable|boolean',
        ]);

        $reusePreviousData = (bool) ($validated['reuse_previous_data'] ?? false);
        $selectedSpecies = collect($validated['fish_type'])->values()->all();

        $lastCompletedCycle = $pond->cycles()
            ->where('status', 'completed')
            ->latest('cycle_number')
            ->first();

        $previousSpeciesMap = collect($lastCompletedCycle->species_data ?? [])->keyBy('species');

        $speciesData = collect($selectedSpecies)
            ->map(function ($species) use ($reusePreviousData, $previousSpeciesMap) {
                $previous = $previousSpeciesMap->get($species, []);
                $hatchingKg = $reusePreviousData && isset($previous['hatching_kg'])
                    ? (float) $previous['hatching_kg']
                    : null;

                return [
                    'species' => $species,
                    'hatching_kg' => $hatchingKg,
                    'expected_harvest_kg' => $hatchingKg,
                    'unit' => 'kg',
                ];
            })
            ->values()
            ->all();

        $cycleNumber = ((int) $pond->cycles()->max('cycle_number')) + 1;

        $cycle = PondCycle::create([
            'pond_id' => $pond->id,
            'user_id' => auth()->id(),
            'cycle_number' => $cycleNumber,
            'status' => 'active',
            'hatching_started_at' => now()->toDateString(),
            'harvest_date' => $validated['harvest_date'],
            'species_data' => $speciesData,
        ]);

        // Keep pond profile fish types in sync with latest selected cycle species.
        $pond->fish_type = $selectedSpecies;
        $pond->save();

        return redirect()
            ->route('pond.cycle.species-data.form', [$pond, $cycle])
            ->with('success', 'New cycle started. Confirm species quantities in kg.');
    }

    public function cycleHistory(Pond $pond)
    {
        if ($pond->user_id !== auth()->id()) {
            abort(403);
        }

        $cycles = $pond->cycles()->latest('cycle_number')->get();

        return view('pond-cycle-history', [
            'pond' => $pond,
            'cycles' => $cycles,
        ]);
    }

    public function speciesDataForm(Pond $pond, PondCycle $cycle)
    {
        $this->authorizeCycle($pond, $cycle);

        if ($cycle->status === 'completed') {
            return redirect()
                ->route('pond.cycles.history', $pond)
                ->with('warning', 'Completed cycles are read-only.');
        }

        if ($this->isSpeciesDataLocked($cycle)) {
            return redirect()
                ->route('pond-info')
                ->with('warning', 'Species data is locked after hatching starts and can be changed again in the next cycle after harvest completion.');
        }

        $speciesDataByName = collect($cycle->species_data ?? [])->keyBy('species');

        return view('pond-species-data', [
            'pond' => $pond,
            'cycle' => $cycle,
            'speciesDataByName' => $speciesDataByName,
        ]);
    }

    public function storeSpeciesData(Request $request, Pond $pond, PondCycle $cycle)
    {
        $this->authorizeCycle($pond, $cycle);

        if ($cycle->status === 'completed') {
            return redirect()
                ->route('pond.cycles.history', $pond)
                ->with('warning', 'Completed cycles are read-only.');
        }

        if ($this->isSpeciesDataLocked($cycle)) {
            return redirect()
                ->route('pond-info')
                ->with('warning', 'Species data is locked after hatching starts and can be changed again in the next cycle after harvest completion.');
        }

        $species = collect($cycle->species_data ?? [])->pluck('species')->values()->all();

        $validator = Validator::make($request->all(), [
            'species_data' => ['required', 'array', 'min:1'],
            'species_data.*.species' => ['required', 'string'],
            'species_data.*.hatching_kg' => ['required', 'numeric', 'min:0'],
        ]);

        $validator->after(function ($validator) use ($request, $species) {
            $submitted = collect($request->input('species_data', []))
                ->pluck('species')
                ->sort()
                ->values()
                ->all();

            $expected = collect($species)->sort()->values()->all();

            if ($submitted !== $expected) {
                $validator->errors()->add('species_data', 'Species data must be provided for every selected aquatic species.');
            }
        });

        $validated = $validator->validate();

        $cycle->species_data = collect($validated['species_data'])
            ->map(function ($item) {
                $hatchingKg = (float) $item['hatching_kg'];

                return [
                    'species' => $item['species'],
                    'hatching_kg' => $hatchingKg,
                    'expected_harvest_kg' => $hatchingKg,
                    'unit' => 'kg',
                ];
            })
            ->values()
            ->all();
        $cycle->save();

        return redirect()
            ->route('pond-info')
            ->with('success', 'Species quantity data saved successfully.');
    }

    public function harvestDataForm(Pond $pond, PondCycle $cycle)
    {
        $this->authorizeCycle($pond, $cycle);

        if ($cycle->status === 'completed') {
            return redirect()
                ->route('pond.cycles.history', $pond)
                ->with('warning', 'Completed cycles are read-only.');
        }

        if ($this->cycleNeedsSpeciesData($cycle)) {
            return redirect()
                ->route('pond.cycle.species-data.form', [$pond, $cycle])
                ->with('warning', 'Complete species quantity data first before entering harvest data.');
        }

        if (!$this->isHarvestWindowOpen($cycle)) {
            return redirect()
                ->route('pond-info')
                ->with('warning', 'Harvest data is not available until the harvest date is reached.');
        }

        $harvestDataByName = collect($cycle->harvest_data ?? [])->keyBy('species');

        return view('pond-harvest-data', [
            'pond' => $pond,
            'cycle' => $cycle,
            'harvestDataByName' => $harvestDataByName,
        ]);
    }

    public function storeHarvestData(Request $request, Pond $pond, PondCycle $cycle)
    {
        $this->authorizeCycle($pond, $cycle);

        if ($cycle->status === 'completed') {
            return redirect()
                ->route('pond.cycles.history', $pond)
                ->with('warning', 'Completed cycles are read-only.');
        }

        if ($this->cycleNeedsSpeciesData($cycle)) {
            return redirect()
                ->route('pond.cycle.species-data.form', [$pond, $cycle])
                ->with('warning', 'Complete species quantity data first before entering harvest data.');
        }

        if (!$this->isHarvestWindowOpen($cycle)) {
            return redirect()
                ->route('pond-info')
                ->with('warning', 'Harvest quantity input is locked until the harvest date is reached.');
        }

        $species = collect($cycle->species_data ?? [])->pluck('species')->values()->all();

        $validator = Validator::make($request->all(), [
            'harvest_data' => ['required', 'array', 'min:1'],
            'harvest_data.*.species' => ['required', 'string'],
            'harvest_data.*.harvest_kg' => ['required', 'numeric', 'min:0'],
        ]);

        $validator->after(function ($validator) use ($request, $species) {
            $submitted = collect($request->input('harvest_data', []))
                ->pluck('species')
                ->sort()
                ->values()
                ->all();

            $expected = collect($species)->sort()->values()->all();

            if ($submitted !== $expected) {
                $validator->errors()->add('harvest_data', 'Harvest data must be provided for every selected aquatic species.');
            }
        });

        $validated = $validator->validate();

        $cycle->harvest_data = collect($validated['harvest_data'])
            ->map(fn ($item) => [
                'species' => $item['species'],
                'harvest_kg' => (float) $item['harvest_kg'],
                'unit' => 'kg',
                'recorded_at' => now()->toDateString(),
            ])
            ->values()
            ->all();
        $cycle->status = 'completed';
        $cycle->completed_at = now()->toDateString();
        $cycle->save();

        return redirect()
            ->route('pond-info')
            ->with('success', 'Harvest data saved and cycle archived to history.');
    }

    public function telemetrylog(Request $request)
    {
        $user = auth()->user();

        // Get ponds owned by this user
        $ponds = Pond::where('user_id', $user->id)->get();

        // If a pond is selected, load its payloads
        $payloads = collect();

        if ($request->filled('pond_id')) {
            $payloads = Payload::where('pond_id', $request->pond_id)
                ->where('user_id', $user->id)
                ->latest()
                ->paginate(10)
                ->withQueryString();
        }

        return view('telemetrylog', compact('ponds', 'payloads'));
    }

    private function cycleNeedsSpeciesData(PondCycle $cycle): bool
    {
        if (empty($cycle->species_data) || !is_array($cycle->species_data)) {
            return true;
        }

        $expectedSpecies = collect($cycle->species_data)->pluck('species')->sort()->values()->all();

        $completedSpecies = collect($cycle->species_data)
            ->filter(function ($item) {
                return isset($item['species'], $item['hatching_kg'], $item['expected_harvest_kg']);
            })
            ->pluck('species')
            ->sort()
            ->values()
            ->all();

        return $expectedSpecies !== $completedSpecies;
    }

    private function isSpeciesDataLocked(PondCycle $cycle): bool
    {
        if ($cycle->status !== 'active') {
            return false;
        }

        if (!$cycle->hatching_started_at) {
            return false;
        }

        // Allow one-time completion if the species data is still incomplete.
        return !$this->cycleNeedsSpeciesData($cycle);
    }

    private function isHarvestWindowOpen(PondCycle $cycle): bool
    {
        if (!$cycle->harvest_date) {
            return false;
        }

        return now()->startOfDay()->greaterThanOrEqualTo($cycle->harvest_date->copy()->startOfDay());
    }

    private function authorizeCycle(Pond $pond, PondCycle $cycle): void
    {
        if ($pond->user_id !== auth()->id() || $cycle->user_id !== auth()->id() || $cycle->pond_id !== $pond->id) {
            abort(403);
        }
    }

}
