<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
            <a href="{{ route('admin.users') }}" class="block px-4 py-2 rounded-lg bg-blue-700 hover:bg-blue-500 transition">Users</a>
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
                   
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-200 rounded-lg">
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
                                    <td class="px-6 py-3 text-sm text-gray-700">{{$user->id}}</td>
                                    <td class="px-6 py-3 text-sm text-gray-700">{{$user->name}}</td>
                                    <td class="px-6 py-3 text-sm text-gray-700">{{$user->email}}</td>
                                    <td class="px-6 py-3 text-sm text-gray-700">{{$user->created_at}}</td>
                                    <td class="px-6 py-3 text-center">
                                        <button class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 transition">Edit</button>
                                        <button class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 transition">Delete</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

</body>

</html>
