<div id="toast"
    class="hidden fixed top-5 right-5 z-50 items-center w-auto max-w-xs p-4 space-x-3 text-text-primary bg-white border-2 rounded-xl shadow-xl transition-all duration-500">
    <div id="toastIcon" class="flex-shrink-0">
        <!-- Ikon default (berhasil) -->
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-success" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
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

            // Ubah warna & ikon sesuai tipe menggunakan Tailwind Config
            if (type === 'success') {
                toast.classList.remove('bg-error-light', 'border-error', 'bg-warning-light',
                    'border-warning');
                toast.classList.add('bg-success-light', 'border-success');
                toastIcon.innerHTML =
                    `<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`;
            } else if (type === 'error') {
                toast.classList.remove('bg-success-light', 'border-success', 'bg-warning-light',
                    'border-warning');
                toast.classList.add('bg-error-light', 'border-error');
                toastIcon.innerHTML =
                    `<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`;
            } else if (type === 'warning') {
                toast.classList.remove('bg-success-light', 'border-success', 'bg-error-light',
                    'border-error');
                toast.classList.add('bg-warning-light', 'border-warning');
                toastIcon.innerHTML =
                    `<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>`;
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
