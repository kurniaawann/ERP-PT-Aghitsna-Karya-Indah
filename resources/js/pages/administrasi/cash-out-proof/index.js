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

function initDirectorLabel() {
    const bindLabel = function (templateSelect, labelEl, inputEl) {
        if (!templateSelect || !labelEl || !inputEl) return;

        const apply = function () {
            if (templateSelect.value === 'hollow') {
                labelEl.textContent = 'Manager';
                inputEl.placeholder = 'SISWORO SUBENO (default)';
            } else {
                labelEl.textContent = 'Direktur';
                inputEl.placeholder = 'Zulkarnain,ST.,MT (default)';
            }
        };

        templateSelect.addEventListener('change', apply);
        apply();
    };

    const addTemplate = document.getElementById('addTemplateType');
    if (addTemplate) {
        bindLabel(addTemplate, document.getElementById('addDirectorLabel'), document.getElementById('addDirectorInput'));
    }

    document.querySelectorAll('[id^="editTemplateType-"]').forEach(function (select) {
        const suffix = select.id.replace('editTemplateType-', '');
        bindLabel(select, document.getElementById('editDirectorLabel-' + suffix), document.getElementById('editDirectorInput-' + suffix));
    });
}

/* ==========================================
 * INISIALISASI
 * ========================================== */

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
