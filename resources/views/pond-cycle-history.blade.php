<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                Pond Cycle History
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Pond #{{ $pond->id }}. Historical records are immutable and preserved per cycle.
            </p>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto space-y-4">
        @if(session('success'))
            <div class="px-6 py-4 border border-green-200 bg-green-50 text-green-700 text-sm rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if(session('warning'))
            <div class="px-6 py-4 border border-yellow-200 bg-yellow-50 text-yellow-700 text-sm rounded-lg">
                {{ session('warning') }}
            </div>
        @endif

        @if($cycles->isEmpty())
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 p-4 rounded-lg">
                No cycles found for this pond yet.
            </div>
        @else
            @foreach($cycles as $cycle)
                @php
                    $speciesData = collect($cycle->species_data ?? []);
                    $harvestData = collect($cycle->harvest_data ?? []);
                    $expectedTotal = (float) $speciesData->sum('expected_harvest_kg');
                    $actualTotal = (float) $harvestData->sum('harvest_kg');
                    $variance = $actualTotal - $expectedTotal;
                @endphp
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                Cycle #{{ $cycle->cycle_number }}
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">
                                Hatching: {{ $cycle->hatching_started_at?->format('M d, Y') ?? 'N/A' }} |
                                Harvest Date: {{ $cycle->harvest_date?->format('M d, Y') ?? 'N/A' }} |
                                Completed: {{ $cycle->completed_at?->format('M d, Y') ?? 'No' }}
                            </p>
                        </div>
                        <span class="inline-flex items-center justify-center min-w-[96px] h-7 px-3 text-xs font-semibold rounded-full whitespace-nowrap {{ $cycle->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ ucfirst($cycle->status) }}
                        </span>
                    </div>

                    <div class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                        <p><strong>Expected vs Actual:</strong> {{ number_format($expectedTotal, 2) }} kg vs {{ number_format($actualTotal, 2) }} kg ({{ $variance >= 0 ? '+' : '' }}{{ number_format($variance, 2) }} kg)</p>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @foreach($speciesData as $item)
                            <span class="inline-flex items-center bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-xs">
                                {{ $item['species'] ?? 'Unknown' }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif

        <div class="pt-2">
            <a href="{{ route('pond-info') }}"
               class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium">
                Back to Registered Ponds
            </a>
        </div>
    </div>
</x-app-layout>
