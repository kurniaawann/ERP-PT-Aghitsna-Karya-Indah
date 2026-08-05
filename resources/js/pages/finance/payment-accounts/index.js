/* global handleFormSubmit, resetFormSubmitState, openModal, closeModal, showToast */

// ==========================================
// TOGGLE STATUS AKTIF
// ==========================================

/**
 * Toggle status aktif/nonaktif rekening pembayaran.
 * Mengirim form POST ke route toggle dengan CSRF token.
 *
 * Alur:
 * 1. Buat elemen <form> dinamis dengan method POST menuju
 *    /payment-accounts/{id}/toggle.
 * 2. Salin token CSRF dari #deleteForm sebagai hidden input _token.
 * 3. Append form ke <body> lalu submit — request dikirim sebagai
 *    full page submit, server men-toggle is_active lalu me-redirect
 *    kembali ke halaman index.
 *
 * @param  {number} accountId  ID rekening yang akan di-toggle
 */
function toggleActive(accountId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/payment-accounts/${accountId}/toggle`;

    // Ambil CSRF token dari deleteForm yang sudah ada di halaman
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = document.querySelector('#deleteForm input[name="_token"]')?.value || '';
    form.appendChild(csrfInput);

    document.body.appendChild(form);
    form.submit();
}

window.toggleActive = toggleActive;

// ==========================================
// HAPUS MASSAL
// ==========================================

/**
 * Reset tombol delete ke state semula.
 *
 * Alur: kembalikan innerHTML tombol #confirm-btn-deleteModal menjadi
 * "Ya, Hapus", aktifkan kembali, dan hapus class opacity/cursor-not-allowed.
 * Dipanggil setelah operasi hapus selesai atau gagal agar tombol tidak
 * terkunci dalam kondisi loading.
 */
function resetDeleteButton() {
    const btn = document.getElementById('confirm-btn-deleteModal');
    if (!btn) return;
    btn.innerHTML = 'Ya, Hapus';
    btn.disabled = false;
    btn.classList.remove('opacity-70', 'cursor-not-allowed');
}

/**
 * Submit form bulk delete via AJAX untuk menghindari page flicker.
 * Menangani response: success (reload), usage_error (modal), atau error (toast).
 */
function submitDeleteForm() {
    const checkboxes = document.querySelectorAll('.account-checkbox:checked');

    if (checkboxes.length === 0) {
        showToast('Pilih minimal satu rekening untuk dihapus', 'error');
        return;
    }

    // Tampilkan loading pada tombol delete
    const deleteBtn = document.getElementById('confirm-btn-deleteModal');
    if (deleteBtn) {
        deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
        deleteBtn.disabled = true;
        deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }

    const form = document.getElementById('deleteForm');
    if (!form) {
        resetDeleteButton();
        return;
    }

    const formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        closeModal('deleteModal');
        resetDeleteButton();

        if (data.success) {
            window.location.reload();
        } else if (data.type === 'usage_error') {
            document.getElementById('errorMessage').textContent = data.message;
            openModal('errorModal');
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(() => {
        closeModal('deleteModal');
        resetDeleteButton();
        showToast('Terjadi kesalahan saat menghapus rekening.', 'error');
    });
}

window.submitDeleteForm = submitDeleteForm;

// ==========================================
// DOM SIAP
// ==========================================

/**
 * Inisialisasi logika halaman saat DOM siap.
 *
 * Alur:
 * 1. Ikat checkbox pilih semua (#selectAll) dan update status tombol delete
 *    berdasarkan checkbox rekening yang dipilih.
 * 2. Ikat submit form modal Tambah dan semua modal Edit (cegah double submit).
 * 3. Jika ada #usageErrorData, tampilkan modal error "rekening masih digunakan".
 * 4. Reset status submit saat halaman dimuat ulang (pageshow).
 */
document.addEventListener('DOMContentLoaded', function () {

    // ==========================================
    // CHECKBOX PILIH SEMUA
    // ==========================================

    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.account-checkbox');
    const deleteButton = document.getElementById('delete-button');

    /**
     * Update state tombol delete berdasarkan checkbox yang dipilih.
     */
    function updateDeleteButton() {
        const checkedCount = document.querySelectorAll('.account-checkbox:checked').length;
        if (deleteButton) {
            deleteButton.disabled = checkedCount === 0;
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateDeleteButton();
        });
    }

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            const someChecked = Array.from(checkboxes).some(cb => cb.checked);

            if (selectAll) {
                selectAll.checked = allChecked;
                selectAll.indeterminate = !allChecked && someChecked;
            }

            updateDeleteButton();
        });
    });

    updateDeleteButton();

    // ==========================================
    // PENANGANAN SUBMIT FORM — MODAL TAMBAH
    // ==========================================

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

    // ==========================================
    // PENANGANAN SUBMIT FORM — MODAL EDIT
    // ==========================================

    document.querySelectorAll('[id^="editModal-"] form').forEach(form => {
        form.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (!handleFormSubmit(submitBtn)) {
                e.preventDefault();
                return false;
            }
        });
    });

    // ==========================================
    // MODAL ERROR — Rekening masih digunakan
    // ==========================================

    const usageErrorData = document.getElementById('usageErrorData');
    if (usageErrorData) {
        document.getElementById('errorMessage').textContent = usageErrorData.dataset.message;
        openModal('errorModal');
    }

    // ==========================================
    // RESET STATE SUBMIT SAAT HALAMAN DIMUAT ULANG
    // ==========================================

    window.addEventListener('pageshow', () => resetFormSubmitState());
});
