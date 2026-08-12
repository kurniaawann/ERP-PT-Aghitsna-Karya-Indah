/**
 * DO Semen - Index Page JavaScript
 *
 * Modul ini menangani:
 * - Format input currency Rupiah (harga modal & harga tiap baris semen)
 * - Baris Data Semen dinamis (tambah/hapus baris) pada modal tambah/edit
 * - Select all checkbox
 * - Bulk delete button state
 * - Loading state saat submit form modal tambah/edit
 */

/* global submitDeleteForm - dipanggil dari inline onclick pada Blade modal */
/**
 * Submit form hapus (bulk delete) dengan loading state pada tombol konfirmasi.
 *
 * @returns {void}
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
 * Mengikat event listener untuk format currency pada input harga.
 *
 * @param {HTMLInputElement} input
 */
function bindCurrencyInput(input) {
    input.addEventListener('input', function () {
        this.value = formatRupiah(parseCurrencyInput(this.value));
    });
}

/**
 * Mengikat pencegahan double-submit pada form modal tambah/edit.
 *
 * @param {HTMLFormElement|null} form Form modal (opsional)
 */
function bindFormSubmit(form) {
    if (form) {
        form.addEventListener('submit', function (e) {
            const submitBtnEl = form.querySelector('button[type="submit"]');
            const originalText = submitBtnEl.innerHTML;
            if (!handleFormSubmit(submitBtnEl, originalText)) {
                e.preventDefault();
                return false;
            }
        });
    }
}

/**
 * Bangun elemen baris Data Semen baru di dalam sebuah <tbody>.
 *
 * @param {HTMLTableSectionElement} tbody
 * @returns {HTMLTableRowElement}
 */
function buildCementRow(tbody) {
    const index = tbody.querySelectorAll('.cement-row').length;
    const tr = document.createElement('tr');
    tr.className = 'cement-row';

    const cell = function (html) {
        const td = document.createElement('td');
        td.className = 'p-1';
        td.innerHTML = html;
        return td;
    };

    tr.appendChild(cell('<input type="date" name="cements[' + index + '][tanggal]" class="w-full border rounded p-2 text-sm">'));
    tr.appendChild(cell('<input type="text" name="cements[' + index + '][nama_proyek]" class="w-full border rounded p-2 text-sm" placeholder="Nama proyek" required maxlength="255" oninvalid="this.setCustomValidity(\'Nama proyek tidak boleh kosong\')" oninput="this.setCustomValidity(\'\')">'));
    tr.appendChild(cell('<input type="number" name="cements[' + index + '][jumlah]" value="0" min="0" class="w-full border rounded p-2 text-sm text-center" placeholder="0" required>'));
    tr.appendChild(cell('<input type="text" name="cements[' + index + '][harga]" value="Rp 0" class="w-full border rounded p-2 text-sm text-right cement-harga" placeholder="Rp 0" required inputmode="numeric">'));
    tr.appendChild(cell('<input type="date" name="cements[' + index + '][tanggal_lunas]" class="w-full border rounded p-2 text-sm">'));

    const actionTd = document.createElement('td');
    actionTd.className = 'p-1 text-center';
    actionTd.innerHTML = '<button type="button" class="remove-row-btn text-error hover:text-red-700 px-2 py-1 rounded" title="Hapus baris"><i class="fa-solid fa-trash w-3 h-3"></i></button>';
    tr.appendChild(actionTd);

    return tr;
}

/**
 * Inisialisasi halaman DO Semen.
 */
document.addEventListener('DOMContentLoaded', function () {

    // ─── Format Harga & Anti Double-Submit pada Modal Tambah ──────────
    const addForm = document.querySelector('#addModal form');
    const addHargaModal = document.getElementById('add-harga-modal');

    if (addHargaModal) bindCurrencyInput(addHargaModal);
    bindFormSubmit(addForm);

    // ─── Format Harga Modal & Anti Double-Submit pada Modal Edit ─────
    document.querySelectorAll('[id^="edit-harga-modal-"]').forEach(function (hargaModalInput) {
        const itemNo = hargaModalInput.id.replace('edit-harga-modal-', '');
        bindCurrencyInput(hargaModalInput);
        bindFormSubmit(document.querySelector('#editModal-' + itemNo + ' form'));
    });

    // ─── Baris Data Semen Dinamis ────────────────────────────────────

    /**
     * Tambahkan satu baris Data Semen ke dalam <tbody> lalu ikat format
     * currency dan tombol hapus.
     *
     * @param {HTMLTableSectionElement} tbody
     */
    function addRow(tbody) {
        const tr = buildCementRow(tbody);
        tbody.appendChild(tr);

        const hargaInput = tr.querySelector('.cement-harga');
        if (hargaInput) bindCurrencyInput(hargaInput);

        const removeBtn = tr.querySelector('.remove-row-btn');
        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                tr.remove();
            });
        }
    }

    /**
     * Ikat tombol hapus pada baris yang sudah ada di dalam <tbody>.
     *
     * @param {HTMLTableSectionElement} tbody
     */
    function bindExistingRows(tbody) {
        tbody.querySelectorAll('.cement-row').forEach(function (tr) {
            const hargaInput = tr.querySelector('.cement-harga');
            if (hargaInput) bindCurrencyInput(hargaInput);

            const removeBtn = tr.querySelector('.remove-row-btn');
            if (removeBtn) {
                removeBtn.addEventListener('click', function () {
                    tr.remove();
                });
            }
        });
    }

    // Tombol "Tambah Baris" pada modal tambah.
    const addRowBtn = document.getElementById('add-row-btn');
    if (addRowBtn) {
        const addRowsBody = document.getElementById('cement-rows');
        if (addRowsBody) bindExistingRows(addRowsBody);
        addRowBtn.addEventListener('click', function () {
            if (addRowsBody) addRow(addRowsBody);
        });
    }

    // Tombol "Tambah Baris" pada setiap modal edit (class .add-row-btn).
    document.querySelectorAll('.add-row-btn').forEach(function (btn) {
        const tbody = btn.closest('.mb-3').querySelector('.cement-rows');
        if (tbody) {
            bindExistingRows(tbody);
            btn.addEventListener('click', function () {
                addRow(tbody);
            });
        }
    });

    // ─── Select All Checkbox ──────────────────────────────────────────
    const selectAllCheckbox = document.getElementById('selectAll');
    const itemCheckboxes = document.querySelectorAll('input[name="selected_items[]"]');
    const deleteButton = document.getElementById('delete-button');

    function updateDeleteButtonState() {
        const anyChecked = Array.from(itemCheckboxes).some(function (cb) {
            return cb.checked;
        });
        if (deleteButton) {
            deleteButton.disabled = !anyChecked;
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            itemCheckboxes.forEach(function (checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateDeleteButtonState();
        });
    }

    itemCheckboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            if (!this.checked) {
                selectAllCheckbox.checked = false;
            } else {
                const allChecked = Array.from(itemCheckboxes).every(function (cb) {
                    return cb.checked;
                });
                selectAllCheckbox.checked = allChecked;
            }
            updateDeleteButtonState();
        });
    });

    updateDeleteButtonState();
});
