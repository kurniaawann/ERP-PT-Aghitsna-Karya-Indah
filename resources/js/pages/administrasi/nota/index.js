/**
 * Nota Administrasi - Index Page JavaScript
 *
 * Modul ini menangani:
 * - Format input currency Rupiah
 * - Manajemen item row (tambah/hapus)
 * - Perhitungan total item secara realtime
 * - Perhitungan grand total
 * - Select all checkbox
 * - Bulk delete button state
 * - Loading indicator pada tombol aksi
 * - Dropdown print handler
 */

/* ==========================================
 * HELPER: Submit Form Hapus
 * ========================================== */

/**
 * Fungsi untuk submit form hapus dengan loading indicator.
 * Dipanggil dari onclick pada modal konfirmasi hapus.
 *
 * @param {string} buttonId - ID tombol konfirmasi (default: confirm-btn-deleteModal)
 * @param {string} formId - ID form yang akan di-submit (default: deleteForm)
 * @param {string} loadingText - Teks loading saat proses (default: Menghapus...)
 */
window.submitDeleteForm = function (buttonId = 'confirm-btn-deleteModal', formId = 'deleteForm', loadingText = 'Menghapus...') {
    const deleteBtn = document.getElementById(buttonId);
    if (deleteBtn) {
        deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ' + loadingText;
        deleteBtn.disabled = true;
        deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }

    const form = document.getElementById(formId);
    if (form) {
        form.submit();
    }
};

/* ==========================================
 * FORMAT RUPIAH
 * ========================================== */

/**
 * Mengubah input angka menjadi format Rupiah dengan pemisah ribuan.
 * Contoh: 10000 -> "10.000"
 *
 * @param {HTMLInputElement} input - Element input yang akan diformat
 */
function formatCurrencyInput(input) {
    if (!input) return;

    // Ambil hanya digit dari value
    const numeric = String(input.value ?? '').replace(/[^\d]/g, '');

    // Format dengan Intl.NumberFormat Indonesia (pemisah ribuan titik)
    input.value = numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '';
}

/* ==========================================
 * MANAJEMEN ITEM ROW
 * ========================================== */

/**
 * Memperbarui visibilitas tombol hapus pada setiap item row.
 * Tombol hapus hanya ditampilkan jika ada lebih dari 1 item.
 *
 * @param {string} modalId - ID modal (addModal atau editModal-{id})
 */
function updateDeleteButtons(modalId) {
    const container = document.getElementById('itemsContainer-' + modalId);
    if (!container) return;

    const items = container.querySelectorAll('.item-row');
    const deleteButtons = container.querySelectorAll('.delete-btn');

    deleteButtons.forEach(function (btn) {
        btn.style.display = items.length > 1 ? 'flex' : 'none';
    });
}

/**
 * Menghitung total per item (Qty × Harga Satuan) dan menampilkannya.
 *
 * @param {HTMLElement} row - Element baris item
 * @returns {number} Total jumlah item
 */
function calculateItemTotal(row) {
    const qty = parseInt(row.querySelector('input[name="item_banyaknya[]"]')?.value) || 0;
    const harga = parseCurrencyInput(row.querySelector('input[name="item_harga_satuan[]"]')?.value);
    const total = qty * harga;

    const totalEl = row.querySelector('.item-total');
    if (totalEl) {
        totalEl.textContent = total ? new Intl.NumberFormat('id-ID').format(total) : '0';
    }

    return total;
}

/**
 * Menghitung grand total dari seluruh items dalam satu modal.
 * Grand total = Σ (Qty × Harga Satuan) untuk setiap item.
 *
 * @param {string} modalId - ID modal (addModal atau editModal-{id})
 * @returns {number} Grand total
 */
function calculateGrandTotal(modalId) {
    const container = document.getElementById('itemsContainer-' + modalId);
    if (!container) return 0;

    const rows = container.querySelectorAll('.item-row');
    let grandTotal = 0;

    rows.forEach(function (row) {
        const qty = parseInt(row.querySelector('input[name="item_banyaknya[]"]')?.value) || 0;
        const harga = parseCurrencyInput(row.querySelector('input[name="item_harga_satuan[]"]')?.value);
        grandTotal += qty * harga;
    });

    const grandTotalEl = document.getElementById('grandTotal-' + modalId);
    if (grandTotalEl) {
        grandTotalEl.textContent = new Intl.NumberFormat('id-ID').format(grandTotal);
    }

    return grandTotal;
}

/**
 * Mengikat event listener pada input qty dan harga satuan.
 * Listener akan memanggil calculateItemTotal dan calculateGrandTotal
 * setiap kali nilai berubah.
 *
 * @param {HTMLElement} row - Element baris item
 * @param {string} modalId - ID modal
 */
