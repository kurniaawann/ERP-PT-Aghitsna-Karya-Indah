/**
 * Shared Delete Form Helper
 *
 * Submit form hapus dengan loading indicator pada tombol konfirmasi.
 * Dipanggil dari onclick pada modal konfirmasi hapus massal.
 */

function submitDeleteForm(buttonId = 'confirm-btn-deleteModal', formId = 'deleteForm', loadingText = 'Menghapus...') {
    const deleteBtn = document.getElementById(buttonId);
    if (deleteBtn) {
        deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ' + loadingText;
        deleteBtn.disabled = true;
        deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }

    const form = document.getElementById(formId);
    if (form) {
        form.submit();
        return true;
    }

    return false;
}

window.submitDeleteForm = submitDeleteForm;
