/**
 * Rekap Pengeluaran — Modular JavaScript
 *
 * Fitur:
 * - Format input currency (Rupiah)
 * - Hapus massal (submit form hapus)
 * - Checkbox pilih semua
 * - Auto-submit form filter
 * - Toggle dropdown cetak
 * - Penanganan submit form (cegah double submit)
 * - Reset state submit saat halaman dimuat ulang (tombol kembali)
 */

// ============================================================
// FORMAT INPUT CURRENCY
// ============================================================

/**
 * Format input currency ke format Indonesia (Rp X.XXX).
 * @param {HTMLInputElement} input
 */
function formatCurrencyInput(input) {
    if (!input) return;
    const numeric = String(input.value ?? '').replace(/[^\d]/g, '');
    input.value = numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '';
}
window.formatCurrencyInput = formatCurrencyInput;

// ============================================================
// KATEGORI INCOME vs EXPENSE — LABEL & NOMOR FAKTUR
// ============================================================

/**
 * Sinkronkan form (tambah/edit) berdasarkan tipe kategori terpilih.
 * - INCOME: label "Jumlah Pemasukan", bagian No. Faktur disembunyikan (di-generate otomatis).
 * - EXPENSE: label "Jumlah Pengeluaran", bagian No. Faktur ditampilkan.
 *
 * Alur:
 * 1. Baca <select name="transaction_category_id"> dari form dan ambil opsi terpilih.
 * 2. Tentukan isIncome dari dataset.type === 'INCOME'.
 * 3. Update label .amount-label sesuai tipe (Jumlah Pemasukan / Jumlah Pengeluaran).
 * 4. Toggle class 'hidden' pada .invoice-section — disembunyikan jika INCOME.
 *
 * @param {HTMLFormElement} form
 */
function syncCategoryFields(form) {
    const select = form.querySelector('select[name="transaction_category_id"]');
    if (!select) return;

    const selected = select.options[select.selectedIndex];
    const isIncome = selected && selected.dataset.type === 'INCOME';

    const amountLabel = form.querySelector('.amount-label');
    const invoiceSection = form.querySelector('.invoice-section');

    if (amountLabel) {
        amountLabel.innerHTML = (isIncome ? 'Jumlah Pemasukan' : 'Jumlah Pengeluaran') +
            ' <span class="text-error">*</span>';
    }
    if (invoiceSection) {
        invoiceSection.classList.toggle('hidden', isIncome);
    }
}
window.syncCategoryFields = syncCategoryFields;

/**
 * Submit bulk delete form dengan loading indicator.
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
window.submitDeleteForm = submitDeleteForm;

// ============================================================
// INISIALISASI
// ============================================================

/**
 * Inisialisasi seluruh interaktivitas halaman Rekap Pengeluaran.
 *
 * Alur:
 * 1. Sinkronkan label form tambah/edit berdasarkan tipe kategori
 *    (syncCategoryFields) + ikat event 'change' pada tiap select kategori.
 * 2. Ikat checkbox pilih semua dan update status tombol hapus massal.
 * 3. Format input currency untuk semua .expense-amount-input.
 * 4. Ikat auto-submit form filter (kategori, bulan, tahun).
 * 5. Ikat submit form modal Tambah dan semua modal Edit (cegah double submit).
 * 6. Reset status submit saat halaman dimuat ulang (pageshow).
 */
document.addEventListener('DOMContentLoaded', function () {

    // ============================================================
    // KATEGORI INCOME vs EXPENSE — SINKRON LABEL FORM
    // ============================================================

    document.querySelectorAll('#addModal form, [id^="editModal-"] form').forEach(function (form) {
        const select = form.querySelector('select[name="transaction_category_id"]');
        if (select) {
            syncCategoryFields(form);
            select.addEventListener('change', function () {
                syncCategoryFields(form);
            });
        }
    });

    // ============================================================
    // CHECKBOX PILIH SEMUA
    // ============================================================

    const selectAllCheckbox = document.getElementById('selectAll');
    const expenseCheckboxes = document.querySelectorAll('input[name="selected_expenses[]"]');
    const deleteButton = document.getElementById('delete-button');

    /**
     * Update status disabled tombol hapus berdasarkan checkbox yang dipilih.
     */
    function updateDeleteButtonState() {
        const anyChecked = Array.from(expenseCheckboxes).some(cb => cb.checked);
        if (deleteButton) {
            deleteButton.disabled = !anyChecked;
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            expenseCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateDeleteButtonState();
        });
    }

    expenseCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            if (!this.checked) {
                selectAllCheckbox.checked = false;
            } else {
                const allChecked = Array.from(expenseCheckboxes).every(cb => cb.checked);
                selectAllCheckbox.checked = allChecked;
            }
            updateDeleteButtonState();
        });
    });

    updateDeleteButtonState();

    // ============================================================
    // FORMAT INPUT CURRENCY
    // ============================================================

    document.querySelectorAll('.expense-amount-input').forEach(input => {
        if (input.value) {
            formatCurrencyInput(input);
        }

        input.addEventListener('input', function () {
            formatCurrencyInput(this);
        });
    });

    // ============================================================
    // AUTO-SUBMIT FORM FILTER
    // ============================================================

    const categorySelect = document.getElementById('category-select');
    const monthSelect = document.getElementById('month-select');
    const yearSelect = document.getElementById('year-select');

    const filterForm = categorySelect ? categorySelect.closest('form') : null;

    if (categorySelect && filterForm) {
        categorySelect.addEventListener('change', function () {
            filterForm.submit();
        });
    }

    if (monthSelect && filterForm) {
        monthSelect.addEventListener('change', function () {
            filterForm.submit();
        });
    }

    if (yearSelect && filterForm) {
        yearSelect.addEventListener('change', function () {
            filterForm.submit();
        });
    }

    // ============================================================
    // PENANGANAN SUBMIT FORM — MODAL TAMBAH
    // ============================================================

    /**
     * Binding submit form modal Tambah — cegah double submit.
     *
     * Saat submit: handleFormSubmit mengubah tombol menjadi loading
     * (spinner + disabled). Jika proses submit sudah berjalan,
     * submit dibatalkan dengan preventDefault.
     */
    const addForm = document.querySelector('#addModal form');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.innerHTML : '';

            if (!handleFormSubmit(submitBtn, originalText)) {
                e.preventDefault();
                return false;
            }
        });
    }

    // ============================================================
    // PENANGANAN SUBMIT FORM — MODAL EDIT
    // ============================================================

    /**
     * Binding submit untuk semua form modal Edit — cegah double submit.
     * Logika sama dengan modal Tambah (handleFormSubmit + preventDefault).
     */
    const editForms = document.querySelectorAll('[id^="editModal-"] form');
    editForms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.innerHTML : '';

            if (!handleFormSubmit(submitBtn, originalText)) {
                e.preventDefault();
                return false;
            }
        });
    });

    // ============================================================
    // RESET STATE SUBMIT SAAT HALAMAN DIMUAT ULANG
    // ============================================================

    window.addEventListener('pageshow', function () {
        resetFormSubmitState();
    });
});
