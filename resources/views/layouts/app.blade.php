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
            {{-- Topbar (gaya Filament): tombol menu (mobile), judul halaman, menu pengguna --}}
            @php
                $authUser = auth()->user();
                $userName = $authUser->name ?? 'Guest';
                // Inisial avatar: huruf pertama dari maksimal 2 kata nama
                $userInitials = collect(preg_split('/\s+/', trim($userName)))
                    ->filter()
                    ->take(2)
                    ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
                    ->implode('');
            @endphp
            <header class="relative z-10 flex h-16 shrink-0 items-center gap-x-3 bg-white px-4 shadow-sm ring-1 ring-gray-950/5 md:px-6">
                {{-- Toggle Sidebar untuk Mobile --}}
                <button id="toggleSidebar" type="button" title="Buka menu"
                    class="-ms-1.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-500 outline-none transition duration-75 hover:bg-gray-100 hover:text-gray-700 focus-visible:ring-2 focus-visible:ring-primary/50 lg:hidden">
                    <x-fi.icon name="bars-3" class="h-6 w-6" />
                    <span class="sr-only">Buka menu</span>
                </button>

                {{-- Judul Halaman --}}
                <h1 class="hidden min-w-0 truncate text-base font-semibold text-gray-950 md:block" id="pageTitle">
                    @yield('title', 'Dashboard')
                </h1>

                {{-- Menu Pengguna: avatar inisial + nama & role → Ubah Password, Logout --}}
                <x-fi.dropdown label="Menu pengguna" width="w-64" class="ms-auto shrink-0">
                    <x-slot:trigger class="flex items-center gap-x-3 rounded-lg p-1 hover:bg-gray-50 aria-expanded:bg-gray-50 md:pe-2">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-semibold text-white">
                            {{ $userInitials }}
                        </span>
                        <span class="hidden text-start md:block">
                            <span class="block max-w-[12rem] truncate text-sm font-semibold leading-5 text-gray-950">{{ $userName }}</span>
                            @if ($authUser)
                                <span class="block text-xs leading-4 text-gray-500">{{ $authUser->role_label }}</span>
                            @endif
                        </span>
                        <x-fi.icon name="chevron-down" class="hidden h-4 w-4 text-gray-400 md:block" />
                    </x-slot:trigger>

                    <div class="flex items-center gap-x-3 p-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-semibold text-white">
                            {{ $userInitials }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-950">{{ $userName }}</p>
                            <p class="truncate text-sm text-gray-500">{{ $authUser->email ?? '' }}</p>
                        </div>
                    </div>

                    <x-fi.dropdown.list>
                        <x-fi.dropdown.item icon="lock-closed" :href="route('profile.change-password')">
                            Ubah Password
                        </x-fi.dropdown.item>
                    </x-fi.dropdown.list>

                    <x-fi.dropdown.list>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <x-fi.dropdown.item type="submit" icon="arrow-left-start-on-rectangle">
                                Logout
                            </x-fi.dropdown.item>
                        </form>
                    </x-fi.dropdown.list>
                </x-fi.dropdown>
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
                window.dispatchEvent(new CustomEvent('modalOpened', { detail: id }));
            }
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
                window.dispatchEvent(new CustomEvent('modalClosed', { detail: id }));
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

    {{-- Modal tambahan dari komponen (mis. Penandatangan Laporan) --}}
    @stack('modals')

    @stack('scripts')

</body>

</html>
