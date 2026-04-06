<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                    My Registered Fish Ponds
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    View and monitor your existing pond records.
                </p>
            </div>

            <a href="{{ route('pond.create') }}"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium">
                Add Pond
            </a>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto mt-6 mb-8 px-4 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-5 px-4 py-3 border border-green-200 bg-green-50 text-green-700 text-sm rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if($ponds->isEmpty())
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 p-4 rounded-lg">
                No pond records found.
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($ponds as $pond)
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-5">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <div class="mb-1">
                                    @foreach(json_decode($pond->fish_type) as $fish)
                                        <span class="inline-block bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-xs mr-1 mb-1">
                                            {{ $fish }}
                                        </span>
                                    @endforeach
                                </div>
                                <p class="text-xs text-gray-500">
                                    Created {{ $pond->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <span class="text-xs px-3 py-1 rounded-full bg-green-100 text-green-700">
                                Active
                            </span>
                        </div>

                        <div class="space-y-1 text-sm text-gray-600 dark:text-gray-300">
                            <p><strong>Pond ID:</strong> {{ $pond->id }}</p>
                            <p><strong>Pond Area:</strong> {{ $pond->hectares }} ha</p>
                            <p><strong>Stocking Date:</strong> {{ isset($pond->hatching_date) ? \Carbon\Carbon::parse($pond->hatching_date)->format('M d, Y') : 'N/A'}}</p>
                            <p><strong>Harvest Date:</strong> {{ isset($pond->harvest_date) ? \Carbon\Carbon::parse($pond->harvest_date)->format('M d, Y') : 'N/A'}}</p>
                            <p><strong>Quantity of Hatching:</strong> {{ $pond->quantity_of_hatching ?? 0 }}</p>
                            <p><strong>Quantity of Harvest:</strong> {{ $pond->quantity_of_harvest ?? 0 }}</p>
                        </div>

                        @php
                            $daysLeft = now()->startOfDay()->diffInDays(
                                \Carbon\Carbon::parse($pond->harvest_date)->startOfDay(), false
                            );
                        @endphp

                        <div class="mt-4 pt-3 border-t text-sm">
                            @if($daysLeft > 0)
                                <span class="text-green-600 font-medium">
                                    {{ $daysLeft }} days remaining until harvest
                                </span>
                            @else
                                <span class="text-red-600 font-medium">
                                    Harvest period reached
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
