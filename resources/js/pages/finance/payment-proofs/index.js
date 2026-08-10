/**
 * Modul Bukti Pembayaran - Logika Frontend
 *
 * Menangani interaksi UI untuk modul Bukti Pembayaran:
 * - Lazy loading opsi invoice (berbasis chunk)
 * - Bagian nominal dinamis & perhitungan tahap pembayaran
 * - Validasi nominal (tidak melebihi sisa tagihan)
 * - Binding form untuk modal create & edit
 * - Checkbox pilih semua / hapus massal
 *
 * Dependensi (dari modul shared):
 * - handleFormSubmit, resetFormSubmitState (form-submit.js)
 * - parseCurrencyInput, formatRupiah (currency.js)
 * - openModal, closeModal (layout/app.blade.php global)
 */
/* global handleFormSubmit, parseCurrencyInput, formatRupiah */

const PAYMENT_PROOF_INVOICE_CHUNK_SIZE = 10;

/**
 * Menentukan apakah tipe invoice memakai nominal manual (bisa diisi user).
 *
 * 'proyek' dan 'recap' memakai input manual yang divalidasi terhadap sisa
 * tagihan; tipe lain mengikuti sisa tagihan (readonly / otomatis lunas).
 *
 * @param  {string} invoiceType
 * @return {boolean}
 */
function isManualPaymentAmountType(invoiceType) {
    return invoiceType === 'proyek' || invoiceType === 'recap';
}

/**
 * Menentukan apakah tipe invoice memakai tahap pembayaran (Pembayaran ke-N).
 *
 * 'proyek' dan 'recap' memakai penomoran tahap berurutan; tipe lain (barang,
 * alumunium) tidak memiliki konsep tahap.
 *
 * @param  {string} invoiceType
 * @return {boolean}
 */
function isStagedPaymentType(invoiceType) {
    return invoiceType === 'proyek' || invoiceType === 'recap';
}

// ─── Helper Config ──────────────────────────────────────────────────────

/**
 * Mengambil referensi DOM elements berdasarkan prefix (create atau edit-{id}).
 *
 * @param  {string} prefix  'create' atau 'edit-{id}'
 * @return {Object}  Config object berisi DOM element references
 */
function getPaymentProofConfig(prefix) {
    return {
        module: document.getElementById(`payment-proof-module-${prefix}`),
        invoiceType: document.getElementById(`payment-proof-invoice-type-${prefix}`),
        invoiceNumber: document.getElementById(`payment-proof-invoice-number-${prefix}`),
        stageText: document.getElementById(`payment-proof-stage-${prefix}`),
        stageInput: document.getElementById(`payment-proof-stage-input-${prefix}`),
        stageWrap: document.getElementById(`payment-proof-stage-wrap-${prefix}`),
        amountWrap: document.getElementById(`payment-proof-amount-wrap-${prefix}`),
        amountInput: document.getElementById(`payment-proof-amount-${prefix}`),
        amountHelp: document.getElementById(`payment-proof-amount-help-${prefix}`),
        amountWarning: document.getElementById(`payment-proof-amount-warning-${prefix}`),
    };
}

/**
 * Mengambil data invoice dari window.paymentProofInvoiceData berdasarkan module & type.
 *
 * @param  {string} prefix
 * @return {Array}  Array of invoice option objects
 */
function getPaymentProofInvoiceData(prefix) {
    const config = getPaymentProofConfig(prefix);
    if (!config.module || !config.invoiceType || !config.invoiceNumber) return [];

    const moduleValue = config.module.value;
    const invoiceTypeValue = config.invoiceType.value;

    return window.paymentProofInvoiceData?.[moduleValue]?.[invoiceTypeValue] ?? [];
}

// ─── Pemuatan Opsi Invoice ──────────────────────────────────────────────

