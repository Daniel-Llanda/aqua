<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/2.9.2/semantic.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.6/css/dataTables.semanticui.css">
    <style>
        .sidebar-nav {
            flex: 1;
            padding: 1.5rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        /* Default link style */
        .nav-link {
            display: block;
            padding: 0.6rem 1rem;
            border-radius: 0.5rem;
            color: #ffffff;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.25s ease, transform 0.15s ease;
        }

        /* Hover effect */
        .nav-link:hover {
            background-color: #3b82f6;
             color: #ffffff; /* blue-500 */
            transform: translateX(4px);
        }

        /* Active / current page */
        .nav-link.active {
            background-color: #1d4ed8; /* blue-700 */
        }

       

    </style>
</head>

<body class="bg-gray-100 font-sans antialiased min-h-screen">
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/2.9.2/semantic.min.js"></script>
    <script src="https://cdn.datatables.net/2.3.6/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.3.6/js/dataTables.semanticui.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Sidebar -->
    <aside class="fixed inset-y-0 left-0 z-30 h-screen w-64 overflow-y-auto bg-blue-600 text-white flex flex-col">
        <div class="px-6 py-6 border-b border-blue-500">
            <h1 class="text-2xl font-bold">Admin Panel</h1>
        </div>

        <nav class="sidebar-nav">
            <a href="{{ route('admin.dashboard') }}" class="nav-link">Dashboard</a>
            <a href="{{ route('admin.users') }}" class="nav-link active">Users</a>
            <!-- <a href="{{ route('admin.telemetry') }}" class="nav-link">Telemetry</a> -->
        </nav>

        <div class="px-6 py-4 border-t border-blue-500">
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit"
                    class="w-full bg-white text-blue-600 font-semibold py-2 rounded-lg hover:bg-blue-100 transition">
                    Logout
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="ml-64 min-h-screen min-w-0 flex flex-col">
        <!-- Top Bar -->
        <header class="bg-white shadow-sm px-8 py-4 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">Dashboard</h2>
            <div class="text-gray-600">
                Welcome, <span class="font-semibold text-blue-600">Admin</span>
            </div>
        </header>

        <!-- Content -->
        <section class="p-8 flex-1">
            <div class="bg-white p-6 rounded-2xl shadow-md">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-semibold text-gray-700">User List</h3>
                    <button id="openAddUserModal"
                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                        Add New User
                    </button>

                </div>

                <div class="overflow-x-auto">
                    <table id="myTable" class="min-w-full border border-gray-200 rounded-lg">
                        <thead class="bg-blue-600 text-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-semibold">#</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold">Name</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold">Email</th>
                                
                                <th class="px-6 py-3 text-left text-sm font-semibold">Created At</th>
                                <th class="px-6 py-3 text-center text-sm font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($users as $user)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-3 text-sm text-gray-700">{{ $user->id }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-700">{{ $user->name }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-700">{{ $user->email }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-700">{{ $user->created_at?->format('Y-m-d') ?? 'N/A'}}</td>
                                    <td class="px-6 py-3 text-center">
                                        <button
                                            class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 transition view-user"
                                            data-user='@json($user)'>
                                            View
                                        </button>


                                    </td>
                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
    <!-- User Info Modal -->
<!-- Overlay -->
<div id="userModalOverlay"
     class="fixed inset-0 bg-black bg-opacity-50 hidden z-40"></div>

<!-- Modal -->
<div id="userModal"
     class="fixed inset-0 flex items-center justify-center hidden z-50">

    <div class="bg-white rounded-lg shadow-lg w-full max-w-5xl max-h-screen overflow-y-auto">

        <!-- Header -->
        <div class="flex justify-between items-center px-6 py-4 border-b">
            <h3 class="text-lg font-semibold">User Information</h3>
            <button id="closeModal" class="text-gray-500 hover:text-red-500 text-xl">
                &times;
            </button>
        </div>

        <!-- Body -->
        <div class="px-6 py-4 space-y-4">

            <!-- User Info -->
            <div>
                <p><strong>Name:</strong> <span id="modalUserName"></span></p>
                <p><strong>Email:</strong> <span id="modalUserEmail"></span></p>
                <p><strong>Joined:</strong> <span id="modalUserCreated"></span></p>
            </div>

            <!-- Ponds -->
            <div>
                <h4 class="font-semibold mt-4 mb-2">Ponds</h4>
                <div id="modalUserPonds" class="space-y-2"></div>
            </div>

            <div id="adminPondTelemetrySection" class="hidden border-t pt-4">
                <div class="mb-4">
                    <h4 id="adminSelectedPondTitle" class="font-semibold text-gray-800"></h4>
                    <p class="text-sm text-gray-500">Telemetry charts are scoped to the selected pond only.</p>
                </div>

                <p id="adminPondNoTelemetry" class="hidden text-sm text-gray-500">
                    No telemetry data found for this pond.
                </p>

                <div id="adminPondCharts" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="border rounded-lg p-4">
                        <h5 class="text-sm font-semibold mb-2">pH Level</h5>
                        <canvas id="adminPhChart"></canvas>
                    </div>

                    <div class="border rounded-lg p-4">
                        <h5 class="text-sm font-semibold mb-2">Water Temperature (°C)</h5>
                        <canvas id="adminTempChart"></canvas>
                    </div>

                    <div class="border rounded-lg p-4">
                        <h5 class="text-sm font-semibold mb-2">Ammonia (ppm)</h5>
                        <canvas id="adminAmmoniaChart"></canvas>
                    </div>
                </div>

                <div id="adminPondHarvestComparisonSection" class="hidden mt-4 rounded-lg border p-4">
                    <div class="mb-4">
                        <h5 class="text-sm font-semibold text-gray-800">Previous vs Latest Harvest</h5>
                        <p class="text-sm text-gray-500">
                            Compares harvest quantities by species from the last two completed cycles for this pond.
                        </p>
                    </div>

                    <div id="adminPondHarvestComparisonEmpty"
                         class="hidden rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-5 text-sm text-gray-500">
                        Not enough completed harvest cycles to compare yet.
                    </div>

                    <div id="adminPondHarvestComparisonContent" class="hidden">
                        <div class="mb-4 flex flex-col gap-1 text-sm text-gray-500 md:flex-row md:items-center md:justify-between">
                            <span id="adminPondHarvestPreviousCycle"></span>
                            <span id="adminPondHarvestLatestCycle"></span>
                        </div>

                        <div class="relative h-80">
                            <canvas id="adminHarvestComparisonChart"></canvas>
                        </div>

                        <div id="adminPondHarvestComparisonNotes"
                             class="hidden mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        </div>
                    </div>
                </div>
            </div>

            <div id="adminPondHarvestSection" class="hidden border-t pt-4">
                <div class="mb-4">
                    <h4 class="font-semibold text-gray-800">Harvest Details</h4>
                    <p class="text-sm text-gray-500">Harvest information below is scoped to the selected pond only.</p>
                </div>

                <div id="adminPondHarvestEmpty"
                     class="hidden rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-5 text-sm text-gray-500">
                    No harvest details are available for this pond yet.
                </div>

                <div id="adminPondHarvestContent" class="hidden space-y-4">
                    <div id="adminPondHarvestSummary" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4"></div>

                    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                        <div id="adminPondActiveHarvestPanel" class="hidden rounded-xl border border-gray-200 p-4"></div>
                        <div id="adminPondLatestHarvestPanel" class="hidden rounded-xl border border-gray-200 p-4"></div>
                    </div>

                    <div id="adminPondHistoryPanel" class="hidden rounded-xl border border-gray-200 p-4"></div>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t text-right">
            <button id="closeModalBtn"
                class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Add User Modal Overlay -->
<div id="addUserOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-40"></div>

<!-- Add User Modal -->
<div id="addUserModal" class="fixed inset-0 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-xl">

        <!-- Header -->
        <div class="flex justify-between items-center px-6 py-4 border-b">
            <h3 class="text-lg font-semibold">Add New User</h3>
            <button id="closeAddUserModal" class="text-gray-500 hover:text-red-500 text-xl">&times;</button>
        </div>

        <!-- Body -->
        <form method="POST" action="{{ route('admin.users.create') }}">
            @csrf
            <div class="px-6 py-4 space-y-4">

                <div>
                    <label class="block font-medium">Name</label>
                    <input type="text" name="name" class="w-full border rounded px-3 py-2" required>
                </div>

                <div>
                    <label class="block font-medium">Email</label>
                    <input type="email" name="email" class="w-full border rounded px-3 py-2" required>
                </div>

                <div>
                    <label class="block font-medium">Password</label>
                    <input type="password" name="password" class="w-full border rounded px-3 py-2" required>
                </div>

            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t text-right">
                <button type="button" id="closeAddUserModalBtn"
                    class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded">
                    Cancel
                </button>

                <button type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Create User
                </button>
            </div>
        </form>
    </div>
</div>

    <script>
        const adminPondTelemetryUrlTemplate = @json($pondTelemetryUrlTemplate);

        let currentUserId = null;
        let currentUserPonds = {};
        let adminPondCharts = {
            ph: null,
            temp: null,
            ammonia: null,
        };
        let adminHarvestComparisonChart = null;

        function resetAdminPondTelemetry() {
            $('#adminPondTelemetrySection').addClass('hidden');
            $('#adminPondNoTelemetry').addClass('hidden').text('No telemetry data found for this pond.');
            $('#adminPondCharts').removeClass('hidden');
            $('#adminSelectedPondTitle').text('');
            resetAdminHarvestComparison();
            resetAdminPondHarvest();
        }

        function resetAdminPondHarvest() {
            $('#adminPondHarvestSection').addClass('hidden');
            $('#adminPondHarvestEmpty').addClass('hidden').text('No harvest details are available for this pond yet.');
            $('#adminPondHarvestContent').addClass('hidden');
            $('#adminPondHarvestSummary').empty();
            $('#adminPondActiveHarvestPanel').addClass('hidden').empty();
            $('#adminPondLatestHarvestPanel').addClass('hidden').empty();
            $('#adminPondHistoryPanel').addClass('hidden').empty();
        }

        function resetAdminHarvestComparison() {
            $('#adminPondHarvestComparisonSection').addClass('hidden');
            $('#adminPondHarvestComparisonEmpty')
                .addClass('hidden')
                .text('Not enough completed harvest cycles to compare yet.');
            $('#adminPondHarvestComparisonContent').addClass('hidden');
            $('#adminPondHarvestPreviousCycle').text('');
            $('#adminPondHarvestLatestCycle').text('');
            $('#adminPondHarvestComparisonNotes').addClass('hidden').empty();

            if (adminHarvestComparisonChart) {
                adminHarvestComparisonChart.destroy();
                adminHarvestComparisonChart = null;
            }
        }

        function buildAdminLineChart(canvasId, label, borderColor, backgroundColor) {
            return new Chart(document.getElementById(canvasId).getContext('2d'), {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: label,
                        data: [],
                        borderColor: borderColor,
                        backgroundColor: backgroundColor,
                        tension: 0.3,
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                        },
                    },
                },
            });
        }

        function ensureAdminPondCharts() {
            if (adminPondCharts.ph) {
                return;
            }

            adminPondCharts.ph = buildAdminLineChart('adminPhChart', 'pH Level', 'blue', 'rgba(0,0,255,0.1)');
            adminPondCharts.temp = buildAdminLineChart('adminTempChart', 'Water Temperature (°C)', 'orange', 'rgba(255,165,0,0.1)');
            adminPondCharts.ammonia = buildAdminLineChart('adminAmmoniaChart', 'Ammonia (ppm)', 'red', 'rgba(255,0,0,0.1)');
        }

        function updateAdminPondChart(chart, labels, data) {
            chart.data.labels = labels;
            chart.data.datasets[0].data = data;
            chart.update();
        }

        function buildAdminHarvestComparisonChart(comparison) {
            const canvas = document.getElementById('adminHarvestComparisonChart');

            if (!canvas || !comparison || !comparison.hasComparison) {
                return;
            }

            if (adminHarvestComparisonChart) {
                adminHarvestComparisonChart.destroy();
            }

            const previousCycleNumber = comparison.previousCycle?.cycleNumber ?? '';
            const latestCycleNumber = comparison.latestCycle?.cycleNumber ?? '';
            const previousLabel = previousCycleNumber
                ? `Previous Cycle Harvest (Cycle #${previousCycleNumber})`
                : 'Previous Cycle Harvest';
            const latestLabel = latestCycleNumber
                ? `Latest Cycle Harvest (Cycle #${latestCycleNumber})`
                : 'Latest Cycle Harvest';

            adminHarvestComparisonChart = new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: comparison.labels,
                    datasets: [
                        {
                            label: previousLabel,
                            data: comparison.previousData,
                            backgroundColor: 'rgba(37, 99, 235, 0.72)',
                            borderColor: 'rgb(37, 99, 235)',
                            borderWidth: 1,
                        },
                        {
                            label: latestLabel,
                            data: comparison.latestData,
                            backgroundColor: 'rgba(22, 163, 74, 0.72)',
                            borderColor: 'rgb(22, 163, 74)',
                            borderWidth: 1,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = Number.parseFloat(context.parsed.y ?? 0);
                                    return `${context.dataset.label}: ${value.toFixed(2)} kg`;
                                },
                            },
                        },
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Species Name',
                            },
                            ticks: {
                                autoSkip: false,
                                maxRotation: 30,
                            },
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Harvest Quantity (kg)',
                            },
                        },
                    },
                },
            });
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function formatKg(value, fallback = 'N/A') {
            if (value === null || value === undefined || value === '') {
                return fallback;
            }

            return `${Number(value).toFixed(2)} kg`;
        }

        function formatVariance(value) {
            if (value === null || value === undefined || value === '') {
                return 'Pending';
            }

            const numericValue = Number(value);
            return `${numericValue >= 0 ? '+' : ''}${numericValue.toFixed(2)} kg`;
        }

        function renderHarvestSummaryCard(label, value, helper) {
            return `
                <div class="rounded-xl border border-gray-200 bg-white p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500">${escapeHtml(label)}</p>
                    <p class="mt-3 text-xl font-semibold text-gray-900">${escapeHtml(value)}</p>
                    <p class="mt-1 text-sm text-gray-500">${escapeHtml(helper)}</p>
                </div>
            `;
        }

        function renderSpeciesHarvestTable(speciesBreakdown, emptyMessage) {
            if (!speciesBreakdown || speciesBreakdown.length === 0) {
                return `<p class="text-sm text-gray-500">${escapeHtml(emptyMessage)}</p>`;
            }

            const rows = speciesBreakdown.map((item) => `
                <tr class="border-t border-gray-100">
                    <td class="px-3 py-2 text-sm font-medium text-gray-700">${escapeHtml(item.species)}</td>
                    <td class="px-3 py-2 text-sm text-gray-600">${escapeHtml(formatKg(item.expectedHarvestKg))}</td>
                    <td class="px-3 py-2 text-sm text-gray-600">${escapeHtml(formatKg(item.harvestKg, 'Pending'))}</td>
                    <td class="px-3 py-2 text-sm text-gray-600">${escapeHtml(formatVariance(item.varianceKg))}</td>
                </tr>
            `).join('');

            return `
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full rounded-lg border border-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Species</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Expected</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Harvested</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Variance</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rows}
                        </tbody>
                    </table>
                </div>
            `;
        }

        function renderHarvestPanel(title, snapshot, emptyMessage) {
            if (!snapshot) {
                return `
                    <p class="text-sm font-semibold text-gray-700">${escapeHtml(title)}</p>
                    <p class="mt-3 text-sm text-gray-500">${escapeHtml(emptyMessage)}</p>
                `;
            }

            return `
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500">${escapeHtml(title)}</p>
                        <h5 class="mt-2 text-lg font-semibold text-gray-900">Cycle #${escapeHtml(snapshot.cycleNumber)}</h5>
                    </div>
                    <span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-semibold text-gray-700">
                        ${escapeHtml(snapshot.harvestStatus)}
                    </span>
                </div>
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Harvest Date</p>
                        <p class="mt-2 text-sm font-semibold text-gray-900">${escapeHtml(snapshot.harvestDate ?? 'Not set')}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Completed</p>
                        <p class="mt-2 text-sm font-semibold text-gray-900">${escapeHtml(snapshot.completedAt ?? 'Not completed')}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Expected Total</p>
                        <p class="mt-2 text-sm font-semibold text-gray-900">${escapeHtml(formatKg(snapshot.expectedTotalKg))}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Actual Total</p>
                        <p class="mt-2 text-sm font-semibold text-gray-900">${escapeHtml(formatKg(snapshot.actualTotalKg, 'Pending'))}</p>
                    </div>
                </div>
                ${renderSpeciesHarvestTable(snapshot.speciesBreakdown, 'No species quantities recorded for this cycle yet.')}
            `;
        }

        function renderHarvestHistory(history) {
            if (!history || history.length === 0) {
                return `
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500">Completed Harvest History</p>
                    <p class="mt-3 text-sm text-gray-500">No completed harvest history is available for this pond yet.</p>
                `;
            }

            const rows = history.map((item) => `
                <tr class="border-t border-gray-100">
                    <td class="px-3 py-2 text-sm font-medium text-gray-700">Cycle #${escapeHtml(item.cycleNumber)}</td>
                    <td class="px-3 py-2 text-sm text-gray-600">${escapeHtml(item.harvestDate ?? 'Not set')}</td>
                    <td class="px-3 py-2 text-sm text-gray-600">${escapeHtml(item.completedAt ?? 'Not completed')}</td>
                    <td class="px-3 py-2 text-sm text-gray-600">${escapeHtml(formatKg(item.actualTotalKg))}</td>
                    <td class="px-3 py-2 text-sm text-gray-600">${escapeHtml(formatVariance(item.varianceKg))}</td>
                </tr>
            `).join('');

            return `
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500">Completed Harvest History</p>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full rounded-lg border border-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Cycle</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Harvest Date</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Completed</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Harvested</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Variance</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rows}
                        </tbody>
                    </table>
                </div>
            `;
        }

        function showAdminHarvestComparison(pond, comparison) {
            $('#adminPondHarvestComparisonSection').removeClass('hidden');

            if (!comparison || !comparison.hasComparison) {
                if (adminHarvestComparisonChart) {
                    adminHarvestComparisonChart.destroy();
                    adminHarvestComparisonChart = null;
                }

                $('#adminPondHarvestComparisonContent').addClass('hidden');
                $('#adminPondHarvestComparisonEmpty')
                    .removeClass('hidden')
                    .text(comparison?.message ?? 'Not enough completed harvest cycles to compare yet.');
                return;
            }

            $('#adminPondHarvestComparisonEmpty').addClass('hidden');
            $('#adminPondHarvestComparisonContent').removeClass('hidden');
            $('#adminPondHarvestPreviousCycle').text(`Previous cycle: Cycle #${comparison.previousCycle.cycleNumber}`);
            $('#adminPondHarvestLatestCycle').text(`Latest cycle: Cycle #${comparison.latestCycle.cycleNumber}`);

            buildAdminHarvestComparisonChart(comparison);

            if (comparison.notes && comparison.notes.length > 0) {
                const notes = comparison.notes.map((note) => `<li>${escapeHtml(note)}</li>`).join('');
                $('#adminPondHarvestComparisonNotes')
                    .removeClass('hidden')
                    .html(`
                        <p class="font-semibold">Species notes</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">${notes}</ul>
                    `);
            } else {
                $('#adminPondHarvestComparisonNotes').addClass('hidden').empty();
            }
        }

        function showAdminPondHarvest(pond, harvest) {
            $('#adminPondHarvestSection').removeClass('hidden');

            if (!harvest || !harvest.hasData) {
                $('#adminPondHarvestContent').addClass('hidden');
                $('#adminPondHarvestEmpty')
                    .removeClass('hidden')
                    .text(`No harvest details are available for Pond #${pond.id} yet.`);
                return;
            }

            $('#adminPondHarvestEmpty').addClass('hidden');
            $('#adminPondHarvestContent').removeClass('hidden');

            const summary = harvest.summary ?? {};
            const summaryCards = [
                renderHarvestSummaryCard(
                    'Active Cycle',
                    harvest.activeCycle ? `Cycle #${harvest.activeCycle.cycleNumber}` : 'No active cycle',
                    harvest.activeCycle ? (harvest.activeCycle.harvestStatus ?? 'Monitoring') : 'Waiting for the next cycle'
                ),
                renderHarvestSummaryCard(
                    'Latest Harvest',
                    harvest.latestHarvest ? formatKg(harvest.latestHarvest.actualTotalKg, 'Pending') : 'No recorded harvest',
                    harvest.latestHarvest ? `Cycle #${harvest.latestHarvest.cycleNumber}` : 'No completed harvest yet'
                ),
                renderHarvestSummaryCard(
                    'Completed Cycles',
                    String(summary.completedCycles ?? 0),
                    'Harvested cycles for this pond'
                ),
                renderHarvestSummaryCard(
                    'Total Harvested',
                    formatKg(summary.totalHarvestedKg, '0.00 kg'),
                    summary.latestCompletedAt ? `Latest completion ${summary.latestCompletedAt}` : 'No completed harvest yet'
                ),
            ];

            $('#adminPondHarvestSummary').html(summaryCards.join(''));

            $('#adminPondActiveHarvestPanel')
                .removeClass('hidden')
                .html(renderHarvestPanel(
                    'Active Cycle Harvest Plan',
                    harvest.activeCycle,
                    'This pond does not have an active cycle right now.'
                ));

            $('#adminPondLatestHarvestPanel')
                .removeClass('hidden')
                .html(renderHarvestPanel(
                    'Latest Harvest Record',
                    harvest.latestHarvest,
                    'No completed harvest record is available for this pond yet.'
                ));

            $('#adminPondHistoryPanel')
                .removeClass('hidden')
                .html(renderHarvestHistory(harvest.recentHistory));
        }

        async function fetchAdminPondDetails(pond) {
            const pondId = String(pond.id);
            const url = adminPondTelemetryUrlTemplate
                .replace('__USER__', currentUserId)
                .replace('__POND__', pondId);

            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Unable to load pond telemetry.');
            }

            return response.json();
        }

        function showAdminPondTelemetry(pond, series) {
            const chartSeries = {
                labels: series.labels && series.labels.length ? series.labels : ['No readings yet'],
                phData: series.phData && series.phData.length ? series.phData : [0],
                tempData: series.tempData && series.tempData.length ? series.tempData : [0],
                ammoniaData: series.ammoniaData && series.ammoniaData.length ? series.ammoniaData : [0],
                hasTelemetry: Boolean(series.hasTelemetry),
            };

            $('#adminSelectedPondTitle').text(`Pond #${pond.id} Water Quality Trends`);
            $('#adminPondTelemetrySection').removeClass('hidden');

            if (chartSeries.hasTelemetry) {
                $('#adminPondNoTelemetry').addClass('hidden');
            } else {
                $('#adminPondNoTelemetry')
                    .removeClass('hidden')
                    .text('No telemetry data yet. Showing default zero values until readings are received.');
            }

            $('#adminPondCharts').removeClass('hidden');

            ensureAdminPondCharts();
            updateAdminPondChart(adminPondCharts.ph, chartSeries.labels, chartSeries.phData);
            updateAdminPondChart(adminPondCharts.temp, chartSeries.labels, chartSeries.tempData);
            updateAdminPondChart(adminPondCharts.ammonia, chartSeries.labels, chartSeries.ammoniaData);
        }

        $(document).on('click', '.view-user', function () {

            const user = $(this).data('user');
            currentUserId = user.id;
            currentUserPonds = {};
            resetAdminPondTelemetry();

            // Fill user info
            $('#modalUserName').text(user.name);
            $('#modalUserEmail').text(user.email);
            $('#modalUserCreated').text(user.created_at);

            // Fill ponds
            const pondsList = $('#modalUserPonds');
            pondsList.empty();

            if (user.ponds && user.ponds.length > 0) {
                user.ponds.forEach(pond => {
                    currentUserPonds[String(pond.id)] = pond;
                    pondsList.append(`
                        <button type="button"
                            class="view-pond-telemetry w-full flex items-center justify-between gap-3 rounded border border-blue-100 bg-blue-50 px-4 py-2 text-left text-sm text-blue-700 hover:bg-blue-100 transition"
                            data-pond-id="${pond.id}">
                            <span>Pond #${pond.id} — ${pond.hectares} ha</span>
                            <span class="font-semibold">View Pond</span>
                        </button>
                    `);
                });
            } else {
                pondsList.append(`
                    <p class="text-gray-500">No ponds assigned.</p>
                `);
            }

            // Show modal
            $('#userModal, #userModalOverlay').removeClass('hidden');
        });

        $(document).on('click', '.view-pond-telemetry', async function () {
            const pondId = String($(this).data('pond-id'));
            const pond = currentUserPonds[pondId];

            if (!pond) {
                return;
            }

            $('.view-pond-telemetry')
                .removeClass('bg-blue-600 text-white')
                .addClass('bg-blue-50 text-blue-700');

            $(this)
                .removeClass('bg-blue-50 text-blue-700')
                .addClass('bg-blue-600 text-white');

            $('#adminSelectedPondTitle').text(`Pond #${pond.id} Water Quality Trends`);
            $('#adminPondTelemetrySection').removeClass('hidden');
            $('#adminPondCharts').addClass('hidden');
            $('#adminPondNoTelemetry').removeClass('hidden').text('Loading telemetry...');
            resetAdminHarvestComparison();
            $('#adminPondHarvestSection').removeClass('hidden');
            $('#adminPondHarvestContent').addClass('hidden');
            $('#adminPondHarvestEmpty').removeClass('hidden').text('Loading harvest details...');
            $('#adminPondHarvestComparisonSection').removeClass('hidden');
            $('#adminPondHarvestComparisonContent').addClass('hidden');
            $('#adminPondHarvestComparisonEmpty').removeClass('hidden').text('Loading harvest comparison...');

            try {
                const pondDetails = await fetchAdminPondDetails(pond);
                showAdminPondTelemetry(pond, pondDetails);
                showAdminHarvestComparison(pond, pondDetails.harvest?.comparison);
                showAdminPondHarvest(pond, pondDetails.harvest);
            } catch (error) {
                $('#adminPondCharts').addClass('hidden');
                $('#adminPondNoTelemetry').removeClass('hidden').text('Unable to load telemetry for this pond.');
                $('#adminPondHarvestComparisonSection').removeClass('hidden');
                $('#adminPondHarvestComparisonContent').addClass('hidden');
                $('#adminPondHarvestComparisonEmpty')
                    .removeClass('hidden')
                    .text('Unable to load harvest comparison for this pond.');
                $('#adminPondHarvestSection').removeClass('hidden');
                $('#adminPondHarvestContent').addClass('hidden');
                $('#adminPondHarvestEmpty')
                    .removeClass('hidden')
                    .text('Unable to load harvest details for this pond.');
            }
        });

        // Close modal
        $('#closeModal, #closeModalBtn, #userModalOverlay').on('click', function () {
            $('#userModal, #userModalOverlay').addClass('hidden');
            resetAdminPondTelemetry();
        });
        $('#openAddUserModal').on('click', function() {
            $('#addUserModal, #addUserOverlay').removeClass('hidden');
        });

        // Close Modal
        $('#closeAddUserModal, #closeAddUserModalBtn, #addUserOverlay').on('click', function() {
            $('#addUserModal, #addUserOverlay').addClass('hidden');
        });
    </script>


    <script>
        $(document).ready(function () {
            $('#myTable').DataTable({
                order: [[0, 'desc']]  
            });
        });

    </script>

</body>

</html>
