<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                Add Pond
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Register a new pond and selected aquatic species.
            </p>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <form method="POST" action="{{ route('pond.store') }}" class="px-6 py-6 space-y-6">
                @csrf

                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                        Pond Information
                    </h3>
                    <p class="text-sm text-gray-500">
                        Add pond details. Start a cycle afterward to set species and quantity data.
                    </p>
                </div>

                @if ($errors->any())
                    <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Pond Area (Hectares)
                        </label>
                        <input type="number" step="0.01" name="hectares" required value="{{ old('hectares') }}"
                               placeholder="e.g. 1.50"
                               class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <p class="mt-1 text-xs text-gray-500">
                            Total surface area of the pond
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            Cultured Aquatic Species (Select all that apply)
                        </label>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">
                                    Fish
                                </h4>
                                <div class="space-y-2 text-sm">
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="fish_type[]" value="Milkfish (Chanos chanos)" {{ in_array('Milkfish (Chanos chanos)', old('fish_type', [])) ? 'checked' : '' }}>
                                        Milkfish / Bangus (Chanos chanos)
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="fish_type[]" value="Tilapia (General)" {{ in_array('Tilapia (General)', old('fish_type', [])) ? 'checked' : '' }}>
                                        Tilapia (General)
                                    </label>
                                </div>
                            </div>

                            <div>
                                <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">
                                    Shrimp / Prawns
                                </h4>
                                <div class="space-y-2 text-sm">
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="fish_type[]" value="Penaeid Shrimp (General)" {{ in_array('Penaeid Shrimp (General)', old('fish_type', [])) ? 'checked' : '' }}>
                                        Penaeid Shrimp (General brackishwater group)
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="fish_type[]" value="Black Tiger Shrimp (Penaeus monodon)" {{ in_array('Black Tiger Shrimp (Penaeus monodon)', old('fish_type', [])) ? 'checked' : '' }}>
                                        Black Tiger Shrimp (Penaeus monodon)
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="fish_type[]" value="Pacific White Shrimp (Litopenaeus vannamei)" {{ in_array('Pacific White Shrimp (Litopenaeus vannamei)', old('fish_type', [])) ? 'checked' : '' }}>
                                        Pacific White Shrimp / Whiteleg Shrimp (Litopenaeus vannamei)
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="fish_type[]" value="Indian White Shrimp (Penaeus indicus)" {{ in_array('Indian White Shrimp (Penaeus indicus)', old('fish_type', [])) ? 'checked' : '' }}>
                                        Indian White Shrimp (Penaeus indicus)
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="fish_type[]" value="Banana Shrimp (Metapenaeus spp.)" {{ in_array('Banana Shrimp (Metapenaeus spp.)', old('fish_type', [])) ? 'checked' : '' }}>
                                        Banana / Speckled Shrimp (Metapenaeus ensis / M. monoceros)
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="fish_type[]" value="Giant Freshwater Prawn (Macrobrachium rosenbergii)" {{ in_array('Giant Freshwater Prawn (Macrobrachium rosenbergii)', old('fish_type', [])) ? 'checked' : '' }}>
                                        Giant Freshwater Prawn (Macrobrachium rosenbergii)
                                    </label>
                                </div>
                            </div>

                            <div>
                                <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">
                                    Crabs
                                </h4>
                                <div class="space-y-2 text-sm">
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="fish_type[]" value="Mud Crab (Scylla serrata)" {{ in_array('Mud Crab (Scylla serrata)', old('fish_type', [])) ? 'checked' : '' }}>
                                        Mud Crab / Giant Mangrove Crab (Scylla serrata)
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="fish_type[]" value="Green Mud Crab (Scylla tranquebarica)" {{ in_array('Green Mud Crab (Scylla tranquebarica)', old('fish_type', [])) ? 'checked' : '' }}>
                                        Green Mud Crab (Scylla tranquebarica)
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="fish_type[]" value="Orange Mud Crab (Scylla olivacea)" {{ in_array('Orange Mud Crab (Scylla olivacea)', old('fish_type', [])) ? 'checked' : '' }}>
                                        Orange Mud Crab (Scylla olivacea)
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="fish_type[]" value="Blue Swimming Crab (Portunus pelagicus)" {{ in_array('Blue Swimming Crab (Portunus pelagicus)', old('fish_type', [])) ? 'checked' : '' }}>
                                        Blue Swimming Crab (Portunus pelagicus)
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="fish_type[]" value="Atlantic Blue Crab (Callinectes sapidus)" {{ in_array('Atlantic Blue Crab (Callinectes sapidus)', old('fish_type', [])) ? 'checked' : '' }}>
                                        Atlantic Blue Crab (Callinectes sapidus)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between pt-4">
                    <a href="{{ route('pond-info') }}" class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium">
                        Back to Registered Ponds
                    </a>

                    <button type="submit"
                            class="inline-flex items-center px-6 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium shadow-sm focus:ring-2 focus:ring-blue-500">
                        Save Pond Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
