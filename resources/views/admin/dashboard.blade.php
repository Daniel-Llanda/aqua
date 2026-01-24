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

<body class="bg-gray-100 font-sans antialiased flex min-h-screen">

    <!-- Sidebar -->
    <aside class="w-64 bg-blue-600 text-white flex flex-col">
        <div class="px-6 py-6 border-b border-blue-500">
            <h1 class="text-2xl font-bold">Admin Panel</h1>
        </div>

        <nav class="sidebar-nav">
            <a href="{{ route('admin.dashboard') }}" class="nav-link active">Dashboard</a>
            <a href="{{ route('admin.users') }}" class="nav-link">Users</a>
            <!-- <a href="{{ route('admin.telemetry') }}" class="nav-link">Telemetry</a> -->
            <!-- <a href="#" class="nav-link">Settings</a>
            <a href="#" class="nav-link">Reports</a> -->
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
            <h2 class="text-xl font-semibold text-gray-800">Dashboard</h2>
            <div class="text-gray-600">
                Welcome, <span class="font-semibold text-blue-600">Admin</span>
            </div>
        </header>

        <!-- Content -->
        <section class="p-8 flex-1">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Card 1 -->
                <div class="bg-white p-6 rounded-2xl shadow-md hover:shadow-lg transition transform hover:-translate-y-1">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-700">Total Users</h3>
                  
                    </div>
                    <p class="text-4xl font-bold text-blue-600 mt-4">120</p>
                    <p class="text-gray-500 text-sm mt-2">+5% from last month</p>
                </div>

                <!-- Card 2 -->
                <div class="bg-white p-6 rounded-2xl shadow-md hover:shadow-lg transition transform hover:-translate-y-1">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-700">Active Devices</h3>
                 
                    </div>
                    <p class="text-4xl font-bold text-blue-600 mt-4">8</p>
                    <p class="text-gray-500 text-sm mt-2">Updated 10 mins ago</p>
                </div>

                <!-- Card 3 -->
                <div class="bg-white p-6 rounded-2xl shadow-md hover:shadow-lg transition transform hover:-translate-y-1">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-700">Alerts</h3>
                      
                    </div>
                    <p class="text-4xl font-bold text-red-500 mt-4">3</p>
                    <p class="text-gray-500 text-sm mt-2">2 new critical alerts</p>
                </div>
            </div>
        </section>
    </main>

</body>

</html>
