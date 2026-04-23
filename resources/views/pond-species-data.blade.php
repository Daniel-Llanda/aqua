<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                Species Quantity Data
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Pond #{{ $pond->id }} Cycle #{{ $cycle->cycle_number }}. Enter initial quantities in kilograms (kg).
            </p>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto">
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
            <form method="POST" action="{{ route('pond.cycle.species-data.store', [$pond, $cycle]) }}" class="p-6 space-y-6">
                @csrf

                <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    Complete all fields before submitting. Harvest results are captured in the separate Harvest Data form.
                </div>

                @foreach(collect($cycle->species_data ?? [])->pluck('species')->all() as $index => $species)
                    @php
                        $existing = $speciesDataByName->get($species, []);
                        $hatchingValue = old('species_data.'.$index.'.hatching_kg', $existing['hatching_kg'] ?? '');
                    @endphp
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-4">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                            {{ $species }}
                        </h3>

                        <input type="hidden" name="species_data[{{ $index }}][species]" value="{{ $species }}">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Quantity of Hatching (kg)
                                </label>
                                <input type="number" step="0.01" min="0" required
                                       name="species_data[{{ $index }}][hatching_kg]"
                                       value="{{ $hatchingValue }}"
                                       data-hatching-input
                                       data-species-index="{{ $index }}"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Expected Harvest Quantity (kg)
                                </label>
                                <input type="number" step="0.01" min="0" required readonly
                                       name="species_data[{{ $index }}][expected_harvest_kg]"
                                       value="{{ $hatchingValue }}"
                                       data-expected-harvest-input
                                       data-species-index="{{ $index }}"
                                       class="w-full rounded-lg border-gray-300 bg-gray-100 text-gray-600 dark:border-gray-700 dark:bg-gray-800/70 dark:text-gray-300 focus:ring-0 cursor-not-allowed">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    This value automatically matches the hatching quantity.
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="flex justify-between pt-2">
                    <a href="{{ route('pond-info') }}"
                       class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium">
                        Back to Registered Ponds
                    </a>

                    <button type="submit"
                            class="inline-flex items-center px-6 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium shadow-sm focus:ring-2 focus:ring-blue-500">
                        Save Species Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const syncExpectedHarvestValue = (input) => {
                const index = input.dataset.speciesIndex;
                const expectedInput = document.querySelector(`[data-expected-harvest-input][data-species-index="${index}"]`);

                if (!expectedInput) {
                    return;
                }

                expectedInput.value = input.value;
            };

            document.querySelectorAll('[data-hatching-input]').forEach((input) => {
                syncExpectedHarvestValue(input);
                input.addEventListener('input', () => syncExpectedHarvestValue(input));
                input.addEventListener('change', () => syncExpectedHarvestValue(input));
            });
        });
    </script>
</x-app-layout>
