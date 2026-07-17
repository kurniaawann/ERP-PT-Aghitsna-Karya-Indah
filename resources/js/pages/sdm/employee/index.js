/**
 * Logika halaman Karyawan (Data Karyawan).
 *
 * Menangani:
 * - Checkbox Pilih Semua / Batalkan Pilih Semua
 * - Manajemen status checkbox individu dan tombol hapus
 * - Pengiriman formulir hapus massal dengan status memuat
 * - Format mata uang upah harian pada input
 * - Penanganan pengiriman formulir Tambah/Edit dengan pencegahan pengiriman ganda
 */

// ==========================================
// Format Mata Uang untuk Input Upah Harian
// ==========================================

/**
 * Memformat nilai input field sebagai mata uang IDR (misalnya, 150000 -> "150.000").
 * Menghapus semua karakter non-digit dan memformat ulang.
 */
function formatCurrencyInput(input) {
    if (!input) return;

    const numeric = input.value.replace(/[^\d]/g, '');
    input.value = numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '';
}

// ==========================================
// Pilih Semua / Checkbox Individu
// ==========================================

/**
 * Mengaktifkan atau menonaktifkan tombol hapus berdasarkan jumlah checkbox yang dipilih.
 */
function updateDeleteButtonState() {
    const deleteButton = document.getElementById('delete-button');
    const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

    if (checkedCheckboxes.length > 0) {
        deleteButton.disabled = false;
        deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
        deleteButton.classList.add('hover:bg-btn-delete-hover');
    } else {
        deleteButton.disabled = true;
        deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
        deleteButton.classList.remove('hover:bg-btn-delete-hover');
    }
}

/**
 * Mengirim formulir hapus massal dengan spinner memuat pada tombol konfirmasi.
 */
function submitDeleteForm() {
    const deleteBtn = document.getElementById('confirm-btn-deleteModal');
    if (deleteBtn) {
        deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
        deleteBtn.disabled = true;
        deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }
    document.getElementById('deleteForm').submit();
}

// ==========================================
// Pendengar Event
// ==========================================

document.addEventListener('DOMContentLoaded', function () {
    window.submitDeleteForm = submitDeleteForm;

    // Checkbox Pilih Semua
    var selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            var checkboxes = document.querySelectorAll('input[name="ids[]"]');
            checkboxes.forEach(function (checkbox) {
                checkbox.checked = this.checked;
            }, this);
            updateDeleteButtonState();
        });
    }

    // Individual Checkboxes
    document.querySelectorAll('input[name="ids[]"]').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            var allCheckboxes = document.querySelectorAll('input[name="ids[]"]');
            var checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');
            var selectAllCheckbox = document.getElementById('selectAll');

            if (selectAllCheckbox) {
                selectAllCheckbox.checked = allCheckboxes.length === checkedCheckboxes.length;
            }
            updateDeleteButtonState();
        });
    });

    // Initialize delete button state
    updateDeleteButtonState();

    // Initialize searchable selects (from shared global)
    if (typeof window.initSearchableSelects === 'function') {
        window.initSearchableSelects();
    }

    // Daily Wage Currency Formatting
    document.querySelectorAll('.daily-wage-input').forEach(function (input) {
        if (input.value) {
            formatCurrencyInput(input);
        }

        input.addEventListener('input', function () {
            formatCurrencyInput(this);
        });
    });

    // Add Form Submit (with double-submit prevention)
    var addForm = document.querySelector('#addModal form');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            var submitBtn = this.querySelector('button[type="submit"]');
            if (!window.handleFormSubmit(submitBtn)) {
                e.preventDefault();
            }
        });
    }

    // Edit Form Submits (with double-submit prevention)
    document.querySelectorAll('[id^="editModal-"] form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var submitBtn = this.querySelector('button[type="submit"]');
            if (!window.handleFormSubmit(submitBtn)) {
                e.preventDefault();
            }
        });
    });
});
