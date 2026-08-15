/**
 * Bukti Kas Keluar (BKK) - Index Page JavaScript
 *
 * Modul ini menangani:
 * - Select all checkbox dan sinkronisasi checkbox individual
 * - Pengelolaan status tombol aksi (hapus, cetak terpilih)
 * - Format input jumlah dengan format Rupiah
 * - Cetak terpilih via AJAX download PDF
 * - Pencegahan double submit pada form tambah/edit
 * - Dinamis label Direktur/Manager berdasarkan tipe template
 */

/* ==========================================
 * STATUS TOMBOL AKSI
 * ========================================== */

/**
 * Memperbarui status tombol aksi (hapus, cetak terpilih) dan jumlah terpilih.
 *
 * Alur:
 * 1. Hitung jumlah checkbox `input[name="ids[]"]` yang dicentang.
 * 2. Nonaktifkan tombol hapus (dengan kelas opacity/cursor) jika tidak ada yang dipilih.
 * 3. Tampilkan/sembunyikan tombol cetak terpilih berdasarkan jumlah terpilih.
 * 4. Perbarui teks jumlah terpilih pada `selectedCountText`.
 */
function updateButtonStates() {
    const deleteButton = document.getElementById('delete-button');
    const printSelectedItem = document.getElementById('printSelectedItem');
    const selectedCountText = document.getElementById('selectedCountText');
    const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');
    const count = checkedCheckboxes.length;

    if (deleteButton) {
        deleteButton.disabled = count === 0;
        deleteButton.classList.toggle('opacity-50', count === 0);
        deleteButton.classList.toggle('cursor-not-allowed', count === 0);
    }

    if (printSelectedItem) {
        if (count > 0) {
            printSelectedItem.classList.remove('hidden');
        } else {
            printSelectedItem.classList.add('hidden');
        }
    }

    if (selectedCountText) {
        selectedCountText.textContent = count;
    }
}

/* ==========================================
 * CETAK TERPILIH
 * ========================================== */

/**
 * Mencetak bukti kas keluar terpilih sebagai PDF.
 *
 * Alur:
 * 1. Ambil route cetak dari hidden input `cash-out-proof-print-selected-route`.
 * 2. Jika route kosong, hentikan proses.
 * 3. Delegasikan ke sharedPrintSelected(route, btn) yang mengumpulkan
 *    checkbox tercentang, mengirim via AJAX, dan mengunduh file PDF.
 *
 * @param {HTMLButtonElement} btn - Tombol yang diklik.
 * @returns {boolean} true jika proses dimulai; false jika route kosong.
 */
function printSelected(btn) {
    const printRoute = document.getElementById('cash-out-proof-print-selected-route');
    const route = printRoute ? printRoute.value : '';

    if (!route) return false;

    return sharedPrintSelected(route, btn);
}

// Expose ke global scope agar bisa dipanggil dari inline onclick di Blade
window.printSelected = printSelected;

/* ==========================================
 * LABEL DIREKTUR / MANAGER
 * ========================================== */

/**
 * Menginisialisasi label Direktur/Manager pada form tambah & edit
 * berdasarkan tipe template BKK.
 *
 * Alur:
 * 1. `bindLabel(templateSelect, labelEl, inputEl)`:
 *    - Jika tipe template `hollow` -> label "Manager", placeholder "SISWORO SUBENO (default)".
 *    - Selain itu -> label "Direktur", placeholder "Zulkarnain,ST.,MT (default)".
 *    - Listener `change` pada select langsung menerapkan label.
 * 2. Bind ke form tambah (`addTemplateType`) dan semua form edit
 *    (`editTemplateType-{suffix}`) beserta label/input pasangannya.
 * 3. Panggil `apply()` sekali di awal untuk menampilkan label default.
 */
function initDirectorLabel() {
    const bindLabel = function (templateSelect, labelSelector, inputEl) {
        if (!templateSelect || !labelSelector || !inputEl) return;

        const apply = function () {
            labelSelector.textContent = (templateSelect.value === 'hollow') ? 'Manager' : 'Direktur';
            inputEl.placeholder = 'Cari petinggi...';
        };

        templateSelect.addEventListener('change', apply);
        apply();
    };

    const addTemplate = document.getElementById('addTemplateType');
    if (addTemplate) {
        const addDirectorLabel = document.querySelector('label[for="addDirector-input"]');
        const addDirectorInput = document.getElementById('addDirector-input');
        bindLabel(addTemplate, addDirectorLabel, addDirectorInput);
    }

    document.querySelectorAll('[id^="editTemplateType-"]').forEach(function (select) {
        const suffix = select.id.replace('editTemplateType-', '');
        const editDirectorLabel = document.querySelector('label[for="editDirector-' + suffix + '-input"]');
        const editDirectorInput = document.getElementById('editDirector-' + suffix + '-input');
        bindLabel(select, editDirectorLabel, editDirectorInput);
    });
}

/* ==========================================
 * INISIALISASI
 * ========================================== */

/**
 * Inisialisasi interaksi halaman index BKK.
 *
 * Alur:
 * 1. Checkbox "Pilih Semua" -> set semua checkbox dan perbarui status tombol.
 * 2. Checkbox individual -> sinkronkan status selectAll dan perbarui status tombol.
 * 3. Format input currency `.cash-out-amount-input` dengan format Rupiah.
 * 4. Cegah double submit pada form modal tambah & edit via handleFormSubmit.
 * 5. Inisialisasi label Direktur/Manager berdasarkan tipe template.
 */
document.addEventListener('DOMContentLoaded', function () {

    /* ─── Checkbox Pilih Semua ─── */
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('input[name="ids[]"]').forEach(function (checkbox) {
                checkbox.checked = selectAll.checked;
            });
            updateButtonStates();
        });
    }

    /* ─── Listener Checkbox Individual ─── */
    document.querySelectorAll('input[name="ids[]"]').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('input[name="ids[]"]');
            const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

            if (selectAll) {
                selectAll.checked = checkboxes.length === checkedCheckboxes.length;
            }
            updateButtonStates();
        });
    });

    /* ─── Inisialisasi Status Tombol ─── */
    updateButtonStates();

    /* ─── Format Input Mata Uang ─── */
    document.querySelectorAll('.cash-out-amount-input').forEach(function (input) {
        if (input.value) {
            formatCurrencyInput(input);
        }
        input.addEventListener('input', function () {
            formatCurrencyInput(this);
        });
    });

    /* ─── Pencegahan Double Submit: Form Tambah ─── */
    const addForm = document.querySelector('#addModal form');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (!handleFormSubmit(submitBtn)) {
                e.preventDefault();
                return false;
            }
        });
    }

    /* ─── Pencegahan Double Submit: Form Edit ─── */
    document.querySelectorAll('[id^="editModal-"] form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (!handleFormSubmit(submitBtn)) {
                e.preventDefault();
                return false;
            }
        });
    });

    /* ─── Label Direktur / Manager ─── */
    initDirectorLabel();
});