/**
 * Menambahkan opsi invoice ke dropdown secara bertahap (lazy loading).
 *
 * Alur:
 * 1. Ambil data invoice dari window.paymentProofInvoiceData (via getPaymentProofInvoiceData).
 * 2. Baca jumlah item yang sudah dimuat dari dataset.loadedCount.
 * 3. Ambil `count` item berikutnya (default chunk 10) via slice.
 * 4. Untuk tiap item buat <option>: label (+ " (Lunas)" bila lunas), simpan
 *    next_stage & remaining_amount ke dataset, dan disable jika sudah lunas.
 * 5. Perbarui dataset.loadedCount dengan jumlah total yang sudah dimuat.
 *
 * @param  {string}  prefix
 * @param  {number}  count  Jumlah item per chunk (default: 10)
 * @return {void}
 */
function appendPaymentProofInvoiceOptions(prefix, count = PAYMENT_PROOF_INVOICE_CHUNK_SIZE) {
    const config = getPaymentProofConfig(prefix);
    const invoiceData = getPaymentProofInvoiceData(prefix);

    if (!config.invoiceNumber || invoiceData.length === 0) {
        return;
    }

    const loadedCount = Number(config.invoiceNumber.dataset.loadedCount || 0);
    const nextItems = invoiceData.slice(loadedCount, loadedCount + count);

    nextItems.forEach(item => {
        const option = document.createElement('option');
        option.value = item.value;

        const optionSuffix = item.is_fully_paid ? ' (Lunas)' : '';
        option.textContent = `${item.label}${optionSuffix}`;
        option.dataset.nextStage = item.next_stage || '';
        option.dataset.remainingAmount = item.remaining_amount || 0;
        option.dataset.netAmount = item.net_amount || 0;
        option.dataset.paidAmount = item.paid_amount || 0;
        option.dataset.isFullyPaid = item.is_fully_paid ? '1' : '0';

        if (item.is_fully_paid) {
            option.disabled = true;
        }

        config.invoiceNumber.appendChild(option);
    });

    config.invoiceNumber.dataset.loadedCount = String(loadedCount + nextItems.length);
}

/**
 * Memuat opsi invoice ke dropdown dan mengikat event scroll untuk lazy loading.
 *
 * Alur pemilihan multi-invoice (chunked 10):
 * 1. Kosongkan dropdown lalu isi placeholder "Pilih invoice"; disable bila tidak ada data.
 * 2. Reset dataset.loadedCount = 0 lalu panggil appendPaymentProofInvoiceOptions
 *    untuk memuat chunk pertama (10 item).
 * 3. Jika ada selectedInvoiceNumber (mode edit), terus muat chunk berikutnya
 *    sampai opsi tersebut tersedia, lalu set nilai dropdown.
 * 4. Tampilkan/sembunyikan section tahap pembayaran (hanya untuk tipe 'proyek').
 * 5. Panggil updatePaymentProofStage untuk menyelaraskan tampilan.
 * 6. Ikat event scroll sekali saja (flag __paymentProofScrollBound) untuk memuat
 *    chunk berikutnya saat dropdown hampir habis di-scroll.
 *
 * @param  {string}      prefix
 * @param  {string|null} selectedInvoiceNumber  Invoice yang sudah dipilih (untuk edit mode)
 * @return {void}
 */
