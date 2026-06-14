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

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>

<body class="min-h-screen bg-surface-secondary text-text-primary text-base">

    <div class="flex h-screen">
        {{-- Sidebar dipisah sebagai partial --}}
        @include('layouts.sidebar')

        {{-- Main Content --}}
        <div class="flex-1 flex flex-col overflow-hidden">
            {{-- Topbar --}}
            <header class="bg-surface-base shadow-sm z-10 border-b border-border-light">
                <div class="flex items-center justify-between p-4">
                    {{-- Toggle Sidebar untuk Mobile --}}
                    <button id="toggleSidebar" type="button"
                        class="lg:hidden text-text-secondary hover:text-text-heading focus:outline-none focus:ring-2 focus:ring-primary rounded-lg p-2">
                        <i class="fas fa-bars text-xl"></i>
                    </button>

                    {{-- Judul Halaman --}}
                    <h1 class="hidden md:block text-xl font-semibold text-text-heading" id="pageTitle">
                        @yield('title', 'Dashboard')
                    </h1>

                    {{-- User Info + Logout --}}
                    <div class="flex items-center space-x-4">
                        <div class="hidden md:flex items-center space-x-2 text-text-primary">
                            <i class="fas fa-user-circle"></i>
                            <span>{{ auth()->user()->name ?? 'Guest' }}</span>
                        </div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf

                            @include('partials.loading-submit-button', [
                                'id' => 'logoutBtn',
                                'textId' => 'logoutBtnText',
                                'spinnerId' => 'logoutBtnSpinner',
                                'buttonText' => 'Logout',
                                'buttonType' => 'submit',
                                'buttonClass' =>
                                    'bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-hover transition-colors duration-200 flex items-center space-x-2',
                            ])

                            <span class="sr-only">Logout</span>
                        </form>
                    </div>
                </div>
            </header>

            {{-- Konten Utama --}}
            <main class="flex-1 overflow-y-auto p-6 bg-surface-secondary">
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

    @include('partials.shared.export-loading-script')

    @stack('scripts')

</body>

</html>
