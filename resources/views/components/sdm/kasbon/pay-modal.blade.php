{{-- ═══════════════════════════════════════════════════════════════════════
     Komponen Modal Bayar Cicilan Kasbon (gaya Filament)
     Modal untuk mencatat pembayaran cicilan kasbon (tunai/manual).
     Dibuka/ditutup oleh openPayModal() / closePayModal() di bawah (toggle
     kelas `hidden` pada #payModal), jadi markup modal ditulis sendiri —
     bukan x-fi.modal (yang memakai openModal: hidden ↔ flex).
     Hook: #payModal, #payModalBackdrop, #payForm, #pay-kasbon-code,
     #pay-kasbon-amount, #pay-kasbon-remaining, #pay-kasbon-status,
     #pay-amount, #pay-max-amount, #pay-quick-buttons, #pay-submit-btn.
     ═══════════════════════════════════════════════════════════════════════ --}}
<div id="payModal" class="fixed inset-0 z-50 hidden overflow-y-auto text-base" aria-labelledby="payModalTitle" role="dialog" aria-modal="true">
    {{-- Backdrop --}}
    <div id="payModalBackdrop" class="fixed inset-0 bg-gray-950/50" aria-hidden="true"></div>

    <div class="relative flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-lg rounded-xl bg-white shadow-xl ring-1 ring-gray-950/5">

            {{-- Tombol tutup (X) --}}
            <button type="button" onclick="closePayModal()" title="Tutup"
                class="absolute end-4 top-4 flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-50 hover:text-gray-500">
                <x-fi.icon name="x-mark" class="h-5 w-5" />
                <span class="sr-only">Tutup</span>
            </button>

            {{-- Header --}}
            <div class="flex items-start gap-x-4 px-6 pe-16 pt-6">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                    <x-fi.icon name="banknotes" class="h-5 w-5" />
                </div>
                <h2 id="payModalTitle" class="pt-2 text-base font-semibold leading-6 text-gray-950">Bayar Cicilan Kasbon</h2>
            </div>

            <form id="payForm" method="POST" action="">
                @csrf
                <div class="flex flex-col gap-y-5 px-6 py-6">
                    {{-- Info Kasbon --}}
                    <dl class="grid grid-cols-2 gap-3 rounded-lg bg-gray-50 p-4 text-sm ring-1 ring-inset ring-gray-950/5">
                        <div>
                            <dt class="text-gray-500">Kode Kasbon</dt>
                            <dd class="font-semibold text-gray-950" id="pay-kasbon-code">-</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Jumlah Awal</dt>
                            <dd class="font-semibold text-gray-950" id="pay-kasbon-amount">-</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Sisa Pembayaran</dt>
                            <dd class="font-semibold text-red-600" id="pay-kasbon-remaining">-</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Status</dt>
                            <dd id="pay-kasbon-status"><x-fi.badge color="warning">Belum Dibayar</x-fi.badge></dd>
                        </div>
                    </dl>

                    {{-- Jumlah Bayar --}}
                    <div class="grid gap-y-2">
                        <label for="pay-amount" class="text-sm font-medium leading-6 text-gray-950">
                            Jumlah Bayar<sup class="ms-0.5 text-red-600">*</sup>
                        </label>
                        <x-fi.input inputmode="numeric" name="amount" id="pay-amount" prefix="Rp"
                            class="!text-lg font-semibold" placeholder="Masukkan jumlah bayar" required min="1" />
                        <p class="text-sm text-gray-500">
                            Maksimal: <span id="pay-max-amount" class="font-semibold text-red-600">Rp 0</span>
                        </p>
                    </div>

                    {{-- Bayar Cepat --}}
                    <div class="grid gap-y-2">
                        <p class="text-sm text-gray-500">Bayar Cepat:</p>
                        <div class="flex flex-wrap gap-2" id="pay-quick-buttons">
                            @foreach ([10000, 25000, 50000] as $quick)
                                <button type="button" onclick="setPayAmount(this)" data-amount="{{ $quick }}"
                                    class="rounded-lg bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-950/10 transition hover:bg-gray-50">
                                    Rp {{ number_format($quick, 0, ',', '.') }}
                                </button>
                            @endforeach
                            <button type="button" onclick="setPayFullAmount()"
                                class="rounded-lg bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20 transition hover:bg-emerald-100">
                                Lunasi Semua
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Tombol di kanan: [Batal] [Bayar Cicilan] --}}
                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
                    <x-fi.button color="gray" onclick="closePayModal()">Batal</x-fi.button>
                    <x-fi.button type="submit" id="pay-submit-btn" color="success" icon="banknotes">Bayar Cicilan</x-fi.button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    /**
     * Modal Bayar Cicilan Kasbon
     *
     * Fungsi untuk membuka/menutup modal dan mengelola pembayaran cicilan.
     */
    let currentPayKasbonCode = '';
    let currentPayMaxAmount = 0;

    /**
     * Membuka modal bayar cicilan.
     *
     * @param {string} kasbonCode      Kode kasbon
     * @param {string} amountFormatted  Jumlah awal terformat (Rp xxx)
     * @param {string} remainingFormatted Sisa hutang terformat
     * @param {number} remainingAmount  Sisa hutang numerik
     */
    window.openPayModal = function (kasbonCode, amountFormatted, remainingFormatted, remainingAmount) {
        currentPayKasbonCode = kasbonCode;
        currentPayMaxAmount = remainingAmount;

        const modal = document.getElementById('payModal');
        const form = document.getElementById('payForm');
        const amountInput = document.getElementById('pay-amount');

        // Set form action
        form.action = `/kasbon/${kasbonCode}/pay`;

        // Set info kasbon
        document.getElementById('pay-kasbon-code').textContent = kasbonCode;
        document.getElementById('pay-kasbon-amount').textContent = amountFormatted;
        document.getElementById('pay-kasbon-remaining').textContent = remainingFormatted;
        document.getElementById('pay-max-amount').textContent = remainingFormatted;

        // Reset input
        amountInput.value = '';
        amountInput.max = remainingAmount;

        // Show modal
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        setTimeout(() => amountInput.focus(), 100);
    };

    /**
     * Menutup modal bayar cicilan.
     */
    window.closePayModal = function () {
        const modal = document.getElementById('payModal');
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        currentPayKasbonCode = '';
        currentPayMaxAmount = 0;
    };

    /**
     * Set jumlah bayar dari tombol cepat.
     */
    window.setPayAmount = function (btn) {
        const amount = parseInt(btn.dataset.amount) || 0;
        const input = document.getElementById('pay-amount');
        input.value = new Intl.NumberFormat('id-ID').format(Math.min(amount, currentPayMaxAmount));
        input.focus();
    };

    /**
     * Set jumlah bayar ke sisa hutang penuh (lunasi semua).
     */
    window.setPayFullAmount = function () {
        const input = document.getElementById('pay-amount');
        input.value = new Intl.NumberFormat('id-ID').format(currentPayMaxAmount);
        input.focus();
    };

    // Format input sebagai mata uang
    document.addEventListener('DOMContentLoaded', function () {
        const payAmountInput = document.getElementById('pay-amount');
        if (payAmountInput) {
            payAmountInput.addEventListener('input', function () {
                const numeric = this.value.replace(/[^\d]/g, '');
                this.value = numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '';
            });
        }

        // Close on backdrop click
        const backdrop = document.getElementById('payModalBackdrop');
        if (backdrop) {
            backdrop.addEventListener('click', closePayModal);
        }

        // Submit handler with loading
        const payForm = document.getElementById('payForm');
        if (payForm) {
            payForm.addEventListener('submit', function (e) {
                const submitBtn = document.getElementById('pay-submit-btn');
                const amountInput = document.getElementById('pay-amount');
                const rawAmount = parseInt(amountInput.value.replace(/[^\d]/g, ''), 10) || 0;

                if (rawAmount <= 0) {
                    e.preventDefault();
                    alert('Jumlah pembayaran harus lebih dari 0');
                    return false;
                }

                if (rawAmount > currentPayMaxAmount) {
                    e.preventDefault();
                    alert('Jumlah pembayaran melebihi sisa hutang');
                    return false;
                }

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';
                }

                return true;
            });
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !document.getElementById('payModal').classList.contains('hidden')) {
            closePayModal();
        }
    });
</script>
