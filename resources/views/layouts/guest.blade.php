<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ERP System')</title>

    {{-- Favicon --}}
    <link rel="icon" href="{{ asset('images/logo.jpeg') }}" type="image/jpeg">

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])


</head>

<body class="min-h-screen flex items-center justify-center bg-surface-secondary text-text-primary text-base">

    {{-- Wrapper halaman --}}
    <main class="w-full flex flex-col items-center justify-center px-4 py-8">
        @yield('content')
    </main>

    {{-- Script tambahan per halaman (optional) --}}
    @stack('scripts')

    <script>
        // Password show/hide toggle for pages that use data-password-toggle
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('[data-password-toggle]');
            if (!btn) return;

            const targetId = btn.getAttribute('data-target-id');
            if (!targetId) return;

            const input = document.getElementById(targetId);
            if (!input) return;

            const isPassword = input.getAttribute('type') === 'password';
            input.setAttribute('type', isPassword ? 'text' : 'password');

            // Update icon (svg)
            const svg = btn.querySelector('[data-password-toggle-icon]');
            if (svg) {
                const eyeOpen = svg.querySelector('#eye-open');
                const eyeClosed = svg.querySelector('#eye-closed');
                if (eyeOpen && eyeClosed) {
                    if (isPassword) {
                        eyeOpen.style.display = 'none';
                        eyeClosed.style.display = '';
                    } else {
                        eyeOpen.style.display = '';
                        eyeClosed.style.display = 'none';
                    }
                }
            }
        });
    </script>


</body>

</html>
