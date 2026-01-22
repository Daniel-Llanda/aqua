<x-app-layout>
    {{-- ================= HEADER ================= --}}
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                Fish Pond Management
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Record, monitor, and manage aquaculture pond information
            </p>
        </div>
    </x-slot>

    {{-- ================= FORM SECTION ================= --}}
    <div class="max-w-5xl mx-auto">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">

            {{-- Success Alert --}}
            @if(session('success'))
                <div class="px-6 py-4 border-b border-green-200 bg-green-50 text-green-700 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('pond.store') }}" class="px-6 py-6 space-y-6">
                @csrf

                {{-- Section Header --}}
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                        Pond Information
                    </h3>
                    <p class="text-sm text-gray-500">
                        Provide accurate details for monitoring and harvest planning.
                    </p>
                </div>

                {{-- Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    {{-- Pond Size --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Pond Area (Hectares)
                        </label>
                        <input type="number" step="0.01" name="hectares" required
                               placeholder="e.g. 1.50"
                               class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700
                                      dark:bg-gray-800 dark:text-gray-100
                                      focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <p class="mt-1 text-xs text-gray-500">
                            Total surface area of the pond
                        </p>
                    </div>

                    {{-- Dates --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Stocking / Hatching Date
                        </label>
                        <input type="date" name="hatching_date" required
                               class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700
                                      dark:bg-gray-800 dark:text-gray-100
                                      focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Expected Harvest Date
                        </label>
                        <input type="date" name="harvest_date" required
                               class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700
                                      dark:bg-gray-800 dark:text-gray-100
                                      focus:ring-2 focus:ring-blue-500">
                    </div>

                    {{-- Fish Types --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            Cultured Aquatic Species (Select all that apply)
                        </label>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                            {{-- Fish --}}
                            <div>
                                <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">
                                    Fish
                                </h4>
                                <div class="space-y-2 text-sm">
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="fish_type[]" value="Milkfish (Chanos chanos)">
                                        Milkfish / Bangus (Chanos chanos)
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="fish_type[]" value="Tilapia (General)">
                                        Tilapia (General)
                                    </label>

                                </div>
                            </div>

                            {{-- Shrimp / Prawns --}}
                            <div>
                                <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">
                                    Shrimp / Prawns
                                </h4>
                                <div class="space-y-2 text-sm">
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="fish_type[]" value="Penaeid Shrimp (General)">
                                        Penaeid Shrimp (General brackishwater group)
                                    </label>

                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="fish_type[]" value="Black Tiger Shrimp (Penaeus monodon)">
                                        Black Tiger Shrimp (Penaeus monodon)
                                    </label>

                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="fish_type[]" value="Pacific White Shrimp (Litopenaeus vannamei)">
                                        Pacific White Shrimp / Whiteleg Shrimp
                                        (Litopenaeus vannamei)
                                    </label>

                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="fish_type[]" value="Indian White Shrimp (Penaeus indicus)">
                                        Indian White Shrimp (Penaeus indicus)
                                    </label>

                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="fish_type[]" value="Banana Shrimp (Metapenaeus spp.)">
                                        Banana / Speckled Shrimp
                                        (Metapenaeus ensis / M. monoceros)
                                    </label>

                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="fish_type[]" value="Giant Freshwater Prawn (Macrobrachium rosenbergii)">
                                        Giant Freshwater Prawn
                                        (Macrobrachium rosenbergii)
                                    </label>
                                </div>
                            </div>

                            {{-- Crabs --}}
                            <div>
                                <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">
                                    Crabs
                                </h4>
                                <div class="space-y-2 text-sm">
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="fish_type[]" value="Mud Crab (Scylla serrata)">
                                        Mud Crab / Giant Mangrove Crab
                                        (Scylla serrata)
                                    </label>

                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="fish_type[]" value="Green Mud Crab (Scylla tranquebarica)">
                                        Green Mud Crab
                                        (Scylla tranquebarica)
                                    </label>

                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="fish_type[]" value="Orange Mud Crab (Scylla olivacea)">
                                        Orange Mud Crab
                                        (Scylla olivacea)
                                    </label>

                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="fish_type[]" value="Blue Swimming Crab (Portunus pelagicus)">
                                        Blue Swimming Crab
                                        (Portunus pelagicus)
                                    </label>

                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="fish_type[]" value="Atlantic Blue Crab (Callinectes sapidus)">
                                        Atlantic Blue Crab
                                        (Callinectes sapidus)
                                    </label>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>

                {{-- Submit --}}
                <div class="flex justify-end pt-4">
                    <button type="submit"
                            class="inline-flex items-center px-6 py-2.5 rounded-lg
                                   bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium
                                   shadow-sm focus:ring-2 focus:ring-blue-500">
                        Save Pond Record
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================= LIST SECTION ================= --}}
    <div class="max-w-6xl mx-auto mt-10 mb-8">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">
            My Registered Fish Ponds
        </h3>

        @if($ponds->isEmpty())
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 p-4 rounded-lg">
                No pond records found.
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($ponds as $pond)
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700
                                rounded-xl shadow-sm p-5">

                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <div class="mb-1">
                                    @foreach(json_decode($pond->fish_type) as $fish)
                                        <span class="inline-block bg-blue-100 text-blue-700
                                                     px-2 py-0.5 rounded-full text-xs mr-1 mb-1">
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
                            <p><strong>Stocking Date:</strong> {{ \Carbon\Carbon::parse($pond->hatching_date)->format('M d, Y') }}</p>
                            <p><strong>Harvest Date:</strong> {{ \Carbon\Carbon::parse($pond->harvest_date)->format('M d, Y') }}</p>
                        </div>

                        {{-- Countdown --}}
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
