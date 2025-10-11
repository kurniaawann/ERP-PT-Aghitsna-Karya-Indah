<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ERP System')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

    {{-- Navbar sederhana --}}
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="font-semibold text-lg">ERP System</h1>

            <div class="flex items-center gap-4">
                <span>{{ auth()->user()->name ?? 'Guest' }}</span>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-white text-blue-600 px-3 py-1 rounded hover:bg-blue-100 transition">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </nav>

    {{-- Konten halaman --}}
    <main class="flex-1 container mx-auto p-6">
        @yield('content')
    </main>

</body>
</html>
