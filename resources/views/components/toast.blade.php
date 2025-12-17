<div id="toast"
    class="hidden fixed top-6 right-6 z-[60] items-center w-auto max-w-sm p-5 space-x-4 text-gray-800 bg-white rounded-2xl shadow-[0_10px_40px_rgba(0,0,0,0.12)] backdrop-blur-sm border border-gray-100 transition-all duration-300 ease-out">
    <div id="toastIcon" class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-xl">
        <!-- Ikon default (berhasil) -->
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-success" fill="none" viewBox="0 0 24 24"
            stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
        </svg>
    </div>
    <div id="toastInner" class="flex-1 min-w-0">
        <p id="toastMessage" class="text-sm font-semibold leading-relaxed"></p>
    </div>
    <button onclick="hideToast()" class="flex-shrink-0 ml-2 text-gray-400 hover:text-gray-600 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
            stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toast = document.getElementById('toast');
        const toastInner = document.getElementById('toastInner');
        const toastMessage = document.getElementById('toastMessage');
        const toastIcon = document.getElementById('toastIcon');
        let toastTimeout = null;

        // Fungsi untuk menutup toast
        window.hideToast = function() {
            clearTimeout(toastTimeout);
            toast.classList.remove('opacity-100', 'translate-x-0');
            toast.classList.add('opacity-0', 'translate-x-8');
            setTimeout(() => {
                toast.classList.add('hidden');
                toast.classList.remove('flex');
            }, 300);
        };

        // Fungsi menampilkan toast
        window.showToast = function(message, type = 'success') {
            clearTimeout(toastTimeout);
            toastMessage.textContent = message;

            // Reset classes
            toast.classList.remove('border-green-200', 'border-red-200', 'border-amber-200');
            toastIcon.classList.remove('bg-green-50', 'bg-red-50', 'bg-amber-50');

            // Ubah warna & ikon sesuai tipe
            if (type === 'success') {
                toast.classList.add('border-green-200');
                toastIcon.classList.add('bg-green-50');
                toastIcon.innerHTML =
                    `<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`;
            } else if (type === 'error') {
                toast.classList.add('border-red-200');
                toastIcon.classList.add('bg-red-50');
                toastIcon.innerHTML =
                    `<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`;
            } else if (type === 'warning') {
                toast.classList.add('border-amber-200');
                toastIcon.classList.add('bg-amber-50');
                toastIcon.innerHTML =
                    `<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>`;
            }

            // Tampilkan toast dengan animasi slide in
            toast.classList.remove('hidden', 'opacity-0', 'translate-x-8');
            toast.classList.add('flex', 'opacity-100', 'translate-x-0');

            // Hilang otomatis setelah 4 detik
            toastTimeout = setTimeout(() => {
                hideToast();
            }, 4000);
        };

        // Jika ada pesan dari session Laravel, tampilkan otomatis
        @if (session('success'))
            showToast("{{ session('success') }}", "success");
        @elseif (session('error'))
            showToast("{{ session('error') }}", "error");
        @endif
    });
</script>
