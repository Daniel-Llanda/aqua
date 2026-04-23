<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                Water Test Dashboard
            </h2>
            <p class="text-sm text-gray-500">
                Device Monitoring & Analysis System
            </p>
        </div>
    </x-slot>


    <div class="p-6 bg-gray-50 dark:bg-dark-eval-1 rounded-lg shadow-sm">
        <!-- ================= REAL-TIME STATUS ================= -->
        <!-- <div class="mb-4 text-center mt-6">
            <h3 class="text-lg font-semibold">Real-Time Water Quality Status</h3>

            @if($status == 'Normal')
                <div class="bg-green-500 text-white px-4 py-2 rounded">
                    NORMAL
                </div>
            @elseif($status == 'Warning')
                <div class="bg-yellow-500 text-white px-4 py-2 rounded">
                    WARNING
                </div>
            @elseif($status == 'Critical')
                <div class="bg-red-600 text-white px-4 py-2 rounded">
                    CRITICAL
                </div>
            @else
                <div class="bg-gray-500 text-white px-4 py-2 rounded">
                    NO DATA
                </div>
            @endif
        </div> -->

        <!-- ================= POND SELECTOR ================= -->
        <div class="mb-6">
            <form method="GET" action="{{ route('dashboard') }}">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Select Pond
                </label>

                <select id="pond-select"
                    name="pond_id"
                    onchange="this.form.submit()"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700
                           dark:bg-gray-900 dark:text-gray-100
                           focus:ring-2 focus:ring-blue-500">
                    <option value="">Choose a Pond</option>

                    @foreach($ponds as $pond)
                    <option
                        value="{{ $pond->id }}"
                        data-fish='@json(is_array($pond->fish_type) ? $pond->fish_type : [])'
                        data-area="{{ $pond->hectares }}"
                        {{ $selectedPond && $selectedPond->id === $pond->id ? 'selected' : '' }}>
                        Pond #{{ $pond->id }} — {{ $pond->hectares }} ha
                    </option>
                    @endforeach
                </select>
            </form>
        </div>

        @php
            $selectedFish = $selectedPond && is_array($selectedPond->fish_type) ? $selectedPond->fish_type : [];
        @endphp

        <!-- ================= FISH DISPLAY ================= -->
        <div id="pond-fish-container" class="{{ $selectedPond ? '' : 'hidden' }} mb-8">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                Fish Species in Selected Pond
            </h4>
            <div id="pond-fish-list" class="flex flex-wrap gap-2">
                @foreach($selectedFish as $fish)
                    <span class="bg-blue-100 text-blue-700 text-xs px-3 py-1 rounded-full">
                        {{ $fish }}
                    </span>
                @endforeach
            </div>
        </div>

        @if($selectedPond)
            <!-- ================= WATER QUALITY CHART ================= -->
            <div id="pond-chart-section" class="mb-8">
                <div class="mb-3">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                        Pond #{{ $selectedPond->id }} Water Quality Trends
                    </h3>
                    <p class="text-sm text-gray-500">
                        Charts use telemetry recorded for this pond only.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white dark:bg-gray-900 border rounded-lg p-4">
                        <h4 class="text-sm font-semibold mb-2">pH Level</h4>
                        <canvas id="phChart"></canvas>
                    </div>

                    <div class="bg-white dark:bg-gray-900 border rounded-lg p-4">
                        <h4 class="text-sm font-semibold mb-2">Water Temperature (°C)</h4>
                        <canvas id="tempChart"></canvas>
                    </div>

                    <div class="bg-white dark:bg-gray-900 border rounded-lg p-4">
                        <h4 class="text-sm font-semibold mb-2">Ammonia (ppm)</h4>
                        <canvas id="ammoniaChart"></canvas>
                    </div>
                </div>
            </div>
        @endif

        <!-- ================= KPI CARDS ================= -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <!-- Water Temp -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">
                    Water Temperature
                </h3>
                <p id="water-level" class="text-3xl font-semibold text-gray-900 dark:text-gray-100 mt-3">
                    --
                </p>
                <p class="text-xs text-gray-400 mt-1">°C</p>
            </div>

            <!-- pH -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">
                    pH Level
                </h3>
                <p id="device-status" class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mt-3">
                    --
                </p>
            </div>

            <!-- Ammonia -->
            <div id="notification-card"
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">
                    Ammonia Sensor
                </h3>
                <p id="notification-message" class="text-3xl font-semibold text-gray-900 dark:text-gray-100 mt-3">
                    --
                </p>
                <p class="text-xs text-gray-400 mt-1">ppm</p>
            </div>
        </div>

        <!-- ================= AI PANEL ================= -->
        <div class="mt-8 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                AI Analysis & Recommendation
            </h3>

            <div id="ai-suggestion"
                class="mt-3 text-gray-700 dark:text-gray-300 text-sm leading-relaxed italic">
                Select a pond and run water test to generate insights.
            </div>
        </div>

        <!-- ================= ACTION BUTTON ================= -->
        <div class="mt-8 flex justify-end">
            <button id="simulate-btn" {{ $selectedPond ? '' : 'disabled' }}
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700
                       disabled:bg-gray-400 disabled:cursor-not-allowed
                       text-white text-sm font-medium px-6 py-2.5 rounded-md
                       shadow-sm transition focus:ring-2 focus:ring-blue-500">
                Run Water Test
            </button>
        </div>



    </div>

    <!-- ================= SCRIPTS ================= -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


    <script>
        // ================= Pass payloads to JS =================
        window.sensorPayloads = @json(
            $payLoads->mapWithKeys(function ($p) {
                // Keep one payload per pond id (latest wins from query order)
                return [$p->pond_id => $p->payload];
            })
        );
        window.selectedPondContext = @json($selectedPond ? [
            'id' => (string) $selectedPond->id,
            'fish' => is_array($selectedPond->fish_type) ? $selectedPond->fish_type : [],
        ] : null);

        $(document).ready(function() {

            let selectedPond = window.selectedPondContext;
            const smsAlertEndpoint = @json(route('dashboard.alerts.sms'));
            const csrfToken = @json(csrf_token());

            function typeText(el, text, speed = 20) {
                el.html('');
                let i = 0;
                let interval = setInterval(() => {
                    if (i < text.length) {
                        el.append(text.charAt(i));
                        i++;
                    } else {
                        clearInterval(interval);
                    }
                }, speed);
            }

            async function sendSmsAlert(payload) {
                try {
                    await fetch(smsAlertEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload),
                    });
                } catch (error) {
                    // Keep dashboard UX unaffected if alert sending fails.
                    console.warn('SMS alert failed:', error);
                }
            }

            let phChart = null;
            let tempChart = null;
            let ammoniaChart = null;

            function buildLineChart(canvasId, label, data, borderColor, backgroundColor) {
                const canvas = document.getElementById(canvasId);

                if (!canvas) {
                    return null;
                }

                return new Chart(canvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: @json($labels),
                        datasets: [{
                            label: label,
                            data: data,
                            borderColor: borderColor,
                            backgroundColor: backgroundColor,
                            tension: 0.3
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            if (selectedPond) {
                phChart = buildLineChart('phChart', 'pH Level', @json($phData), 'blue', 'rgba(0,0,255,0.1)');
                tempChart = buildLineChart('tempChart', 'Water Temperature (°C)', @json($tempData), 'orange', 'rgba(255,165,0,0.1)');
                ammoniaChart = buildLineChart('ammoniaChart', 'Ammonia (ppm)', @json($ammoniaData), 'red', 'rgba(255,0,0,0.1)');
            }

            // ================= Pond Selection =================
            $('#pond-select').on('change', function() {
                const option = $(this).find(':selected');

                if (!option.val()) {
                    $('#pond-fish-container').addClass('hidden');
                    $('#pond-fish-list').html('');
                    $('#simulate-btn').prop('disabled', true);
                    selectedPond = null;
                    return;
                }

                selectedPond = {
                    id: option.val(),
                    fish: option.data('fish')
                };

                const fishList = Array.isArray(selectedPond.fish) ? selectedPond.fish : [];
                $('#pond-fish-list').html('');
                fishList.forEach(fish => {
                    $('#pond-fish-list').append(`
                    <span class="bg-blue-100 text-blue-700 text-xs px-3 py-1 rounded-full">
                        ${fish}
                    </span>
                `);
                });

                $('#pond-fish-container').removeClass('hidden');
                $('#simulate-btn').prop('disabled', false);
            });

            // ================= Run Water Test =================
            $('#simulate-btn').on('click', function() {
                if (!selectedPond) return;

                let sensorPayload = sensorPayloads[selectedPond.id] ?? null;

                if (!sensorPayload) {
                    alert('No telemetry data found for this pond.');
                    return;
                }

                // Show measuring text
                $('#water-level').text('Measuring...');
                $('#device-status').text('Sampling...');
                $('#notification-message').text('Checking ammonia...');
                $('#ai-suggestion').text('AI analyzing pond #' + selectedPond.id + '...');

                setTimeout(() => {
                    const parseSensorNumber = (value) => {
                        const parsed = Number.parseFloat(value);
                        return Number.isFinite(parsed) ? parsed : 0;
                    };

                    let temp = parseSensorNumber(sensorPayload.water_temp ?? sensorPayload.temperature);
                    let pH = parseSensorNumber(sensorPayload.ph);
                    let ammonia = parseSensorNumber(sensorPayload.mq_ratio ?? sensorPayload.ammonia);
                    const smsConditionKeys = new Set();
                    const triggerSmsForCondition = (conditionKey) => smsConditionKeys.add(conditionKey);

                    // Update KPI cards
                    $('#water-level').text(temp + ' °C');
                    $('#device-status').text(pH);
                    $('#notification-message').text(ammonia);

                    // ================= AI LOGIC =================
                    let issues = [];
                    let actions = [];

                    if (temp < 24) {
                        issues.push('Low water temperature');
                        actions.push('Increase water temperature gradually.');
                        triggerSmsForCondition('temp_low');
                    } else if (temp > 32) {
                        issues.push('High water temperature');
                        actions.push('Increase aeration and provide shade.');
                        triggerSmsForCondition('temp_high');
                    }

                    if (pH < 6.5) {
                        issues.push('Low pH (acidic water)');
                        actions.push('Apply agricultural lime carefully.');
                        triggerSmsForCondition('ph_low');
                    } else if (pH > 8.5) {
                        issues.push('High pH (alkaline water)');
                        actions.push('Perform partial water exchange.');
                        triggerSmsForCondition('ph_high');
                    }

                    if (ammonia > 0.05) {
                        issues.push('Elevated ammonia level');
                        actions.push('Reduce feeding and change water immediately.');
                        triggerSmsForCondition('ammonia_high');
                    }

                    const fishSpecies = Array.isArray(selectedPond.fish) && selectedPond.fish.length
                        ? selectedPond.fish.join(', ')
                        : 'unknown species';

                    let aiText = 'AI Assessment for Pond #' + selectedPond.id +
                        ' with fish species: ' + fishSpecies + '. ';

                    aiText += issues.length ?
                        issues.join('. ') + '. Recommended actions: ' + actions.join(' ') :
                        'All water parameters are within safe range. Continue regular monitoring.';

                    typeText($('#ai-suggestion'), aiText);

                    if (smsConditionKeys.size > 0) {
                        sendSmsAlert({
                            pond_id: Number.parseInt(selectedPond.id, 10),
                            temp: temp,
                            ph: pH,
                            ammonia: ammonia,
                            ai_text: aiText,
                            issues: issues,
                            actions: actions,
                        });
                    }

                    if (phChart && tempChart && ammoniaChart) {
                        // ================= Update Charts =================
                        phChart.data.datasets[0].data = [...phChart.data.datasets[0].data, pH];
                        tempChart.data.datasets[0].data = [...tempChart.data.datasets[0].data, temp];
                        ammoniaChart.data.datasets[0].data = [...ammoniaChart.data.datasets[0].data, ammonia];

                        let currentTime = new Date().toLocaleTimeString();
                        phChart.data.labels.push(currentTime);
                        tempChart.data.labels.push(currentTime);
                        ammoniaChart.data.labels.push(currentTime);

                        phChart.update();
                        tempChart.update();
                        ammoniaChart.update();
                    }

                }, 1000);
            });
        });
    </script>

</x-app-layout>
