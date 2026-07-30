/**
 * Manajemen User - Index Page JavaScript
 *
 * Modul ini menangani:
 * - Select all checkbox dan state tombol hapus
 * - Submit form dengan loading indicator (double-submit prevention)
 * - Bulk delete dengan loading indicator
 */

/* global handleFormSubmit */

/**
 * Fungsi global yang dipanggil dari inline onclick pada Blade modal delete.
 * Menampilkan loading indicator pada tombol konfirmasi dan mengirim form hapus.
 */
window.submitDeleteForm = function () {
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
};

/**
 * Mengupdate status tombol hapus berdasarkan checkbox yang dipilih.
 * Tombol aktif jika minimal satu checkbox dicentang.
 */
function updateDeleteButtonState() {
    const checkboxes = document.querySelectorAll('input[name="ids[]"]');
    const deleteButton = document.getElementById('delete-button');
    const anyChecked = Array.from(checkboxes).some(function (cb) {
        return cb.checked;
    });

    if (deleteButton) {
        deleteButton.disabled = !anyChecked;
    }
}

/**
 * Mengikat loading indicator pada form submit.
 * Menggunakan handleFormSubmit() dari shared module untuk double-submit prevention.
 *
 * @param {HTMLFormElement} form
 * @param {string} loadingText  Teks yang ditampilkan saat loading
 */
function bindFormLoading(form, loadingText) {
    if (!form) return;

    form.addEventListener('submit', function (e) {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            const originalText = submitBtn.innerHTML;
            if (!handleFormSubmit(submitBtn, originalText, loadingText)) {
                e.preventDefault();
                return false;
            }
        }
    });
}

/**
 * Inisialisasi halaman Manajemen User.
 * Dipanggil setelah DOM selesai dimuat.
 */
document.addEventListener('DOMContentLoaded', function () {

    // ─── Select All Checkbox ────────────────────────────────────────────
    const selectAllCheckbox = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('input[name="ids[]"]');

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            checkboxes.forEach(function (checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateDeleteButtonState();
        });
    }

    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            if (!this.checked) {
                selectAllCheckbox.checked = false;
            } else {
                const allChecked = Array.from(checkboxes).every(function (cb) {
                    return cb.checked;
                });
                selectAllCheckbox.checked = allChecked;
            }
            updateDeleteButtonState();
        });
    });

    updateDeleteButtonState();

    // ─── Loading State pada Form Tambah User ───────────────────────────
    const addModal = document.getElementById('addModal');
    if (addModal) {
        const addForm = addModal.querySelector('form');
        bindFormLoading(addForm, 'Menyimpan...');
    }

    // ─── Loading State pada semua Form Edit User ───────────────────────
    document.querySelectorAll('[id^="editModal-"]').forEach(function (modal) {
        const form = modal.querySelector('form');
        bindFormLoading(form, 'Menyimpan...');
    });
});
