<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ERP System')</title>

    {{-- Favicon --}}
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Tambahan CSS opsional --}}
   
</head>

<body class="min-h-screen flex items-center justify-center bg-gray-100">

    {{-- Wrapper halaman --}}
    <main class="w-full flex flex-col items-center justify-center px-4">
        @yield('content')
    </main>

    {{-- Script tambahan per halaman (optional) --}}
    @stack('scripts')

</body>
</html>
