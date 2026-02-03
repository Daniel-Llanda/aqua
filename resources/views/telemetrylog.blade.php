<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                Telemetry Log
            </h2>
            <p class="text-sm text-gray-500">
                Historical telemetry data from your ponds.
            </p>
        </div>
    </x-slot>

    <div class="p-6 bg-gray-50 dark:bg-dark-eval-1 rounded-lg shadow-sm">

        <!-- ================= POND SELECTOR ================= -->
        <div class="mb-6">
            <form method="GET" action="{{ route('telemetrylog') }}">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Select Pond
                </label>

                <select name="pond_id"
                    onchange="this.form.submit()"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700
                            dark:bg-gray-900 dark:text-gray-100
                            focus:ring-2 focus:ring-blue-500">

                    <option value="">Choose a Pond</option>

                    @foreach($ponds as $pond)
                        <option value="{{ $pond->id }}"
                            {{ request('pond_id') == $pond->id ? 'selected' : '' }}>
                            Pond #{{ $pond->id }} — {{ $pond->hectares }} ha
                        </option>
                    @endforeach
                </select>
            </form>

            @if(request('pond_id'))
                <div class="mt-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">
                        Telemetry Records
                    </h3>

                    @forelse($payloads as $log)
                        <div class="mb-3 p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
                            <div class="grid grid-cols-3 gap-4 text-sm">
                                <div>
                                    <span class="font-medium">pH:</span>
                                    {{ $log->payload['ph'] ?? '—' }}
                                </div>

                                <div>
                                    <span class="font-medium">Water Temp:</span>
                                    {{ $log->payload['water_temp'] ?? '—' }} °C
                                </div>

                                <div>
                                    <span class="font-medium">Ammonia:</span>
                                    {{ $log->payload['ammonia'] ?? '—' }}
                                </div>
                            </div>

                            <div class="mt-2 text-xs text-gray-500">
                                {{ $log->created_at?->format('M d, Y h:i A') ?? 'N/A' }}
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm">
                            No telemetry data found for this pond.
                        </p>
                    @endforelse
                </div>
            @endif
        </div>

    </div>



</x-app-layout>
