<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terjadi Kesalahan - PT Aghitsna Karya Indah</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="min-h-screen bg-surface-secondary text-text-primary text-base">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="w-full max-w-lg bg-surface-base rounded-xl shadow-lg p-8 text-center">
            {{-- Illustration 500 --}}
            <div class="mb-6 flex items-center justify-center">
                <img src="{{ asset('svg/500.svg') }}" alt="500" class="w-72 max-w-full h-auto" />
            </div>

            <h1 class="text-3xl font-bold text-text-primary mb-3">Terjadi Kesalahan</h1>

            <p class="text-text-secondary mb-6">
                Maaf, terjadi kesalahan pada sistem. Tim kami telah diberitahu dan akan segera memperbaikinya.
            </p>

            {{-- Error Code --}}
            <div class="bg-error-light border border-error rounded-lg p-4 mb-6">
                <p class="text-sm text-error">
                    <span class="font-semibold">Kode Error:</span> 500 - Internal Server Error
                </p>

                @php
                    $debug = (bool) config('app.debug');
                    $message =
                        isset($exception) && method_exists($exception, 'getMessage') ? $exception->getMessage() : null;
                @endphp

                @if ($debug)
                    <p class="text-xs text-error mt-2">
                        {{ $message ?? 'Unknown error' }}
                    </p>
                @endif
            </div>

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <button onclick="window.location.reload()"
                    class="flex items-center justify-center gap-2 bg-btn-add hover:bg-btn-add-hover text-white px-6 py-3 rounded-lg transition-colors duration-200 font-medium">
                    <i class="fa-solid fa-rotate-right"></i>
                    Coba Lagi
                </button>

                <a href="{{ route('dashboard') }}"
                    class="flex items-center justify-center gap-2 bg-surface-secondary hover:bg-surface-hover text-text-primary px-6 py-3 rounded-lg transition-colors duration-200 font-medium">
                    <i class="fa-solid fa-home"></i>
                    Kembali ke Dashboard
                </a>
            </div>

            <div class="mt-8 pt-6 border-t border-border-light">
                <p class="text-xs text-text-label">
                    Jika masalah terus berlanjut, hubungi administrator sistem
                </p>
            </div>
        </div>
    </div>
</body>

</html>