function loadPaymentProofInvoices(prefix, selectedInvoiceNumber = null) {
    const config = getPaymentProofConfig(prefix);
    const invoiceData = getPaymentProofInvoiceData(prefix);

    if (!config.module || !config.invoiceType || !config.invoiceNumber) return;

    config.invoiceNumber.innerHTML = '<option value="">Pilih invoice</option>';
    config.invoiceNumber.disabled = invoiceData.length === 0;
    config.invoiceNumber.dataset.loadedCount = '0';

    appendPaymentProofInvoiceOptions(prefix);

    if (selectedInvoiceNumber) {
        while (
            Number(config.invoiceNumber.dataset.loadedCount || 0) < invoiceData.length &&
            !Array.from(config.invoiceNumber.options).some(option => option.value === selectedInvoiceNumber)
        ) {
            appendPaymentProofInvoiceOptions(prefix);
        }

        config.invoiceNumber.value = selectedInvoiceNumber;
    }

    if (config.stageWrap) {
        config.stageWrap.classList.toggle('hidden', !isStagedPaymentType(config.invoiceType.value));
    }

    updatePaymentProofStage(prefix);

    if (config.invoiceNumber.__paymentProofScrollBound !== true) {
        config.invoiceNumber.addEventListener('scroll', () => {
            const currentInvoiceData = getPaymentProofInvoiceData(prefix);
            const remainingSpace = config.invoiceNumber.scrollHeight - config.invoiceNumber.scrollTop -
                config.invoiceNumber.clientHeight;

            if (remainingSpace <= 4) {
                const loadedCount = Number(config.invoiceNumber.dataset.loadedCount || 0);

                if (loadedCount < currentInvoiceData.length) {
                    appendPaymentProofInvoiceOptions(prefix);
                }
            }
        });

        config.invoiceNumber.__paymentProofScrollBound = true;
    }
}

/**
 * Wrapper untuk loadPaymentProofInvoices (untuk dipanggil saat module/type berubah).
 *
 * @param  {string}      prefix
 * @param  {string|null} selectedInvoiceNumber
 * @return {void}
 */
function updatePaymentProofInvoices(prefix, selectedInvoiceNumber = null) {
    const config = getPaymentProofConfig(prefix);
    if (!config.module || !config.invoiceType || !config.invoiceNumber) return;

    loadPaymentProofInvoices(prefix, selectedInvoiceNumber);
}

// ─── Perhitungan Tahap & Nominal ──────────────────────────────────────────

/**
 * Memperbarui tampilan tahap pembayaran berdasarkan invoice yang dipilih.
 *
 * Alur tahap pembayaran (select -> amount -> done):
 * 1. Ambil opsi invoice terpilih dan dataset.nextStage-nya.
 * 2. Bila tipe bukan 'proyek'/'recap': tampilkan "Tidak ada tahap pembayaran".
 * 3. Bila ada nextStage: tampilkan "Pembayaran ke {n}" dan isi hidden input stage.
 * 4. Selain itu tampilkan "-".
 * 5. Teruskan ke updatePaymentProofAmountSection agar section nominal ikut sinkron.
 *
 * @param  {string} prefix
 * @return {void}
 */
function updatePaymentProofStage(prefix) {
    const config = getPaymentProofConfig(prefix);
    if (!config.invoiceNumber || !config.stageText || !config.stageInput) return;

    const selectedOption = config.invoiceNumber.options[config.invoiceNumber.selectedIndex];
    const nextStage = selectedOption?.dataset?.nextStage;

    if (config.stageWrap) {
        config.stageWrap.classList.toggle('hidden', !isStagedPaymentType(config.invoiceType.value));
    }

    if (!isStagedPaymentType(config.invoiceType.value)) {
        config.stageText.textContent = 'Tidak ada tahap pembayaran';
        config.stageInput.value = '';
    } else if (nextStage) {
        config.stageText.textContent = `Pembayaran ke ${nextStage}`;
        config.stageInput.value = nextStage;
    } else {
        config.stageText.textContent = '-';
        config.stageInput.value = '';
    }

    updatePaymentProofAmountSection(prefix);
}

/**
 * Memperbarui tampilan section nominal pembayaran.
 *
 * Untuk invoice proyek: nominal bisa diubah manual.
 * Untuk tipe lain: nominal mengikuti sisa tagihan (readonly).
 *
 * Alur (membedakan 1 invoice vs banyak / proyek vs non-proyek):
 * - Non-proyek: sembunyikan input nominal, set nilai readonly mengikuti sisa
 *   tagihan, dan tampilkan bantuan "Nominal mengikuti sisa tagihan ...".
 * - Proyek: tampilkan input nominal yang bisa diedit; bila belum ada invoice
 *   terpilih tampilkan petunjuk memilih, bila sudah tampilkan sisa tagihan
 *   invoice tersebut.
 *
 * @param  {string} prefix
 * @return {void}
 */
