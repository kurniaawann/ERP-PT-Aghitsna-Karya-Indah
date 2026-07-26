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

   <link rel="icon" type="image/png" href="/images/logo.jpeg">
</head>

<body class="min-h-screen bg-surface-secondary text-text-primary text-base">

    <div class="flex h-screen">
        {{-- Sidebar dipisah sebagai partial --}}
        @include('layouts.sidebar')

        {{-- Main Content --}}
        <div class="flex-1 flex flex-col overflow-hidden">
            {{-- Topbar --}}
            <header class="bg-surface-base shadow-sm z-10 border-b border-border-light py-1">
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

                    {{-- User Info + Dropdown --}}
                    <div class="relative" x-data="{ open: false }" @click.away="open = false">
                        <button @click="open = !open"
                            class="flex items-center space-x-2 text-text-primary hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary rounded-lg px-3 py-2 transition-colors duration-200">
                            <i class="fas fa-user-circle text-lg"></i>
                            <span class="hidden md:inline text-sm font-medium">{{ auth()->user()->name ?? 'Guest' }}</span>
                            <i class="fas fa-chevron-down text-xs transition-transform duration-200"
                                :class="open ? 'rotate-180' : ''"></i>
                        </button>

                        {{-- Dropdown Menu --}}
                        <div x-show="open" x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 mt-2 w-56 bg-surface-base rounded-xl shadow-[0_10px_40px_rgba(0,0,0,0.12)] border border-border-light py-2 z-50"
                            style="display: none;">

                            <div class="px-4 py-2 border-b border-border-light">
                                <p class="text-sm font-semibold text-text-heading">{{ auth()->user()->name ?? 'Guest' }}</p>
                                <p class="text-xs text-text-secondary">{{ auth()->user()->email ?? '' }}</p>
                            </div>

                            <a href="{{ route('profile.change-password') }}"
                                class="flex items-center px-4 py-2.5 text-sm text-text-primary hover:bg-primary-light hover:text-primary transition-colors duration-150">
                                <i class="fas fa-lock w-5 text-text-tertiary"></i>
                                <span class="ml-2">Ubah Password</span>
                            </a>

                            <div class="border-t border-border-light mt-1 pt-1">
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="flex items-center w-full px-4 py-2.5 text-sm text-text-primary hover:bg-red-50 hover:text-error transition-colors duration-150">
                                        <i class="fas fa-sign-out-alt w-5 text-text-tertiary"></i>
                                        <span class="ml-2">Logout</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Konten Utama --}}
            <main class="flex-1 overflow-y-auto p-6 bg-surface-secondary flex flex-col">
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
                // Init searchable selects inside the modal
                if (typeof initSearchableSelects === 'function') {
                    initSearchableSelects(modal);
                }
                // Init searchable multi-selects inside the modal
                if (typeof initSearchableMultiSelects === 'function') {
                    initSearchableMultiSelects(modal);
                }
            }
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }
        }

        // Password show/hide toggle
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('[data-password-toggle]');
            if (!btn) return;

            const targetId = btn.getAttribute('data-target-id');
            if (!targetId) return;

            const input = document.getElementById(targetId);
            if (!input) return;

            const isPassword = input.getAttribute('type') === 'password';
            input.setAttribute('type', isPassword ? 'text' : 'password');

            const svg = btn.querySelector('[data-password-toggle-icon]');
            if (svg) {
                const eyeOpen = svg.querySelector('#eye-open');
                const eyeOpenCircle = svg.querySelector('#eye-open-circle');
                const eyeClosed = svg.querySelector('#eye-closed');
                if (eyeOpen && eyeClosed) {
                    if (isPassword) {
                        eyeOpen.style.display = 'none';
                        if (eyeOpenCircle) eyeOpenCircle.style.display = 'none';
                        eyeClosed.style.display = '';
                    } else {
                        eyeOpen.style.display = '';
                        if (eyeOpenCircle) eyeOpenCircle.style.display = '';
                        eyeClosed.style.display = 'none';
                    }
                }
            }
        });
    </script>

    {{-- Alpine.js --}}
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    @stack('scripts')

</body>

</html>
