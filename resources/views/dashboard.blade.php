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

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
    // Payloads from database (passed via Blade)
    // Format: { pond_id: {ph, water_temp, ammonia}, ... }
    window.sensorPayloads = {!! json_encode($payloads ?? []) !!};

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

        // ================= POND SELECTION =================
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

        // ================= RUN WATER TEST =================
        $('#simulate-btn').on('click', function () {

            if (!selectedPond) return;

            // Get the payload for the selected pond
            let sensorPayload = sensorPayloads[selectedPond.id] ?? null;

            if (!sensorPayload) {
                alert('No telemetry data found for this pond.');
                return;
            }

            $('#water-level').text('Measuring...');
            $('#device-status').text('Sampling...');
            $('#notification-message').text('Checking ammonia...');
            $('#ai-suggestion').text('AI analyzing pond #' + selectedPond.id + '...');

            setTimeout(() => {

                // Read JSON payload
                let temp = parseFloat(sensorPayload.water_temp);
                let pH = parseFloat(sensorPayload.ph);
                let ammonia = parseFloat(sensorPayload.ammonia);

                // Display values
                $('#water-level').text(temp + ' °C');
                $('#device-status').text(pH);

                $('#notification-message').html(`
                    Temp: ${temp} °C<br>
                    pH: ${pH}<br>
                    Ammonia: ${ammonia} ppm
                `);

                // ================= AI LOGIC =================
                let issues = [];
                let actions = [];

                // Temperature rules
                if (temp < 24) {
                    issues.push('Low water temperature');
                    actions.push('Increase water temperature gradually.');
                } else if (temp > 32) {
                    issues.push('High water temperature');
                    actions.push('Increase aeration and provide shade.');
                }

                // pH rules
                if (pH < 6.5) {
                    issues.push('Low pH (acidic water)');
                    actions.push('Apply agricultural lime carefully.');
                } else if (pH > 8.5) {
                    issues.push('High pH (alkaline water)');
                    actions.push('Perform partial water exchange.');
                }

                // Ammonia rules
                if (ammonia > 0.05) {
                    issues.push('Elevated ammonia level');
                    actions.push('Reduce feeding and change water immediately.');
                }

                // ================= AI RESPONSE =================
                let aiText = 'AI Assessment for Pond #' + selectedPond.id +
                    ' with fish species: ' + selectedPond.fish.join(', ') + '. ';

                aiText += issues.length
                    ? issues.join('. ') + '. Recommended actions: ' + actions.join(' ')
                    : 'All water parameters are within safe range. Continue regular monitoring.';

                typeText($('#ai-suggestion'), aiText);

            }, 1000);
        });
    });
</script>


</x-app-layout>
