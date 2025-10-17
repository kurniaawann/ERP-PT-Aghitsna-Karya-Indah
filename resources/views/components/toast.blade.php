<div id="toast"
    class="hidden fixed top-5 right-5 z-50 items-center w-auto max-w-xs p-4 space-x-3 text-gray-700 bg-white border rounded-lg shadow-lg transition-all duration-500">
    <div id="toastIcon" class="text-green-600">
        <!-- Ikon default (berhasil) -->
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
    </div>
    <div id="toastInner" class="flex-1">
        <p id="toastMessage" class="text-sm font-medium"></p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toast = document.getElementById('toast');
        const toastInner = document.getElementById('toastInner');
        const toastMessage = document.getElementById('toastMessage');
        const toastIcon = document.getElementById('toastIcon');
        let toastTimeout = null;

        // Fungsi menampilkan toast
        window.showToast = function(message, type = 'success') {
            clearTimeout(toastTimeout);
            toastMessage.textContent = message;

            // Ubah warna & ikon sesuai tipe
            if (type === 'success') {
                toast.classList.remove('bg-red-100', 'border-red-400');
                toast.classList.add('bg-green-100', 'border-green-400');
                toastIcon.innerHTML =
                    `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>`;
            } else {
                toast.classList.remove('bg-green-100', 'border-green-400');
                toast.classList.add('bg-red-100', 'border-red-400');
                toastIcon.innerHTML =
                    `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>`;
            }

            toast.classList.remove('hidden', 'opacity-0', 'translate-y-2');
            toast.classList.add('flex', 'opacity-100', 'translate-y-0');

            // Hilang otomatis setelah 3 detik
            toastTimeout = setTimeout(() => {
                toast.classList.remove('opacity-100', 'translate-y-0');
                toast.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => toast.classList.add('hidden'), 500);
            }, 3000);
        };

        // Jika ada pesan dari session Laravel, tampilkan otomatis
        @if (session('success'))
            showToast("{{ session('success') }}", "success");
        @elseif (session('error'))
            showToast("{{ session('error') }}", "error");
        @endif
    });
</script>
