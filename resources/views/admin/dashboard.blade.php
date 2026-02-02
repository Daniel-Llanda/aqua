<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geospatial Environmental Site Suitability System</title>

    <!-- Tailwind & other assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/2.9.2/semantic.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.6/css/dataTables.semanticui.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

    <style>
        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
        }

        .sidebar-nav {
            flex: 1;
            padding: 1.5rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .nav-link {
            display: block;
            padding: 0.6rem 1rem;
            border-radius: 0.5rem;
            color: #ffffff;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.25s ease, transform 0.15s ease;
        }

        .nav-link:hover {
            background-color: #3b82f6;
            color: #ffffff;
            transform: translateX(4px);
        }

        .nav-link.active {
            background-color: #1d4ed8;
        }

        #map {
            height: 60vh;
            border-radius: 6px;
            border: 1px solid #d1d5db;
        }

        .panel {
            background: #ffffff;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            padding: 20px;

        }

        .panel h2 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 16px;
            color: #111827;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 8px;
        }

        .data-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 14px;
        }

        .data-row span {
            font-weight: 600;
            color: #1f2933;
        }

        .status {
            margin-top: 12px;
            padding: 10px;
            border-radius: 4px;
            font-weight: 600;
            text-align: center;
            background-color: #eef2f7;
        }

        footer {
            text-align: center;
            font-size: 12px;
            padding: 10px;
            color: #6b7280;
        }
    </style>
</head>

<body class="flex min-h-screen bg-gray-100">

    <!-- Sidebar -->
    <aside class="w-64 bg-blue-600 text-white flex flex-col">
        <div class="px-6 py-6 border-b border-blue-500">
            <h1 class="text-2xl font-bold">Admin Panel</h1>
        </div>

        <nav class="sidebar-nav">
            <a href="{{ route('admin.dashboard') }}" class="nav-link active">Dashboard</a>
            <a href="{{ route('admin.users') }}" class="nav-link">Users</a>
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
            <h2 class="text-xl font-semibold text-gray-800">Geospatial Dashboard</h2>
            <div class="text-gray-600">
                Welcome, <span class="font-semibold text-blue-600">Admin</span>
            </div>
        </header>


        <!-- Map and Environmental Panel -->
        <section class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div id="map"></div>

            <div class="panel">
                <h2>Selected Location Information</h2>
                <div class="data-row">Latitude <span id="lat">-</span></div>
                <div class="data-row">Longitude <span id="lon">-</span></div>
                <div class="data-row">Temperature <span id="temp">-</span></div>
                <div class="data-row">Weather Condition <span id="weather">-</span></div>
                <div class="data-row">Rainfall (Last Hour) <span id="rain">-</span></div>
                <div class="data-row">Air Quality Index <span id="air">-</span></div>
                <div class="data-row">Nearby Water Bodies <span id="water">-</span></div>
                <div class="status" id="pond">Pond Suitability: -</div>
            </div>
        </section>

        <footer>Data Sources: OpenStreetMap, OpenWeatherMap | For academic and research use</footer>

    </main>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        const API_KEY = "89b4b3b5b60df96d3e3a90eec2ab9df5";

        const map = L.map('map').setView([14.5995, 120.9842], 6);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        let marker;

        map.on('click', function (e) {
            const lat = e.latlng.lat;
            const lon = e.latlng.lng;

            if (marker) map.removeLayer(marker);
            marker = L.marker([lat, lon]).addTo(map);

            document.getElementById("lat").textContent = lat.toFixed(5);
            document.getElementById("lon").textContent = lon.toFixed(5);

            getEnvironmentalData(lat, lon);
        });

        function getEnvironmentalData(lat, lon) {
            fetch(`https://api.openweathermap.org/data/2.5/weather?lat=${lat}&lon=${lon}&appid=${API_KEY}&units=metric`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById("temp").textContent = data.main.temp + " °C";
                    document.getElementById("weather").textContent = data.weather[0].description;

                    const rain = data.rain ? (data.rain["1h"] || 0) : 0;
                    document.getElementById("rain").textContent = rain + " mm";

                    checkWaterBodies(lat, lon, rain);
                });

            fetch(`https://api.openweathermap.org/data/2.5/air_pollution?lat=${lat}&lon=${lon}&appid=${API_KEY}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById("air").textContent = data.list[0].main.aqi;
                });
        }

        function checkPondSuitability(rain, hasWater) {
            let score = 0;
            if (rain >= 5) score++;
            if (hasWater) score++;

            if (score === 2) return "Highly Suitable";
            if (score === 1) return "Moderately Suitable";
            return "Not Suitable";
        }

        function checkWaterBodies(lat, lon, rain) {
            const radius = 1000;
            const query = `
            [out:json];
            (
              way["natural"="water"](around:${radius},${lat},${lon});
              relation["natural"="water"](around:${radius},${lat},${lon});
            );
            out body;
            `;

            fetch("https://overpass-api.de/api/interpreter", {
                method: "POST",
                body: query
            })
            .then(res => res.json())
            .then(data => {
                const hasWater = data.elements.length > 0;
                document.getElementById("water").textContent = hasWater ? "Yes" : "No";

                const result = checkPondSuitability(rain, hasWater);
                document.getElementById("pond").textContent = "Pond Suitability: " + result;
            });
        }
    </script>

</body>
</html>