function attachItemListeners(row, modalId) {
    const qtyInput = row.querySelector('input[name="item_banyaknya[]"]');
    const hargaInput = row.querySelector('input[name="item_harga_satuan[]"]');

    function recalc() {
        calculateItemTotal(row);
        calculateGrandTotal(modalId);
    }

    if (qtyInput) {
        qtyInput.addEventListener('input', recalc);
    }

    if (hargaInput) {
        hargaInput.addEventListener('input', function () {
            formatCurrencyInput(this);
            recalc();
        });
    }
}

/**
 * Menambahkan baris item baru ke dalam container items.
 * Baris baru memiliki input kosong yang siap diisi oleh user.
 *
 * @param {string} modalId - ID modal (addModal atau editModal-{id})
 */
function addItemRow(modalId) {
    const container = document.getElementById('itemsContainer-' + modalId);
    if (!container) return;

    const newRow = document.createElement('div');
    newRow.className = 'item-row bg-surface-base border-2 border-border-strong rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow';
    newRow.innerHTML = `
        <div class="space-y-3">
            <div class="grid grid-cols-12 gap-3">
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-text-label mb-1.5">Qty <span class="text-error">*</span></label>
                    <input type="number" name="item_banyaknya[]" class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-center text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all" placeholder="0" min="1" required>
                </div>
                <div class="col-span-10">
                    <label class="block text-xs font-semibold text-text-label mb-1.5">Nama Barang <span class="text-error">*</span></label>
                    <input type="text" name="item_nama_barang[]" class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all" placeholder="Masukkan nama barang..." required>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-3">
                <div class="col-span-4">
                    <label class="block text-xs font-semibold text-text-label mb-1.5">Harga Satuan <span class="text-error">*</span></label>
                    <input type="text" name="item_harga_satuan[]" class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-right text-text-input price-input focus:ring-2 focus:ring-primary focus:border-primary transition-all" placeholder="0" required>
                </div>
                <div class="col-span-3">
                    <label class="block text-xs font-semibold text-text-label mb-1.5">Jumlah</label>
                    <div class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-right bg-surface-secondary text-text-input item-total">0</div>
                </div>
                <div class="col-span-5 flex items-end">
                    <button type="button" class="delete-btn w-full bg-btn-delete hover:bg-btn-delete-hover text-white px-3 py-2.5 rounded-lg text-sm font-medium shadow-sm transition-all duration-200 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-trash"></i>
                        <span>Hapus</span>
                    </button>
                </div>
            </div>
        </div>
    `;

    container.appendChild(newRow);

    // Pasang event listener pada baris baru
    attachItemListeners(newRow, modalId);

    // Pasang event listener tombol hapus
    var deleteBtn = newRow.querySelector('.delete-btn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function () {
            removeItemRow(this);
        });
    }

    // Perbarui visibilitas tombol hapus
    updateDeleteButtons(modalId);
}

/**
 * Menghapus baris item dari container.
 * Setelah penghapusan, grand total akan dihitung ulang.
 *
 * @param {HTMLElement} button - Tombol hapus yang diklik
 */
function removeItemRow(button) {
    const itemRow = button.closest('.item-row');
    if (!itemRow) return;

    const container = itemRow.parentElement;
    const modalId = container.id.replace('itemsContainer-', '');

    itemRow.remove();
    calculateGrandTotal(modalId);
    updateDeleteButtons(modalId);
}

/* ==========================================
 * LOADING INDICATOR
 * ========================================== */

/**
 * Mengaktifkan/menonaktifkan loading indicator pada tombol submit.
 *
 * @param {HTMLButtonElement} submitBtn - Tombol submit
 * @param {string} loadingText - Teks yang ditampilkan saat loading
 * @param {boolean} loading - true untuk aktifkan loading, false untuk nonaktifkan
 */
function setButtonLoading(submitBtn, loading, loadingText = 'Menyimpan...') {
    if (!submitBtn) return;

    if (loading) {
        // Simpan teks original
        if (!submitBtn.dataset.originalHtml) {
            submitBtn.dataset.originalHtml = submitBtn.innerHTML;
        }
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ' + loadingText;
        submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
    } else {
        submitBtn.disabled = false;
        submitBtn.innerHTML = submitBtn.dataset.originalHtml || submitBtn.innerHTML;
        submitBtn.classList.remove('opacity-70', 'cursor-not-allowed');
        delete submitBtn.dataset.originalHtml;
    }
}

/* ==========================================
 * PRINT SELECTED
 * ========================================== */

/**
 * Fungsi untuk print nota yang dipilih.
 * Mengumpulkan checkbox yang dicentang, mengirim via AJAX, dan download PDF.
 *
 * @param {HTMLButtonElement} btn - Tombol yang diklik
 * @returns {boolean} true jika proses dimulai
 */
function printSelected(btn) {
    var printRoute = document.getElementById('nota-print-selected-route');
    var route = printRoute ? printRoute.value : '';

    if (!route) return false;

    return sharedPrintSelected(route, btn);
}

// Expose ke global scope agar bisa dipanggil dari inline onclick di Blade
window.printSelected = printSelected;

