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
         <!-- ================= WATER QUALITY CHART ================= -->
        <div class="my-5 grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- pH Chart -->
            <div class="bg-white dark:bg-gray-900 border rounded-lg p-4">
                <h4 class="text-sm font-semibold mb-2">pH Level</h4>
                <canvas id="phChart"></canvas>
            </div>

            <!-- Temperature Chart -->
            <div class="bg-white dark:bg-gray-900 border rounded-lg p-4">
                <h4 class="text-sm font-semibold mb-2">Water Temperature (°C)</h4>
                <canvas id="tempChart"></canvas>
            </div>

            <!-- Ammonia Chart -->
            <div class="bg-white dark:bg-gray-900 border rounded-lg p-4">
                <h4 class="text-sm font-semibold mb-2">Ammonia (ppm)</h4>
                <canvas id="ammoniaChart"></canvas>
            </div>
        </div>


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
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Select Pond
            </label>

            <select id="pond-select"
                class="w-full rounded-lg border-gray-300 dark:border-gray-700
                       dark:bg-gray-900 dark:text-gray-100
                       focus:ring-2 focus:ring-blue-500">
                <option value="">Choose a Pond</option>

                @foreach($ponds as $pond)
                    <option
                        value="{{ $pond->id }}"
                        data-fish='@json(json_decode($pond->fish_type))'
                        data-area="{{ $pond->hectares }}">
                        Pond #{{ $pond->id }} — {{ $pond->hectares }} ha
                    </option>
                @endforeach
            </select>
        </div>

        <!-- ================= FISH DISPLAY ================= -->
        <div id="pond-fish-container" class="hidden mb-8">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                Fish Species in Selected Pond
            </h4>
            <div id="pond-fish-list" class="flex flex-wrap gap-2"></div>
        </div>

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
                <p id="notification-message" class="mt-3 text-gray-700 dark:text-gray-300">
                    No data.
                </p>
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
            <button id="simulate-btn" disabled
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
    window.sensorPayloads = {!! json_encode($payLoads->mapWithKeys(function($p) {
        return [$p->pond_id => $p->payload]; // payload is already array
    })) !!};

    $(document).ready(function () {

        let selectedPond = null;

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

        // ================= Initialize Charts =================
        const phChart = new Chart(document.getElementById('phChart').getContext('2d'), {
            type: 'line',
            data: { labels: @json($labels), datasets: [{ label: 'pH Level', data: @json($phData), borderColor: 'blue', backgroundColor: 'rgba(0,0,255,0.1)', tension: 0.3 }] },
            options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
        });

        const tempChart = new Chart(document.getElementById('tempChart').getContext('2d'), {
            type: 'line',
            data: { labels: @json($labels), datasets: [{ label: 'Water Temperature (°C)', data: @json($tempData), borderColor: 'orange', backgroundColor: 'rgba(255,165,0,0.1)', tension: 0.3 }] },
            options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
        });

        const ammoniaChart = new Chart(document.getElementById('ammoniaChart').getContext('2d'), {
            type: 'line',
            data: { labels: @json($labels), datasets: [{ label: 'Ammonia (ppm)', data: @json($ammoniaData), borderColor: 'red', backgroundColor: 'rgba(255,0,0,0.1)', tension: 0.3 }] },
            options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
        });

        // ================= Pond Selection =================
        $('#pond-select').on('change', function () {
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

            $('#pond-fish-list').html('');
            selectedPond.fish.forEach(fish => {
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
        $('#simulate-btn').on('click', function () {
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
                let temp = parseFloat(sensorPayload.temperature);
                let pH = parseFloat(sensorPayload.ph);
                let ammonia = parseFloat(sensorPayload.mq_ratio);

                // Update KPI cards
                $('#water-level').text(temp + ' °C');
                $('#device-status').text(pH);
                $('#notification-message').html(`
                    Ammonia: ${ammonia} ppm
                `);

                // ================= AI LOGIC =================
                let issues = [];
                let actions = [];

                if (temp < 24) { issues.push('Low water temperature'); actions.push('Increase water temperature gradually.'); }
                else if (temp > 32) { issues.push('High water temperature'); actions.push('Increase aeration and provide shade.'); }

                if (pH < 6.5) { issues.push('Low pH (acidic water)'); actions.push('Apply agricultural lime carefully.'); }
                else if (pH > 8.5) { issues.push('High pH (alkaline water)'); actions.push('Perform partial water exchange.'); }

                if (ammonia > 0.05) { issues.push('Elevated ammonia level'); actions.push('Reduce feeding and change water immediately.'); }

                let aiText = 'AI Assessment for Pond #' + selectedPond.id +
                    ' with fish species: ' + selectedPond.fish.join(', ') + '. ';

                aiText += issues.length
                    ? issues.join('. ') + '. Recommended actions: ' + actions.join(' ')
                    : 'All water parameters are within safe range. Continue regular monitoring.';

                typeText($('#ai-suggestion'), aiText);

                // ================= Update Charts =================
                phChart.data.datasets[0].data = [ ...phChart.data.datasets[0].data, pH ];
                tempChart.data.datasets[0].data = [ ...tempChart.data.datasets[0].data, temp ];
                ammoniaChart.data.datasets[0].data = [ ...ammoniaChart.data.datasets[0].data, ammonia ];

                let currentTime = new Date().toLocaleTimeString();
                phChart.data.labels.push(currentTime);
                tempChart.data.labels.push(currentTime);
                ammoniaChart.data.labels.push(currentTime);

                phChart.update();
                tempChart.update();
                ammoniaChart.update();

            }, 1000);
        });
    });
</script>

</x-app-layout>