function updatePaymentProofAmountSection(prefix) {
    const config = getPaymentProofConfig(prefix);

    if (!config.invoiceNumber || !config.amountInput || !config.amountHelp || !config.amountWrap) {
        return;
    }

    const selectedOption = config.invoiceNumber.options[config.invoiceNumber.selectedIndex];
    const remainingAmount = Number(selectedOption?.dataset?.remainingAmount || 0);

    if (!isManualPaymentAmountType(config.invoiceType.value)) {
        config.amountWrap.classList.add('hidden');
        config.amountInput.disabled = true;
        config.amountInput.required = false;
        config.amountInput.value = selectedOption?.value ? formatRupiah(remainingAmount) : '';
        config.amountHelp.textContent = selectedOption?.value ?
            `Nominal mengikuti sisa tagihan ${formatRupiah(remainingAmount)}.` :
            'Pilih invoice terlebih dahulu agar nominal otomatis terisi.';
        return;
    }

    config.amountWrap.classList.remove('hidden');
    config.amountInput.disabled = false;
    config.amountInput.required = true;

    if (!selectedOption || !selectedOption.value) {
        config.amountHelp.textContent = 'Pilih invoice terlebih dahulu agar sisa tagihan tampil di sini.';
        return;
    }

    config.amountHelp.textContent = remainingAmount > 0
        ? `Sisa tagihan invoice ini ${formatRupiah(remainingAmount)}.`
        : 'Invoice ini sudah lunas.';
}

/**
 * Memvalidasi nominal pembayaran apakah melebihi sisa tagihan.
 *
 * Alur:
 * 1. Baca sisa tagihan (remaining_amount) dari opsi invoice terpilih.
 * 2. Validasi hanya berlaku untuk tipe 'proyek' dengan invoice terpilih;
 *    selain itu sembunyikan peringatan dan anggap valid.
 * 3. Parse nominal input via parseCurrencyInput.
 * 4. Jika nominal > sisa tagihan (dan sisa > 0): tampilkan pesan peringatan
 *    dan kembalikan false (submit dibatalkan).
 * 5. Selain itu sembunyikan peringatan dan kembalikan true.
 *
 * @param  {string}  prefix
 * @return {boolean} true jika valid, false jika melebihi
 */
function validatePaymentProofAmount(prefix) {
    const config = getPaymentProofConfig(prefix);
    if (!config.amountInput || !config.amountWarning) return true;

    const selectedOption = config.invoiceNumber?.options[config.invoiceNumber.selectedIndex];
    const remainingAmount = Number(selectedOption?.dataset?.remainingAmount || 0);

    if (!isManualPaymentAmountType(config.invoiceType?.value) || !selectedOption?.value) {
        config.amountWarning.classList.add('hidden');
        return true;
    }

    const amountValue = parseCurrencyInput(config.amountInput.value);

    if (amountValue > remainingAmount && remainingAmount > 0) {
        config.amountWarning.innerHTML = '<span class="font-semibold">Peringatan:</span> Nominal pembayaran tidak boleh melebihi sisa tagihan sebesar ' + formatRupiah(remainingAmount) + '!';
        config.amountWarning.classList.remove('hidden');
        return false;
    } else {
        config.amountWarning.classList.add('hidden');
        return true;
    }
}

// ─── Binding Form ────────────────────────────────────────────────────────

/**
 * Mengikat event listener dan menginisialisasi form bukti pembayaran.
 *
 * @param  {string} prefix   'create' atau 'edit-{id}'
 * @param  {Object} defaults Nilai default untuk edit mode
 * @return {void}
 */
