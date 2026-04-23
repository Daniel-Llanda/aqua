<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                Start New Cycle
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Pond #{{ $pond->id }}. Configure this cycle before entering species quantities.
            </p>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto">
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
            <form method="POST" action="{{ route('pond.cycles.store', $pond) }}" class="px-6 py-6 space-y-6">
                @csrf

                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    Hatching start date is set automatically to today ({{ now()->format('M d, Y') }}).
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Harvest Date
                        </label>
                        <input type="date" name="harvest_date" required min="{{ now()->toDateString() }}"
                               value="{{ old('harvest_date') }}"
                               class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <p class="mt-1 text-xs text-gray-500">
                            Harvest input unlocks on or after this date.
                        </p>
                    </div>

                    <div class="flex items-end">
                        <label class="inline-flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" name="reuse_previous_data" value="1" {{ old('reuse_previous_data', $lastCompletedCycle ? '1' : null) ? 'checked' : '' }}
                                   class="mt-1 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <span>
                                Reuse previous cycle quantity defaults
                                @if($lastCompletedCycle)
                                    <span class="block text-xs text-gray-500 mt-1">Uses Cycle #{{ $lastCompletedCycle->cycle_number }} where species names match.</span>
                                @else
                                    <span class="block text-xs text-gray-500 mt-1">No completed cycle found. Defaults will be empty.</span>
                                @endif
                            </span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Cycle Species (Select all that apply)
                    </label>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Fish</h4>
                            <div class="space-y-2 text-sm">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="fish_type[]" value="Milkfish (Chanos chanos)" {{ in_array('Milkfish (Chanos chanos)', old('fish_type', $defaultSpecies)) ? 'checked' : '' }}>
                                    Milkfish / Bangus (Chanos chanos)
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="fish_type[]" value="Tilapia (General)" {{ in_array('Tilapia (General)', old('fish_type', $defaultSpecies)) ? 'checked' : '' }}>
                                    Tilapia (General)
                                </label>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Shrimp / Prawns</h4>
                            <div class="space-y-2 text-sm">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="fish_type[]" value="Penaeid Shrimp (General)" {{ in_array('Penaeid Shrimp (General)', old('fish_type', $defaultSpecies)) ? 'checked' : '' }}>
                                    Penaeid Shrimp (General brackishwater group)
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="fish_type[]" value="Black Tiger Shrimp (Penaeus monodon)" {{ in_array('Black Tiger Shrimp (Penaeus monodon)', old('fish_type', $defaultSpecies)) ? 'checked' : '' }}>
                                    Black Tiger Shrimp (Penaeus monodon)
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="fish_type[]" value="Pacific White Shrimp (Litopenaeus vannamei)" {{ in_array('Pacific White Shrimp (Litopenaeus vannamei)', old('fish_type', $defaultSpecies)) ? 'checked' : '' }}>
                                    Pacific White Shrimp / Whiteleg Shrimp (Litopenaeus vannamei)
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="fish_type[]" value="Indian White Shrimp (Penaeus indicus)" {{ in_array('Indian White Shrimp (Penaeus indicus)', old('fish_type', $defaultSpecies)) ? 'checked' : '' }}>
                                    Indian White Shrimp (Penaeus indicus)
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="fish_type[]" value="Banana Shrimp (Metapenaeus spp.)" {{ in_array('Banana Shrimp (Metapenaeus spp.)', old('fish_type', $defaultSpecies)) ? 'checked' : '' }}>
                                    Banana / Speckled Shrimp (Metapenaeus ensis / M. monoceros)
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="fish_type[]" value="Giant Freshwater Prawn (Macrobrachium rosenbergii)" {{ in_array('Giant Freshwater Prawn (Macrobrachium rosenbergii)', old('fish_type', $defaultSpecies)) ? 'checked' : '' }}>
                                    Giant Freshwater Prawn (Macrobrachium rosenbergii)
                                </label>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Crabs</h4>
                            <div class="space-y-2 text-sm">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="fish_type[]" value="Mud Crab (Scylla serrata)" {{ in_array('Mud Crab (Scylla serrata)', old('fish_type', $defaultSpecies)) ? 'checked' : '' }}>
                                    Mud Crab / Giant Mangrove Crab (Scylla serrata)
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="fish_type[]" value="Green Mud Crab (Scylla tranquebarica)" {{ in_array('Green Mud Crab (Scylla tranquebarica)', old('fish_type', $defaultSpecies)) ? 'checked' : '' }}>
                                    Green Mud Crab (Scylla tranquebarica)
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="fish_type[]" value="Orange Mud Crab (Scylla olivacea)" {{ in_array('Orange Mud Crab (Scylla olivacea)', old('fish_type', $defaultSpecies)) ? 'checked' : '' }}>
                                    Orange Mud Crab (Scylla olivacea)
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="fish_type[]" value="Blue Swimming Crab (Portunus pelagicus)" {{ in_array('Blue Swimming Crab (Portunus pelagicus)', old('fish_type', $defaultSpecies)) ? 'checked' : '' }}>
                                    Blue Swimming Crab (Portunus pelagicus)
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="fish_type[]" value="Atlantic Blue Crab (Callinectes sapidus)" {{ in_array('Atlantic Blue Crab (Callinectes sapidus)', old('fish_type', $defaultSpecies)) ? 'checked' : '' }}>
                                    Atlantic Blue Crab (Callinectes sapidus)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between pt-2">
                    <a href="{{ route('pond-info') }}"
                       class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium">
                        Back to Registered Ponds
                    </a>

                    <button type="submit"
                            class="inline-flex items-center px-6 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium shadow-sm focus:ring-2 focus:ring-emerald-500">
                        Start Cycle
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