/* ==========================================
 * INITIALIZATION
 * ========================================== */

document.addEventListener('DOMContentLoaded', function () {

    /* ─── Inisialisasi Tombol Hapus & Total untuk Modal Tambah ─── */
    updateDeleteButtons('addModal');
    calculateGrandTotal('addModal');

    /* ─── Inisialisasi Tombol Hapus & Total untuk Modal Edit ─── */
    document.querySelectorAll('[id^="itemsContainer-editModal-"]').forEach(function (container) {
        var modalId = container.id.replace('itemsContainer-', '');
        updateDeleteButtons(modalId);
        calculateGrandTotal(modalId);
    });

    /* ─── Inisialisasi Format Currency pada Input Optional Fields ─── */
    document.querySelectorAll('.price-input').forEach(function (input) {
        input.addEventListener('input', function () {
            formatCurrencyInput(this);
        });
    });

    /* ─── Pasang Event Listener pada Item Row yang Sudah Ada ─── */
    document.querySelectorAll('[id^="itemsContainer-"]').forEach(function (container) {
        var modalId = container.id.replace('itemsContainer-', '');
        container.querySelectorAll('.item-row').forEach(function (row) {
            attachItemListeners(row, modalId);

            // Pasang event listener tombol hapus yang sudah ada
            var deleteBtn = row.querySelector('.delete-btn');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function () {
                    removeItemRow(this);
                });
            }
        });
    });

    /* ─── Tombol "Tambah Item": Pasang Event Listener ─── */
    // Modal Tambah: tombol dengan id addItemBtn-addModal
    var addItemBtnAdd = document.getElementById('addItemBtn-addModal');
    if (addItemBtnAdd) {
        addItemBtnAdd.addEventListener('click', function () {
            addItemRow('addModal');
        });
    }

    // Modal Edit: tombol dengan class addItemBtn
    document.querySelectorAll('.addItemBtn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            // Cari container items terdekat untuk menentukan modalId
            var container = btn.closest('.mb-4')?.querySelector('[id^="itemsContainer-"]');
            if (container) {
                var modalId = container.id.replace('itemsContainer-', '');
                addItemRow(modalId);
            }
        });
    });

    /* ─── Form Submit dengan Loading Indicator: Modal Tambah ─── */
    var addForm = document.querySelector('#addModal form');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            var submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                setButtonLoading(submitBtn, true, 'Menyimpan...');
            }
        });
    }

    /* ─── Form Submit dengan Loading Indicator: Modal Edit ─── */
    document.querySelectorAll('[id^="editModal-"] form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                setButtonLoading(submitBtn, true, 'Memperbarui...');
            }
        });
    });

    /* ─── Select All Checkbox ─── */
    var selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            var checkboxes = document.querySelectorAll('input[name="ids[]"]');
            checkboxes.forEach(function (checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateButtonStates();
        });
    }

    /* ─── Individual Checkbox Listener ─── */
    document.querySelectorAll('input[name="ids[]"]').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            var selectAll = document.getElementById('selectAll');
            var checkboxes = document.querySelectorAll('input[name="ids[]"]');
            var checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

            if (selectAll) {
                selectAll.checked = checkboxes.length === checkedCheckboxes.length;
            }
            updateButtonStates();
        });
    });

    /* ─── Inisialisasi Status Tombol ─── */
    updateButtonStates();

    /* ─── Delete Form - Prevent Double Submission ─── */
    var deleteForm = document.getElementById('deleteForm');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function (e) {
            var submitBtn = document.getElementById('confirm-btn-deleteModal');
            if (submitBtn && submitBtn.disabled) {
                e.preventDefault();
                return false;
            }
        });
    }

    /* ─── Reset State Saat Modal Backdrop Diklik ─── */
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('modal-backdrop')) {
            // Reset semua tombol submit
            document.querySelectorAll('button[type="submit"]').forEach(function (btn) {
                btn.disabled = false;
                btn.classList.remove('opacity-70', 'cursor-not-allowed');
                if (btn.dataset.originalHtml) {
                    btn.innerHTML = btn.dataset.originalHtml;
                }
            });
        }
    });
});

/**
 * Memperbarui status tombol hapus dan tombol print berdasarkan checkbox yang dipilih.
 */
function updateButtonStates() {
    var deleteButton = document.getElementById('delete-button');
    var printSelectedItem = document.getElementById('printSelectedItem');
    var selectedCountText = document.getElementById('selectedCountText');
    var checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');
    var count = checkedCheckboxes.length;

    if (selectedCountText) {
        selectedCountText.textContent = count;
    }

    if (deleteButton) {
        if (count > 0) {
            deleteButton.disabled = false;
            deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            deleteButton.disabled = true;
            deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }

    if (printSelectedItem) {
        if (count > 0) {
            printSelectedItem.classList.remove('hidden');
        } else {
            printSelectedItem.classList.add('hidden');
        }
    }
}
