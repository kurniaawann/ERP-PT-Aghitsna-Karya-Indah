{{-- ═══════════════════════════════════════════════════════════════════════
     Komponen Modal Bayar Cicilan Kasbon
     Modal untuk mencatat pembayaran cicilan kasbon (tunai/manual).
     Menampilkan informasi kasbon saat ini dan form input jumlah bayar.
     ═══════════════════════════════════════════════════════════════════════ --}}
<div id="payModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="payModalTitle" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        {{-- Backdrop --}}
        <div id="payModalBackdrop" class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true"></div>

        {{-- Spacer --}}
        <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

        {{-- Modal Panel --}}
        <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-surface-base rounded-xl shadow-xl sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
            {{-- Header --}}
            <div class="px-6 pt-5 pb-4 border-b border-border-light">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-text-heading" id="payModalTitle">Bayar Cicilan Kasbon</h3>
                    <button type="button" onclick="closePayModal()" class="text-text-label hover:text-text-primary transition-colors">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>
            </div>

            {{-- Body --}}
            <form id="payForm" method="POST" action="">
                @csrf
                <div class="px-6 py-4 space-y-4">
                    {{-- Info Kasbon --}}
                    <div class="p-4 bg-surface-secondary rounded-lg border border-border-light">
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <span class="text-text-label">Kode Kasbon</span>
                                <p class="font-semibold text-text-primary" id="pay-kasbon-code">-</p>
                            </div>
                            <div>
                                <span class="text-text-label">Jumlah Awal</span>
                                <p class="font-semibold text-text-primary" id="pay-kasbon-amount">-</p>
                            </div>
                            <div>
                                <span class="text-text-label">Sisa Hutang</span>
                                <p class="font-semibold text-error" id="pay-kasbon-remaining">-</p>
                            </div>
                            <div>
                                <span class="text-text-label">Status</span>
                                <p id="pay-kasbon-status">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-warning-light text-warning">Belum Dibayar</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Input Jumlah Bayar --}}
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">
                            Jumlah Bayar <span class="text-error">*</span>
                        </label>
                        <input type="text" inputmode="numeric" name="amount" id="pay-amount"
                            class="w-full border border-border-strong rounded-lg p-3 bg-surface-base text-text-input text-lg font-semibold focus:ring-2 focus:ring-primary focus:border-transparent"
                            placeholder="Masukkan jumlah bayar" required min="1">
                        <p class="text-xs text-text-label mt-1">
                            Maksimal: <span id="pay-max-amount" class="font-semibold text-error">Rp 0</span>
                        </p>
                    </div>

                    {{-- Quick Amount Buttons --}}
                    <div>
                        <label class="block text-xs text-text-label mb-2">Bayar Cepat:</label>
                        <div class="flex flex-wrap gap-2" id="pay-quick-buttons">
                            <button type="button" onclick="setPayAmount(this)"
                                class="px-3 py-1.5 text-xs font-medium bg-surface-secondary border border-border-light rounded-lg hover:bg-primary-light hover:border-primary hover:text-primary transition-colors"
                                data-amount="10000">Rp 10.000</button>
                            <button type="button" onclick="setPayAmount(this)"
                                class="px-3 py-1.5 text-xs font-medium bg-surface-secondary border border-border-light rounded-lg hover:bg-primary-light hover:border-primary hover:text-primary transition-colors"
                                data-amount="25000">Rp 25.000</button>
                            <button type="button" onclick="setPayAmount(this)"
                                class="px-3 py-1.5 text-xs font-medium bg-surface-secondary border border-border-light rounded-lg hover:bg-primary-light hover:border-primary hover:text-primary transition-colors"
                                data-amount="50000">Rp 50.000</button>
                            <button type="button" onclick="setPayFullAmount()"
                                class="px-3 py-1.5 text-xs font-medium bg-success-light border border-success rounded-lg text-success hover:bg-success hover:text-white transition-colors">
                                Lunasi Semua
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 bg-surface-secondary border-t border-border-light flex justify-end gap-3">
                    <button type="button" onclick="closePayModal()"
                        class="px-4 py-2 text-sm font-medium text-text-label bg-surface-base border border-border-light rounded-lg hover:bg-surface-secondary transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="pay-submit-btn"
                        class="px-4 py-2 text-sm font-medium text-white bg-success rounded-lg hover:bg-success-hover transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-money-bill-wave"></i>
                        Bayar Cicilan
                    </button>
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
                    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';
                    submitBtn.disabled = true;
                }
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
