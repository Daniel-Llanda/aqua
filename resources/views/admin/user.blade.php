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
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/2.9.2/semantic.min.js"></script>
    <script src="https://cdn.datatables.net/2.3.6/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.3.6/js/dataTables.semanticui.js"></script>

    <!-- Sidebar -->
    <aside class="w-64 bg-blue-600 text-white flex flex-col">
        <div class="px-6 py-6 border-b border-blue-500">
            <h1 class="text-2xl font-bold">Admin Panel</h1>
        </div>

        <nav class="sidebar-nav">
            <a href="{{ route('admin.dashboard') }}" class="nav-link">Dashboard</a>
            <a href="{{ route('admin.users') }}" class="nav-link active">Users</a>
            <a href="{{ route('admin.telemetry') }}" class="nav-link">Telemetry</a>
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
            <div class="bg-white p-6 rounded-2xl shadow-md">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-semibold text-gray-700">User List</h3>
                   
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
                                    <td class="px-6 py-3 text-sm text-gray-700">{{ $user->created_at->format('Y-m-d') }}</td>
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

    <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl">

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
                <ul id="modalUserPonds" class="list-disc pl-5 space-y-1"></ul>
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
    <script>
        $(document).on('click', '.view-user', function () {

            const user = $(this).data('user');

            // Fill user info
            $('#modalUserName').text(user.name);
            $('#modalUserEmail').text(user.email);
            $('#modalUserCreated').text(user.created_at);

            // Fill ponds
            const pondsList = $('#modalUserPonds');
            pondsList.empty();

            if (user.ponds && user.ponds.length > 0) {
                user.ponds.forEach(pond => {
                    pondsList.append(`
                        <li>
                            Pond #${pond.id} — ${pond.hectares} ha
                        </li>
                    `);
                });
            } else {
                pondsList.append(`
                    <li class="text-gray-500">No ponds assigned.</li>
                `);
            }

            // Show modal
            $('#userModal, #userModalOverlay').removeClass('hidden');
        });

        // Close modal
        $('#closeModal, #closeModalBtn, #userModalOverlay').on('click', function () {
            $('#userModal, #userModalOverlay').addClass('hidden');
        });
    </script>


     <script>
        $(document).ready(function () {
            $('#myTable').DataTable();
        });
    </script>

</body>

</html>