function bindPaymentProofForm(prefix, defaults = {}) {
    const config = getPaymentProofConfig(prefix);
    if (!config.module || !config.invoiceType || !config.invoiceNumber) return;

    if (defaults.moduleType) config.module.value = defaults.moduleType;
    if (defaults.invoiceType) config.invoiceType.value = defaults.invoiceType;

    updatePaymentProofInvoices(prefix, defaults.invoiceNumber ?? null);

    if (defaults.amount && config.amountInput) {
        config.amountInput.value = formatRupiah(defaults.amount);
    }

    config.module.addEventListener('change', () => updatePaymentProofInvoices(prefix));
    config.invoiceType.addEventListener('change', () => updatePaymentProofInvoices(prefix));
    config.invoiceNumber.addEventListener('change', () => updatePaymentProofStage(prefix));

    if (config.amountInput) {
        config.amountInput.addEventListener('input', function () {
            this.value = formatRupiah(parseCurrencyInput(this.value));
            updatePaymentProofAmountSection(prefix);
            validatePaymentProofAmount(prefix);
        });
    }

    updatePaymentProofStage(prefix);
    updatePaymentProofAmountSection(prefix);
    validatePaymentProofAmount(prefix);
}

// ─── Hapus Massal ─────────────────────────────────────────────────────────

/**
 * Submit form hapus dengan loading state.
 *
 * @return {void}
 */
function submitDeleteForm() {
    const deleteBtn = document.getElementById('confirm-btn-deleteModal');
    if (deleteBtn) {
        deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
        deleteBtn.disabled = true;
        deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }

    const form = document.getElementById('deleteForm');
    if (form) {
        form.submit();
    }
}

// ─── Checkbox Pilih Semua ────────────────────────────────────────────────

/**
 * Menginisialisasi checkbox select all dan update delete button state.
 *
 * @return {void}
 */
function initSelectAllCheckbox() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const itemCheckboxes = document.querySelectorAll('input[name="selected_items[]"]');
    const deleteButton = document.getElementById('delete-button');

    /**
     * Update status disabled tombol hapus berdasarkan checkbox yang dipilih.
     * Aktif bila minimal ada 1 checkbox yang dicentang.
     */
    function updateDeleteButtonState() {
        const anyChecked = Array.from(itemCheckboxes).some(cb => cb.checked);
        if (deleteButton) {
            deleteButton.disabled = !anyChecked;
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            itemCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateDeleteButtonState();
        });
    }

    itemCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            if (!this.checked) {
                selectAllCheckbox.checked = false;
            } else {
                const allChecked = Array.from(itemCheckboxes).every(cb => cb.checked);
                selectAllCheckbox.checked = allChecked;
            }
            updateDeleteButtonState();
        });
    });

    updateDeleteButtonState();
}

// ─── Inisialisasi ─────────────────────────────────────────────────────

/**
 * Inisialisasi logika halaman saat DOM siap.
 *
 * Alur:
 * 1. Bind form bukti pembayaran mode create (module, tipe invoice, tahap,
 *    nominal, dan validasi).
 * 2. Ikat submit form modal Tambah: validasi nominal dahulu, jika tidak valid
 *    batalkan submit dan scroll ke peringatan; jika valid tampilkan loading.
 * 3. Inisialisasi checkbox pilih semua untuk hapus massal.
 */
document.addEventListener('DOMContentLoaded', function () {
    bindPaymentProofForm('create');

    // State loading submit form - Modal Tambah
    const addForm = document.querySelector('#addModal form');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            if (!validatePaymentProofAmount('create')) {
                e.preventDefault();
                const warning = document.getElementById('payment-proof-amount-warning-create');
                if (warning) warning.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            if (!handleFormSubmit(submitBtn, originalText)) {
                e.preventDefault();
                return false;
            }
        });
    }

    // Checkbox pilih semua
    initSelectAllCheckbox();
});

// Expose ke global scope untuk akses dari onclick di Blade
window.submitDeleteForm = submitDeleteForm;
