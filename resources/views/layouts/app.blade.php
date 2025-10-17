<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ERP System')</title>

    {{-- Styles & JS --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="min-h-screen">

    <div class="flex h-screen">
        {{-- Sidebar dipisah sebagai partial --}}
        @include('layouts.sidebar')

        {{-- Main Content --}}
        <div class="flex-1 flex flex-col overflow-hidden">
            {{-- Topbar --}}
            <header class="bg-white shadow-sm z-10">
                <div class="flex items-center justify-between p-4">
                    {{-- Toggle Sidebar untuk Mobile --}}
                    <button id="toggleSidebar" type="button"
                        class="lg:hidden text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg p-2">
                        <i class="fas fa-bars text-xl"></i>
                    </button>

                    {{-- Judul Halaman --}}
                    <h1 class="hidden md:block text-xl font-semibold text-gray-800" id="pageTitle">
                        @yield('title', 'Dashboard')
                    </h1>

                    {{-- User Info + Logout --}}
                    <div class="flex items-center space-x-4">
                        <div class="hidden md:flex items-center space-x-2 text-gray-700">
                            <i class="fas fa-user-circle"></i>
                            <span>{{ auth()->user()->name ?? 'Guest' }}</span>
                        </div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200 flex items-center space-x-2">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            {{-- Konten Utama --}}
            <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Komponen Toast --}}
    <x-toast />

    {{-- Script Sidebar & Modal --}}
    <script>
        function openModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }
        }
    </script>

    {{-- Alpine.js --}}
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

</body>

</html>
