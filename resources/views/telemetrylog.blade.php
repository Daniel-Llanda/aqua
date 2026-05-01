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
        <div class="grid gap-6">
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                <form method="GET" action="{{ route('telemetrylog') }}" class="space-y-2">
                    <input type="hidden" name="period" value="{{ $period }}">
                    <input type="hidden" name="filter_date" value="{{ $filterDate }}">

                    <label for="pond_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Select Pond
                    </label>

                    <select id="pond_id" name="pond_id"
                        onchange="this.form.submit()"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                        <option value="">Choose a Pond</option>

                        @foreach($ponds as $pond)
                            <option value="{{ $pond->id }}" @selected(request('pond_id') == $pond->id)>
                                Pond #{{ $pond->id }} - {{ number_format((float) $pond->hectares, 2) }} ha
                            </option>
                        @endforeach
                    </select>
                </form>

                @if($selectedPond)
                    <a href="{{ route('telemetrylog.report', ['pond_id' => $selectedPond->id, 'period' => $period, 'filter_date' => $filterDate, 'print' => 1]) }}"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Print / Save PDF
                    </a>
                @else
                    <button type="button"
                        disabled
                        class="inline-flex cursor-not-allowed items-center justify-center rounded-lg bg-gray-300 px-4 py-2 text-sm font-semibold text-gray-600">
                        Print / Save PDF
                    </button>
                @endif
            </div>

            <form method="GET" action="{{ route('telemetrylog') }}"
                class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                @if($selectedPond)
                    <input type="hidden" name="pond_id" value="{{ $selectedPond->id }}">
                @elseif(request('pond_id'))
                    <input type="hidden" name="pond_id" value="{{ request('pond_id') }}">
                @endif

                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                            Summary Period
                        </p>
                        <p class="text-xs text-gray-500">
                            Choose a day, week, or month. The table and report use the same date range.
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div class="flex rounded-lg border border-gray-200 bg-gray-100 p-1 dark:border-gray-700 dark:bg-gray-900">
                            @foreach(['day' => 'Day', 'week' => 'Week', 'month' => 'Month'] as $value => $label)
                                <label class="cursor-pointer rounded-md px-4 py-2 text-sm font-medium transition {{ $period === $value ? 'bg-white text-blue-700 shadow-sm dark:bg-gray-700 dark:text-blue-200' : 'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white' }}">
                                    <input type="radio"
                                        name="period"
                                        value="{{ $value }}"
                                        class="sr-only"
                                        onchange="this.form.submit()"
                                        @checked($period === $value)>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>

                        <div>
                            <label for="filter_date" class="block text-xs font-medium text-gray-600 dark:text-gray-300">
                                Date in period
                            </label>
                            <input id="filter_date"
                                type="date"
                                name="filter_date"
                                value="{{ $filterDate }}"
                                onchange="this.form.submit()"
                                class="mt-1 rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                        </div>

                        <button type="submit"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                            Apply
                        </button>
                    </div>
                </div>
            </form>

            @if(request('pond_id') && ! $selectedPond)
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    The selected pond was not found in your account.
                </div>
            @endif

            @if($selectedPond)
                <div class="grid gap-4 md:grid-cols-4">
                    <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                        <p class="text-xs font-medium uppercase text-gray-500">Covered Range</p>
                        <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                            {{ $dateRange['start']->format('M d, Y') }} - {{ $dateRange['end']->format('M d, Y') }}
                        </p>
                    </div>

                    <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                        <p class="text-xs font-medium uppercase text-gray-500">Records</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ number_format($telemetrySummary['count']) }}
                        </p>
                    </div>

                    <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                        <p class="text-xs font-medium uppercase text-gray-500">Avg Temperature</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ $telemetrySummary['avg_temperature'] !== null ? number_format($telemetrySummary['avg_temperature'], 1) . ' deg C' : '-' }}
                        </p>
                    </div>

                    <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                        <p class="text-xs font-medium uppercase text-gray-500">Avg pH / Ammonia</p>
                        <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                            pH {{ $telemetrySummary['avg_ph'] !== null ? number_format($telemetrySummary['avg_ph'], 2) : '-' }}
                        </p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                            NH3 {{ $telemetrySummary['avg_ammonia'] !== null ? number_format($telemetrySummary['avg_ammonia'], 3) : '-' }}
                        </p>
                    </div>
                </div>

                <div>
                    <div class="mb-4 flex flex-col gap-1">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                            Telemetry Records
                        </h3>
                        <p class="text-sm text-gray-500">
                            Showing {{ ucfirst($period) }} records for Pond #{{ $selectedPond->id }}.
                        </p>
                    </div>

                    @forelse($payloads as $log)
                        @php
                            $payload = is_array($log->payload) ? $log->payload : [];
                            $temperature = $payload['water_temp'] ?? $payload['temperature'] ?? null;
                            $ammonia = $payload['mq_ratio'] ?? $payload['ammonia'] ?? null;
                        @endphp

                        <div class="mb-3 rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                            <div class="grid gap-4 text-sm md:grid-cols-3">
                                <div>
                                    <span class="font-medium text-gray-700 dark:text-gray-200">pH:</span>
                                    {{ $payload['ph'] ?? '-' }}
                                </div>

                                <div>
                                    <span class="font-medium text-gray-700 dark:text-gray-200">Water Temp:</span>
                                    {{ $temperature !== null && $temperature !== '' ? $temperature . ' deg C' : '-' }}
                                </div>

                                <div>
                                    <span class="font-medium text-gray-700 dark:text-gray-200">Ammonia:</span>
                                    {{ $ammonia ?? '-' }}
                                </div>
                            </div>

                            <div class="mt-2 text-xs text-gray-500">
                                {{ $log->created_at?->format('M d, Y h:i A') ?? 'N/A' }}
                            </div>
                        </div>
                    @empty
                        <p class="rounded-lg bg-white p-4 text-sm text-gray-500 shadow-sm dark:bg-gray-800">
                            No telemetry data found for this pond and selected period.
                        </p>
                    @endforelse

                    @if($payloads instanceof \Illuminate\Contracts\Pagination\Paginator && $payloads->hasPages())
                        <div class="mt-6">
                            {{ $payloads->onEachSide(1)->links() }}
                        </div>
                    @endif
                </div>
            @else
                <div class="rounded-lg bg-white p-6 text-sm text-gray-500 shadow-sm dark:bg-gray-800">
                    Choose a pond to view telemetry records, summary filters, and PDF export options.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
