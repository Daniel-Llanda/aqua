<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                    Registered Ponds
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Reuse each pond across multiple hatching cycles while preserving cycle history.
                </p>
            </div>

            <a href="{{ route('pond.create') }}"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium shadow-sm focus:ring-2 focus:ring-blue-500">
                Add Pond
            </a>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto">
        @if(session('success'))
            <div class="mb-4 px-6 py-4 border border-green-200 bg-green-50 text-green-700 text-sm rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if(session('warning'))
            <div class="mb-4 px-6 py-4 border border-yellow-200 bg-yellow-50 text-yellow-700 text-sm rounded-lg">
                {{ session('warning') }}
            </div>
        @endif

        @if($ponds->isEmpty())
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 p-4 rounded-lg">
                No pond records found.
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($ponds as $pond)
                    @php
                        $activeCycle = $pond->cycles->firstWhere('status', 'active');
                        $completedCycles = $pond->cycles->where('status', 'completed');
                        $historyCount = $completedCycles->count();

                        $species = collect($activeCycle?->species_data ?? [])->pluck('species')->filter()->values()->all();
                        $primarySpecies = $species[0] ?? null;
                        $remainingSpecies = array_slice($species, 1);

                        $speciesData = collect($activeCycle?->species_data ?? []);
                        $harvestData = collect($activeCycle?->harvest_data ?? []);
                        $speciesWithData = $speciesData->filter(fn ($item) => isset($item['hatching_kg'], $item['expected_harvest_kg']))->pluck('species')->sort()->values()->all();
                        $speciesWithHarvest = $harvestData->pluck('species')->sort()->values()->all();
                        $speciesSelected = collect($species)->sort()->values()->all();

                        $hasCompleteSpeciesData = !empty($speciesSelected) && $speciesSelected === $speciesWithData;
                        $hasCompleteHarvestData = !empty($speciesSelected) && $speciesSelected === $speciesWithHarvest;
                        $isSpeciesLocked = $activeCycle
                            && $activeCycle->status === 'active'
                            && $activeCycle->hatching_started_at
                            && $hasCompleteSpeciesData;

                        $harvestDateReached = $activeCycle && $activeCycle->harvest_date && now()->startOfDay()->greaterThanOrEqualTo($activeCycle->harvest_date->copy()->startOfDay());

                        $expectedTotal = (float) $speciesData->sum('expected_harvest_kg');
                        $actualTotal = (float) $harvestData->sum('harvest_kg');
                        $variance = $actualTotal - $expectedTotal;

                        $statusLabel = $activeCycle ? 'Cycle Active' : 'Ready for New Cycle';
                        $statusClasses = $activeCycle
                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/70 dark:bg-emerald-950/40 dark:text-emerald-300'
                            : 'border-slate-200 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200';

                        $speciesStatusLabel = $activeCycle
                            ? ($hasCompleteSpeciesData ? 'Complete' : 'Required')
                            : 'No active cycle';
                        $speciesStatusClasses = !$activeCycle
                            ? 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-200'
                            : ($hasCompleteSpeciesData
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/70 dark:bg-emerald-950/40 dark:text-emerald-300'
                                : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/70 dark:bg-amber-950/40 dark:text-amber-300');

                        $harvestStatusLabel = !$activeCycle
                            ? 'No active cycle'
                            : ($hasCompleteHarvestData ? 'Complete' : ($harvestDateReached ? 'Required' : 'Locked'));
                        $harvestStatusClasses = !$activeCycle
                            ? 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-200'
                            : ($hasCompleteHarvestData
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/70 dark:bg-emerald-950/40 dark:text-emerald-300'
                                : ($harvestDateReached
                                    ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/70 dark:bg-amber-950/40 dark:text-amber-300'
                                    : 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-200'));

                        if ($activeCycle && $hasCompleteSpeciesData && $hasCompleteHarvestData) {
                            $yieldValue = ($variance >= 0 ? '+' : '').number_format($variance, 2).' kg';
                            $yieldDetail = number_format($expectedTotal, 2).' expected / '.number_format($actualTotal, 2).' actual';
                            $yieldValueClasses = $variance >= 0
                                ? 'text-emerald-700 dark:text-emerald-300'
                                : 'text-amber-700 dark:text-amber-300';
                        } else {
                            $yieldValue = 'Pending';
                            $yieldDetail = $activeCycle ? 'Awaiting complete cycle data' : 'No active cycle';
                            $yieldValueClasses = 'text-slate-500 dark:text-slate-400';
                        }
                    @endphp

                    <div class="h-full rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition-shadow duration-200 hover:shadow-md dark:border-gray-700 dark:bg-gray-900">
                        <div class="flex h-full flex-col">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400">
                                        Pond
                                    </p>
                                    <h3 class="mt-2 text-xl font-semibold text-gray-900 dark:text-gray-100">
                                        Pond #{{ $pond->id }}
                                    </h3>
                                    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-500 dark:text-gray-400">
                                        <span>{{ number_format((float) $pond->hectares, 2) }} ha</span>
                                        <span>{{ $historyCount }} completed cycle{{ $historyCount === 1 ? '' : 's' }}</span>
                                        <span>Created {{ $pond->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>

                                <span class="inline-flex min-w-[148px] items-center justify-center rounded-full border px-3 py-1.5 text-xs font-semibold whitespace-nowrap {{ $statusClasses }}">
                                    {{ $statusLabel }}
                                </span>
                            </div>

                            <div class="mt-5 rounded-2xl border border-gray-200 bg-gray-50/80 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">
                                            Species
                                        </p>
                                        <div class="mt-3 flex min-h-[2rem] flex-wrap gap-2">
                                            @if($primarySpecies)
                                                <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">
                                                    {{ $primarySpecies }}
                                                </span>
                                            @elseif($activeCycle)
                                                <span class="inline-flex items-center rounded-full bg-gray-200 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                    Species pending
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-gray-200 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                    No active cycle
                                                </span>
                                            @endif

                                            @if(count($remainingSpecies) > 0)
                                                <details class="relative">
                                                    <summary class="list-none inline-flex cursor-pointer select-none items-center rounded-full bg-gray-200 px-3 py-1 text-xs font-medium text-gray-700 transition hover:bg-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                                                        +{{ count($remainingSpecies) }} more
                                                    </summary>

                                                    <div class="absolute left-0 top-9 z-20 w-72 max-w-[85vw] rounded-xl border border-gray-200 bg-white p-3 shadow-lg dark:border-gray-700 dark:bg-gray-900">
                                                        <p class="mb-2 text-xs font-semibold text-gray-500 dark:text-gray-300">
                                                            Cycle #{{ $activeCycle?->cycle_number }} Species
                                                        </p>
                                                        <div class="flex flex-wrap gap-1.5">
                                                            @foreach($species as $fish)
                                                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">
                                                                    {{ $fish }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </details>
                                            @endif
                                        </div>
                                    </div>

                                    <span class="inline-flex shrink-0 items-center rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-semibold text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                        {{ $activeCycle ? 'Cycle #'.$activeCycle->cycle_number : 'Idle' }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-5 grid grid-cols-2 gap-3">
                                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900/60">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">
                                        Hatching
                                    </p>
                                    <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $activeCycle?->hatching_started_at?->format('M d, Y') ?? 'Not started' }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Cycle start date
                                    </p>
                                </div>

                                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900/60">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">
                                        Harvest
                                    </p>
                                    <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $activeCycle?->harvest_date?->format('M d, Y') ?? 'Not set' }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Target harvest date
                                    </p>
                                </div>

                                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900/60">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">
                                        Active Cycle
                                    </p>
                                    <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $activeCycle ? '#'.$activeCycle->cycle_number : 'N/A' }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Current production cycle
                                    </p>
                                </div>

                                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900/60">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">
                                        Yield Delta
                                    </p>
                                    <p class="mt-2 text-sm font-semibold {{ $yieldValueClasses }}">
                                        {{ $yieldValue }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $yieldDetail }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div class="rounded-xl border p-4 {{ $speciesStatusClasses }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em]">
                                                Species Data
                                            </p>
                                            <p class="mt-2 text-sm font-semibold">
                                                {{ $speciesStatusLabel }}
                                            </p>
                                        </div>
                                        <span class="inline-flex rounded-full border border-current/15 bg-white/60 px-2.5 py-1 text-[11px] font-semibold dark:bg-white/5">
                                            {{ $activeCycle ? 'Cycle Ready Check' : 'Unavailable' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="rounded-xl border p-4 {{ $harvestStatusClasses }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em]">
                                                Harvest Data
                                            </p>
                                            <p class="mt-2 text-sm font-semibold">
                                                {{ $harvestStatusLabel }}
                                            </p>
                                        </div>
                                        <span class="inline-flex rounded-full border border-current/15 bg-white/60 px-2.5 py-1 text-[11px] font-semibold dark:bg-white/5">
                                            {{ $activeCycle && $harvestDateReached ? 'Open Now' : 'Schedule Based' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-auto pt-5">
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    @if($activeCycle)
                                        @if($isSpeciesLocked)
                                            <span class="inline-flex min-h-[46px] items-center justify-center rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:border-gray-700 dark:bg-gray-800/70 dark:text-gray-400"
                                                  title="Species data is locked while hatching is active. You can update it in the next cycle after harvest completion.">
                                                Species Data Locked
                                            </span>
                                        @else
                                            <a href="{{ route('pond.cycle.species-data.form', [$pond, $activeCycle]) }}"
                                               class="inline-flex min-h-[46px] items-center justify-center rounded-xl border border-blue-300 px-4 py-3 text-center text-xs font-semibold text-blue-700 transition hover:bg-blue-50 focus:ring-2 focus:ring-blue-500 dark:border-blue-800 dark:text-blue-300 dark:hover:bg-blue-950/30">
                                                {{ $hasCompleteSpeciesData ? 'Update Species Data' : 'Input Species Data' }}
                                            </a>
                                        @endif

                                        @if($harvestDateReached)
                                            <a href="{{ route('pond.cycle.harvest-data.form', [$pond, $activeCycle]) }}"
                                               class="inline-flex min-h-[46px] items-center justify-center rounded-xl border border-emerald-300 px-4 py-3 text-center text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50 focus:ring-2 focus:ring-emerald-500 dark:border-emerald-800 dark:text-emerald-300 dark:hover:bg-emerald-950/30">
                                                {{ $hasCompleteHarvestData ? 'Update Harvest Data' : 'Input Harvest Data' }}
                                            </a>
                                        @else
                                            <span class="inline-flex min-h-[46px] items-center justify-center rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:border-gray-700 dark:bg-gray-800/70 dark:text-gray-400"
                                                  title="Harvest data is available on or after {{ $activeCycle?->harvest_date?->format('M d, Y') ?? 'the harvest date' }}">
                                                Harvest Data Locked
                                            </span>
                                        @endif
                                    @else
                                        <a href="{{ route('pond.cycles.new', $pond) }}"
                                           class="inline-flex min-h-[46px] items-center justify-center rounded-xl bg-emerald-600 px-4 py-3 text-center text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 sm:col-span-2">
                                            Start New Cycle
                                        </a>
                                    @endif
                                </div>

                                <a href="{{ route('pond.cycles.history', $pond) }}"
                                   class="mt-3 inline-flex min-h-[46px] w-full items-center justify-center rounded-xl border border-gray-300 px-4 py-3 text-center text-xs font-semibold text-gray-700 transition hover:bg-gray-50 focus:ring-2 focus:ring-gray-400 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800/80">
                                    View Cycle History
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
