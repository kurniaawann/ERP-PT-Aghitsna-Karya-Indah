/**
 * Halaman Index Divisi - Modul JavaScript
 *
 * Menangani semua fungsionalitas interaktif untuk halaman Data Divisi:
 * - Checkbox Pilih Semua / Batalkan Pilih Semua
 * - Manajemen status checkbox individu dan enable/disable tombol hapus
 * - Pengiriman form hapus massal dengan status memuat
 * - Penanganan submit form Tambah/Edit dengan pencegahan pengiriman ganda
 *
 * Fungsi yang dipanggil dari atribut HTML inline diekspos ke window
 * karena Vite memuat JS sebagai ES module, bukan global.
 */

// ==========================================
// CHECKBOX PILIH SEMUA
// ==========================================

/**
 * Memperbarui Status Tombol Hapus
 * Aktifkan/nonaktifkan tombol hapus berdasarkan jumlah checkbox yang dipilih.
 * Jika tidak ada yang dipilih, tombol disabled dengan opacity rendah.
 */
function updateDeleteButtonState() {
    const deleteButton = document.getElementById('delete-button');
    const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

    if (!deleteButton) return;

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

// ==========================================
// KONFIRMASI HAPUS MASSAL
// ==========================================

/**
 * Mengirim Form Hapus
 * Menampilkan loading spinner pada tombol konfirmasi lalu mengirim form hapus.
 *
 * Ditugaskan ke window karena dipanggil dari atribut onclick inline
 * di modal konfirmasi hapus (Vite memuat JS sebagai ES module, bukan global).
 */
window.submitDeleteForm = function () {
    const deleteBtn = document.getElementById('confirm-btn-deleteModal');
    if (deleteBtn) {
        deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
        deleteBtn.disabled = true;
        deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }
    document.getElementById('deleteForm').submit();
};

// ==========================================
// PENANGANAN SUBMIT FORM TAMBAH/EDIT
// ==========================================

/**
 * Menangani Submit Modal Tambah
 * Mencegah double submit pada form tambah divisi.
 */
function initAddFormHandler() {
    const addForm = document.querySelector('#addModal form');
    if (!addForm) return;

    addForm.addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        if (!handleFormSubmit(submitBtn)) {
            e.preventDefault();
            return false;
        }
    });
}

/**
 * Handle Edit Modal Submits
 * Mencegah double submit pada semua form edit divisi.
 */
function initEditFormHandlers() {
    document.querySelectorAll('[id^="editModal-"] form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (!handleFormSubmit(submitBtn)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

// ==========================================
// INITIALIZATION
// ==========================================

/**
 * Initialize all division page functionality on DOM ready.
 */
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('selectAll');

    if (selectAll) {
        // Select All Checkbox
        // Ketika checkbox "Pilih Semua" diklik, centang/batalkan semua
        // checkbox individu dan perbarui status tombol hapus.
        selectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="ids[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            updateDeleteButtonState();
        });
    }

    // Individual Checkbox
    // Ketika checkbox individu diklik, perbarui status checkbox "Pilih Semua"
    // (centang semua jika semua tercentang, batalkan jika ada yang belum).
    document.querySelectorAll('input[name="ids[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('input[name="ids[]"]');
            const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

            if (selectAll) {
                selectAll.checked = checkboxes.length === checkedCheckboxes.length;
            }
            updateDeleteButtonState();
        });
    });

    // Initialize delete button state on page load
    updateDeleteButtonState();

    initAddFormHandler();
    initEditFormHandlers();
});
