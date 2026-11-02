/**
 * Laporan Keuangan Proyek — Halaman Daftar
 *
 * Fitur:
 * - Hapus massal (submit form hapus)
 * - Checkbox pilih semua + status tombol "Hapus yang dipilih"
 *
 * Form Tambah Transaksi / Edit pindah ke halaman sendiri
 * (resources/js/pages/report/project-financial-report/form.js).
 */

// ============================================================
// HAPUS MASSAL
// ============================================================

/**
 * Submit bulk delete form dengan loading indicator.
 *
 * @param {string} [modalId] ID modal konfirmasi hapus (memuat tombol konfirmasi).
 * @param {string} [formId]  ID form hapus massal yang akan di-submit.
 */
function submitDeleteForm(modalId = 'deleteModal', formId = 'deleteForm') {
    const deleteBtn = document.getElementById('confirm-btn-' + modalId);
    if (deleteBtn) {
        deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
        deleteBtn.disabled = true;
        deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }

    const form = document.getElementById(formId);
    if (form) {
        form.submit();
    }
}
window.submitDeleteForm = submitDeleteForm;

// ============================================================
// INISIALISASI
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

    // ============================================================
    // CHECKBOX PILIH SEMUA (per form hapus massal)
    // ============================================================

    document.querySelectorAll('form[id="deleteForm"], form[id^="deleteForm-"]').forEach(function (form) {
        const suffix = form.id === 'deleteForm' ? '' : form.id.slice('deleteForm'.length);
        const selectAllCheckbox = form.querySelector('#selectAll' + suffix);
        const itemCheckboxes = form.querySelectorAll('input[name="selected_items[]"], input[name="selected_recaps[]"]');
        const deleteButton = document.getElementById('delete-button' + suffix);

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
    });

    // ============================================================
    // RESET STATE SUBMIT SAAT HALAMAN DIMUAT ULANG
    // ============================================================

    window.addEventListener('pageshow', function () {
        resetFormSubmitState();
    });
});
