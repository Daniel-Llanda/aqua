<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                Harvest Data
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Pond #{{ $pond->id }} Cycle #{{ $cycle->cycle_number }}. Enter actual harvest quantities in kilograms (kg).
            </p>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto">
        @if(session('warning'))
            <div class="mb-4 px-6 py-4 border border-yellow-200 bg-yellow-50 text-yellow-700 text-sm rounded-lg">
                {{ session('warning') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <form method="POST" action="{{ route('pond.cycle.harvest-data.store', [$pond, $cycle]) }}" class="p-6 space-y-6">
                @csrf

                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    Harvest date reached: {{ $cycle->harvest_date?->format('M d, Y') }}. Input actual harvest values per species.
                </div>

                @foreach(collect($cycle->species_data ?? [])->pluck('species')->all() as $index => $species)
                    @php
                        $existingHarvest = $harvestDataByName->get($species, []);
                        $expectedSpecies = collect($cycle->species_data ?? [])->firstWhere('species', $species);
                        $expectedHarvest = $expectedSpecies['expected_harvest_kg'] ?? null;
                    @endphp
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-4">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                            {{ $species }}
                        </h3>

                        <input type="hidden" name="harvest_data[{{ $index }}][species]" value="{{ $species }}">

                        <div class="text-xs text-gray-500 dark:text-gray-300">
                            Expected Harvest: {{ $expectedHarvest !== null ? number_format((float) $expectedHarvest, 2) . ' kg' : 'N/A' }}
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Quantity of Harvest (kg)
                            </label>
                            <input type="number" step="0.01" min="0" required
                                   name="harvest_data[{{ $index }}][harvest_kg]"
                                   value="{{ old('harvest_data.'.$index.'.harvest_kg', $existingHarvest['harvest_kg'] ?? '') }}"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                @endforeach

                <div class="flex justify-between pt-2">
                    <a href="{{ route('pond-info') }}"
                       class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium">
                        Back to Registered Ponds
                    </a>

                    <button type="submit"
                            class="inline-flex items-center px-6 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium shadow-sm focus:ring-2 focus:ring-emerald-500">
                        Save Harvest Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
