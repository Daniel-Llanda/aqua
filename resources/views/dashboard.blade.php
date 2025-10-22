<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Water Test Dashboard') }}
            </h2>
            <span class="text-sm text-gray-500">Device Monitoring System</span>
        </div>
    </x-slot>

    <div class="p-6 bg-white rounded-md shadow-md dark:bg-dark-eval-1">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <!-- Water Level -->
            <div class="bg-orange-50 border border-orange-200 rounded-xl p-5 shadow-sm">
                <h3 class="text-lg font-semibold text-orange-600">Current Water Level</h3>
                <p id="water-level" class="text-3xl font-bold mt-3 text-gray-800">--</p>
                <p class="text-sm text-gray-500 mt-1">Measured in milliliters (ml)</p>
            </div>

            <!-- Device Status -->
            <div class="bg-green-50 border border-green-200 rounded-xl p-5 shadow-sm">
                <h3 class="text-lg font-semibold text-green-600">Device Status</h3>
                <p id="device-status" class="text-2xl font-bold mt-3 text-gray-800">Waiting...</p>
            </div>

            <!-- Notification -->
            <div id="notification-card" class="bg-gray-50 border border-gray-200 rounded-xl p-5 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-600">System Notification</h3>
                <p id="notification-message" class="mt-3 text-gray-700">No alerts at the moment.</p>
            </div>
        </div>

        <!-- AI Suggestion (UI only) -->
        <div class="mt-8 bg-orange-100 border border-orange-300 rounded-xl p-5 shadow-sm">
            <h3 class="text-lg font-semibold text-orange-700 flex items-center gap-2">
                🤖 AI Suggestion
            </h3>
            <p id="ai-suggestion" class="mt-3 text-gray-700 italic">
                AI will analyze and suggest solutions here when an issue is detected.
            </p>
        </div>

        <!-- Simulate Button -->
        <div class="mt-8 text-center">
            <button id="simulate-btn" 
                class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-lg shadow-md transition">
                Simulate Water Test
            </button>
        </div>
    </div>

    <!-- ✅ jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        $(document).ready(function () {
            $('#simulate-btn').on('click', function () {
                let waterLevel = Math.floor(Math.random() * 120); // random 0–120 ml
                let limit = 100;

                $('#water-level').text(waterLevel + ' ml');
                $('#device-status').text('Active');

                if (waterLevel >= limit) {
                    // Warning UI
                    $('#notification-card')
                        .removeClass('bg-gray-50 border-gray-200')
                        .addClass('bg-red-50 border-red-300');
                    $('#notification-message')
                        .html('⚠️ <strong>Warning:</strong> Water level exceeded the safe limit!')
                        .addClass('text-red-600 font-semibold');

                    // AI Suggestion (UI only)
                    $('#ai-suggestion').html(`
                        🚨 <strong>Possible Cause:</strong> Device may be submerged too deep or sensor malfunction.<br>
                        💡 <strong>Suggestion:</strong> Remove device from water and check for leaks or reset calibration.
                    `);
                } else {
                    // Normal UI
                    $('#notification-card')
                        .removeClass('bg-red-50 border-red-300')
                        .addClass('bg-gray-50 border-gray-200');
                    $('#notification-message')
                        .text('✅ All systems normal.')
                        .removeClass('text-red-600 font-semibold');
                    $('#ai-suggestion')
                        .text('AI will analyze and suggest solutions here when an issue is detected.');
                }
            });
        });
    </script>
</x-app-layout>
