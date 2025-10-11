<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | ERP System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-100">

    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8">
        <div class="flex flex-col items-center mb-6">
            <div class="w-14 h-14 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold text-2xl">
                ERP
            </div>
            <h1 class="text-2xl font-semibold mt-4 text-gray-700">Masuk ke Akun</h1>
            <p class="text-gray-500 text-sm mt-1">Silakan login untuk melanjutkan</p>
        </div>

        <form action="{{ route('login.post') }}" method="POST" class="space-y-5">
            @csrf
            <div>
                <label class="block text-gray-600 text-sm mb-1">Email</label>
                <input type="email" name="email" required value="{{ old('email') }}"
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('email')
                    <p class="text-red-500 text-base mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-gray-600 text-sm mb-1">Kata Sandi</label>
                <input type="password" name="password" required
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('password')
                    <p class="text-red-500 text-base mt-1">{{ $message }}</p>
                @enderror
            </div>

             <div class="flex items-center justify-between text-sm">
                <label class="flex items-center gap-2">
                    <input type="checkbox" class="rounded border-gray-300 bg-primary focus:ring-[var(--color-primary)]">
                    Ingat saya
                </label>
                <a href="#" class="bg-primary hover:underline">Lupa kata sandi?</a>
            </div>

            <button type="submit"
                    class="w-full bg-blue-600 text-white font-medium py-2 rounded-lg hover:bg-blue-700 transition-all">
                Masuk
            </button>
        </form>
    </div>

</body>
</html>
