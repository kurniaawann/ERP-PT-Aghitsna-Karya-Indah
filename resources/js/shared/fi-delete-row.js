/**
 * Hapus satu data dari menu Aksi ⋮ (komponen x-fi.delete-row-modal).
 *
 * - Klik elemen [data-delete-row="{id}"] → isi #delete-row-value, tampilkan
 *   label data (data-delete-row-label) di #delete-row-label, lalu buka modal
 *   konfirmasi #deleteRowModal.
 * - Tombol konfirmasi modal memanggil submitDeleteRowForm() → submit
 *   #deleteRowForm (DELETE berisi satu id) dengan loading state.
 */

document.addEventListener('click', function (e) {
    const trigger = e.target.closest('[data-delete-row]');
    if (!trigger) return;

    const input = document.getElementById('delete-row-value');
    if (!input) return;

    input.value = trigger.dataset.deleteRow;

    const labelEl = document.getElementById('delete-row-label');
    if (labelEl) labelEl.textContent = trigger.dataset.deleteRowLabel || trigger.dataset.deleteRow;

    openModal('deleteRowModal');
});

/* global submitDeleteRowForm - dipanggil dari onclick tombol konfirmasi x-fi.delete-row-modal */
/**
 * Submit form hapus satu data dengan loading state pada tombol konfirmasi.
 *
 * @returns {void}
 */
window.submitDeleteRowForm = function () {
    const form = document.getElementById('deleteRowForm');
    const input = document.getElementById('delete-row-value');
    if (!form || !input || !input.value) return;

    const deleteBtn = document.getElementById('confirm-btn-deleteRowModal');
    if (deleteBtn) {
        deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
        deleteBtn.disabled = true;
        deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }

    form.submit();
};
