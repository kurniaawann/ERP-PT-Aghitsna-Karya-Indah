<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak - PT Aghitsna Karya Indah</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="min-h-screen bg-surface-secondary text-text-primary text-base">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="w-full max-w-lg bg-surface-base rounded-xl shadow-lg p-8 text-center">
            {{-- Illustration 403 --}}
            <div class="mb-6 flex items-center justify-center">
                <img src="{{ asset('svg/401and403.svg') }}" alt="403" class="w-72 max-w-full h-auto" />
            </div>

            <h1 class="text-6xl font-bold text-warning mb-3">403</h1>
            <h2 class="text-2xl font-semibold text-text-primary mb-3">Akses Ditolak</h2>

            <p class="text-text-secondary mb-6">
                Maaf, Anda tidak memiliki izin untuk mengakses halaman ini.
            </p>

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <button onclick="window.history.back()"
                    class="flex items-center justify-center gap-2 bg-surface-secondary hover:bg-surface-hover text-text-primary px-6 py-3 rounded-lg transition-colors duration-200 font-medium">
                    <i class="fa-solid fa-arrow-left"></i>
                    Kembali
                </button>

                <a href="{{ route('dashboard') }}"
                    class="flex items-center justify-center gap-2 bg-btn-add hover:bg-btn-add-hover text-white px-6 py-3 rounded-lg transition-colors duration-200 font-medium">
                    <i class="fa-solid fa-home"></i>
                    Dashboard
                </a>
            </div>
        </div>
    </div>
</body>

</html>
