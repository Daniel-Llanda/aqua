<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telemetry - Admin Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 font-sans antialiased flex min-h-screen">

    <!-- Sidebar -->
    <aside class="w-64 bg-blue-600 text-white flex flex-col">
        <div class="px-6 py-6 border-b border-blue-500">
            <h1 class="text-2xl font-bold">Admin Panel</h1>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-3">
            <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 rounded-lg hover:bg-blue-500 transition">Dashboard</a>
            <a href="{{ route('admin.users') }}" class="block px-4 py-2 rounded-lg hover:bg-blue-500 transition">Users</a>
            <a href="{{ route('admin.telemetry') }}" class="block px-4 py-2 rounded-lg bg-blue-700 hover:bg-blue-500 transition">Telemetry</a>
            <a href="#" class="block px-4 py-2 rounded-lg hover:bg-blue-500 transition">Settings</a>
            <a href="#" class="block px-4 py-2 rounded-lg hover:bg-blue-500 transition">Reports</a>
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
    <main class="flex-1 flex flex-col">
        <!-- Top Bar -->
        <header class="bg-white shadow-sm px-8 py-4 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">Telemetry</h2>
            <div class="text-gray-600">
                Welcome, <span class="font-semibold text-blue-600">Admin</span>
            </div>
        </header>

        <!-- Content -->
        <section class="p-8 flex-1">
            <!-- Latest Sensor Readings Cards -->
            @if($latestPayload)
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Latest Sensor Readings</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        @foreach($latestPayload->payload as $key => $value)
                            <div class="bg-white p-6 rounded-2xl shadow-md hover:shadow-lg transition transform hover:-translate-y-1">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">{{ ucwords(str_replace('_', ' ', $key)) }}</h4>
                                </div>
                                <p class="text-3xl font-bold text-blue-600 mt-3">
                                    @if(is_numeric($value))
                                        {{ number_format($value, 2) }}
                                    @else
                                        {{ $value }}
                                    @endif
                                </p>
                                <p class="text-gray-400 text-xs mt-2">Last updated: {{ $latestPayload->created_at?->diffForHumans() ?? 'N/A' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="mb-8 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <p class="text-yellow-700">No sensor data available yet.</p>
                </div>
            @endif

            <!-- Payload History Table -->
            <div class="bg-white p-6 rounded-2xl shadow-md">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-semibold text-gray-700">Payload History</h3>
                    <span class="text-sm text-gray-500">{{ $payloads->count() }} records</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-200 rounded-lg">
                        <thead class="bg-blue-600 text-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-semibold">#</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold">Payload Data</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold">Received At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($payloads as $payload)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-3 text-sm text-gray-700">{{ $payload->id }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-700">
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($payload->payload as $key => $value)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ ucwords(str_replace('_', ' ', $key)) }}:
                                                    @if(is_numeric($value))
                                                        {{ number_format($value, 2) }}
                                                    @else
                                                        {{ $value }}
                                                    @endif
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 text-sm text-gray-700">{{ $payload->created_at?->format('M d, Y H:i:s') ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-8 text-center text-gray-500">
                                        No payload data available.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

</body>

</html>
