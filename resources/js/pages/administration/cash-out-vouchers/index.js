/**
 * ============================================================
 * MODUL BUKTI KAS KELUAR - INDEX PAGE
 * ============================================================
 *
 * File JavaScript modular untuk halaman daftar bukti kas keluar.
 * File ini menangani interaksi UI seperti:
 * - Select All checkbox dan sinkronisasi checkbox individual
 * - Pengelolaan status tombol aksi (hapus, cetak)
 * - Format input jumlah dengan format Rupiah
 * - Pencegahan double submit pada form tambah/edit
 *
 * Catatan: Saat ini JavaScript masih di-include langsung di
 * Blade partial. File ini disediakan sebagai referensi untuk
 * migrasi ke arsitektur modular di masa depan.
 * ============================================================
 */

/**
 * Memperbarui status tombol aksi berdasarkan jumlah data yang dipilih.
 *
 * Fungsi ini mengaktifkan/menonaktifkan tombol Hapus dan
 * menampilkan/menyembunyikan tombol Cetak Terpilih sesuai
 * dengan jumlah checkbox yang dicentang.
 *
 * @returns {void}
 */
function updateButtonStates() {
    const deleteButton = document.getElementById('delete-button');
    const printSelectedItem = document.getElementById('printSelectedItem');
    const selectedCountText = document.getElementById('selectedCountText');
    const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');
    const count = checkedCheckboxes.length;

    // Aktifkan/nonaktifkan tombol Hapus
    if (count > 0) {
        deleteButton.disabled = false;
        deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
        deleteButton.classList.add('hover:bg-btn-delete-hover');
    } else {
        deleteButton.disabled = true;
        deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
        deleteButton.classList.remove('hover:bg-btn-delete-hover');
    }

    // Tampilkan/sembunyikan tombol Cetak Terpilih
    if (printSelectedItem) {
        if (count > 0) {
            printSelectedItem.classList.remove('hidden');
        } else {
            printSelectedItem.classList.add('hidden');
        }
    }

    // Perbarui teks jumlah data terpilih
    if (selectedCountText) {
        selectedCountText.textContent = count;
    }
}

/**
 * Format input mata uang dengan format Indonesia (Rupiah).
 *
 * Menghapus semua karakter non-angka lalu memformat
 * menggunakan Intl.NumberFormat('id-ID').
 *
 * @param {HTMLInputElement} input  Element input yang akan diformat
 * @returns {void}
 */
function formatCurrencyInput(input) {
    if (!input) return;

    const numeric = input.value.replace(/[^\d]/g, '');
    input.value = numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '';
}

/**
 * Inisialisasi halaman bukti kas keluar.
 *
 * Fungsi ini dipanggil saat DOM sudah dimuat sepenuhnya.
 * Mengatur event listener untuk checkbox, input mata uang,
 * dan form submission.
 *
 * @returns {void}
 */
function initCashOutProofPage() {
    // Inisialisasi checkbox "Pilih Semua"
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('input[name="ids[]"]');
            checkboxes.forEach(function (checkbox) {
                checkbox.checked = this.checked;
            }.bind(this));
            updateButtonStates();
        });
    }

    // Inisialisasi checkbox individual
    document.querySelectorAll('input[name="ids[]"]').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            const allCheckboxes = document.querySelectorAll('input[name="ids[]"]');
            const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');
            selectAll.checked = allCheckboxes.length === checkedCheckboxes.length;
            updateButtonStates();
        });
    });

    // Inisialisasi status tombol
    updateButtonStates();

    // Format input mata uang
    document.querySelectorAll('.cash-out-amount-input').forEach(function (input) {
        if (input.value) {
            formatCurrencyInput(input);
        }
        input.addEventListener('input', function () {
            formatCurrencyInput(this);
        });
    });

    // Pencegahan double submit: Form Tambah
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

    // Pencegahan double submit: Form Edit
    document.querySelectorAll('[id^="editModal-"] form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (!handleFormSubmit(submitBtn)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

// Jalankan inisialisasi saat DOM dimuat
document.addEventListener('DOMContentLoaded', initCashOutProofPage);
