/**
 * Nota Administrasi - JavaScript Halaman Index
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
 * PEMBANTU: Submit Form Hapus
 * ========================================== */

/**
 * Fungsi untuk submit form hapus dengan loading indicator.
 * Dipanggil dari onclick pada modal konfirmasi hapus.
 *
 * Alur:
 * 1. Ambil tombol konfirmasi sesuai buttonId.
 * 2. Ganti isi tombol dengan spinner + loadingText, disable, dan
 *    beri class opacity/cursor-not-allowed.
 * 3. Submit form formId (default #deleteForm).
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
 * Alur:
 * 1. Ambil nilai input, buang semua karakter non-digit.
 * 2. Format dengan Intl.NumberFormat('id-ID') agar muncul pemisah
 *    ribuan titik; kosongkan input bila tidak ada digit.
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
 * MANAJEMEN BARIS ITEM
 * ========================================== */

/**
 * Memperbarui visibilitas tombol hapus pada setiap item row.
 *
 * Alur:
 * 1. Cari container #itemsContainer-{modalId}.
 * 2. Hitung jumlah baris .item-row dan tombol .delete-btn.
 * 3. Tampilkan tombol hapus (display: flex) bila jumlah item > 1;
 *    sembunyikan bila hanya tersisa 1 baris (harus selalu ada
 *    minimal 1 item).
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
 * Menentukan tipe nota dari container items.
 *
 * Tipe dibaca dari atribut data-tipe pada container:
 * - 'sewa_jual' (default): kolom item_banyaknya[] / item_harga_satuan[]
 * - 'proyek': kolom item_quantity[] / item_harga[]
 *
 * @param {HTMLElement} container - Container items (#itemsContainer-...)
 * @returns {string} 'sewa_jual' atau 'proyek'
 */
function getItemTipe(container) {
    return container ? container.dataset.tipe || 'sewa_jual' : 'sewa_jual';
}

/**
 * Menghitung total per item dan menampilkannya.
 *
 * Tipe sewa_jual: Qty (item_banyaknya) × Harga Satuan (item_harga_satuan)
 * Tipe proyek: Quantity (item_quantity) × Harga (item_harga)
 *
 * @param {HTMLElement} row - Element baris item
 * @returns {number} Total jumlah item
 */
function calculateItemTotal(row) {
    const tipe = getItemTipe(row.closest('[id^="itemsContainer-"]'));

    let qty, harga;
    if (tipe === 'proyek') {
        qty = parseInt(row.querySelector('input[name="item_quantity[]"]')?.value) || 0;
        harga = parseCurrencyInput(row.querySelector('input[name="item_harga[]"]')?.value);
    } else {
        qty = parseInt(row.querySelector('input[name="item_banyaknya[]"]')?.value) || 0;
        harga = parseCurrencyInput(row.querySelector('input[name="item_harga_satuan[]"]')?.value);
    }

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
 * Alur:
 * 1. Cari container #itemsContainer-{modalId}; return 0 bila tak ada.
 * 2. Iterasi seluruh baris .item-row, akumulasi qty × hargaSatuan
 *    (harga di-parse via parseCurrencyInput()).
 * 3. Tampilkan hasil di elemen #grandTotal-{modalId} dengan format
 *    ribuan Indonesia.
 * 4. Kembalikan grand total.
 *
 * Catatan: ini hanya preview realtime. Saat submit, NotaService
 * menghitung itemsTotal, biaya tambahan, dan PPN di server.
 *
 * @param {string} modalId - ID modal (addModal atau editModal-{id})
 * @returns {number} Grand total
 */
function calculateGrandTotal(modalId) {
    const container = document.getElementById('itemsContainer-' + modalId);
    if (!container) return 0;

    const tipe = getItemTipe(container);
    const rows = container.querySelectorAll('.item-row');
    let grandTotal = 0;

    rows.forEach(function (row) {
        let qty, harga;
        if (tipe === 'proyek') {
            qty = parseInt(row.querySelector('input[name="item_quantity[]"]')?.value) || 0;
            harga = parseCurrencyInput(row.querySelector('input[name="item_harga[]"]')?.value);
        } else {
            qty = parseInt(row.querySelector('input[name="item_banyaknya[]"]')?.value) || 0;
            harga = parseCurrencyInput(row.querySelector('input[name="item_harga_satuan[]"]')?.value);
        }
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
 *
 * Alur:
 * 1. Ambil input qty (item_banyaknya[]) dan harga satuan
 *    (item_harga_satuan[]) pada baris tsb.
 * 2. Definisikan recalc() yang memanggil calculateItemTotal(row) lalu
 *    calculateGrandTotal(modalId) — sehingga total item dan grand total
 *    selalu sinkron.
 * 3. Jika input qty ada, pasang listener 'input' -> recalc.
 * 4. Jika input harga ada, pasang listener 'input' yang terlebih dahulu
 *    memformat nilai dengan formatCurrencyInput() (pemisah ribuan)
 *    kemudian memanggil recalc.
 *
 * @param {HTMLElement} row - Element baris item
 * @param {string} modalId - ID modal
 */
function attachItemListeners(row, modalId) {
    const container = document.getElementById('itemsContainer-' + modalId);
    const tipe = getItemTipe(container);

    let qtyInput, hargaInput;
    if (tipe === 'proyek') {
        qtyInput = row.querySelector('input[name="item_quantity[]"]');
        hargaInput = row.querySelector('input[name="item_harga[]"]');
    } else {
        qtyInput = row.querySelector('input[name="item_banyaknya[]"]');
        hargaInput = row.querySelector('input[name="item_harga_satuan[]"]');
    }

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
 * Alur:
 * 1. Cari container #itemsContainer-{modalId}.
 * 2. Buat elemen div.item-row berisi input qty, nama barang, harga
 *    satuan (class price-input), display total (.item-total), dan
 *    tombol hapus (.delete-btn).
 * 3. Append ke container.
 * 4. Pasang listener item (attachItemListeners) dan tombol hapus.
 * 5. Perbarui visibilitas tombol hapus tiap baris (updateDeleteButtons).
 *
 * @param {string} modalId - ID modal (addModal atau editModal-{id})
 */
function addItemRow(modalId) {
    const container = document.getElementById('itemsContainer-' + modalId);
    if (!container) return;

    const tipe = getItemTipe(container);
    const newRow = document.createElement('div');
    newRow.className = 'item-row bg-surface-base border-2 border-border-strong rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow';

    if (tipe === 'proyek') {
        newRow.innerHTML = `
        <div class="space-y-3">
            <div class="grid grid-cols-12 gap-3">
                <div class="col-span-3">
                    <label class="block text-xs font-semibold text-text-label mb-1.5">Quantity <span class="text-error">*</span></label>
                    <input type="number" name="item_quantity[]" class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-center text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all" placeholder="0" min="1" required>
                </div>
                <div class="col-span-3">
                    <label class="block text-xs font-semibold text-text-label mb-1.5">Satuan</label>
                    <input type="text" name="item_satuan[]" class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-center text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all" placeholder="unit" maxlength="50">
                </div>
                <div class="col-span-6">
                    <label class="block text-xs font-semibold text-text-label mb-1.5">Nama Barang <span class="text-error">*</span></label>
                    <input type="text" name="item_nama_barang[]" class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all" placeholder="Masukkan nama barang..." required>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-3">
                <div class="col-span-4">
                    <label class="block text-xs font-semibold text-text-label mb-1.5">Harga <span class="text-error">*</span></label>
                    <input type="text" name="item_harga[]" class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-right text-text-input price-input focus:ring-2 focus:ring-primary focus:border-primary transition-all" placeholder="0" required>
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
    } else {
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
    }

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
 *
 * Alur:
 * 1. Cari baris .item-row terdekat dari tombol; batal bila tak ada.
 * 2. Tentukan modalId dari id container (itemsContainer-{modalId}).
 * 3. Hapus baris, hitung ulang grand total, dan perbarui visibilitas
 *    tombol hapus (minimal 1 baris tersisa).
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
 * INDIKATOR LOADING
 * ========================================== */

/**
 * Mengaktifkan/menonaktifkan loading indicator pada tombol submit.
 *
 * Alur saat loading = true:
 * 1. Simpan HTML asli tombol ke dataset.originalHtml (sekali saja).
 * 2. Disable tombol, ganti isi dengan spinner + loadingText, dan
 *    beri class opacity/cursor-not-allowed.
 *
 * Alur saat loading = false:
 * 1. Kembalikan HTML asli, enable tombol, hapus class loading,
 *    lalu buang dataset.originalHtml.
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
 * CETAK TERPILIH
 * ========================================== */

/**
 * Fungsi untuk print nota yang dipilih.
 * Mengumpulkan checkbox yang dicentang, mengirim via AJAX, dan download PDF.
 *
 * Alur:
 * 1. Baca route print terpilih dari hidden input #nota-print-selected-route.
 * 2. Delegasikan proses ke sharedPrintSelected() (dari shared/print.js)
 *    yang menangani pengumpulan checkbox, request AJAX, dan download PDF.
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
 * INISIALISASI
 * ========================================== */

/**
 * Inisialisasi seluruh interaksi halaman nota saat DOM siap.
 *
 * Bagian penting:
 *
 * 1. Inisialisasi tombol hapus & grand total untuk modal add dan semua
 *    modal edit (#itemsContainer-editModal-*).
 *
 * 2. Format currency pada semua input .price-input saat mengetik.
 *
 * 3. Pasang attachItemListeners() + listener tombol hapus pada setiap
 *    baris item yang sudah ada di semua container items.
 *
 * 4. Tombol "Tambah Item" dengan DUA selector:
 *    - Modal Tambah: tombol ber-id #addItemBtn-addModal
 *      -> addItemRow('addModal').
 *    - Modal Edit: semua tombol ber-class .addItemBtn. Karena bisa ada
 *      banyak modal edit, modalId ditentukan dengan mencari container
 *      items terdekat (btn.closest('.mb-4') lalu cari
 *      [id^="itemsContainer-"]), agar tombol menambah item pada modal
 *      yang benar.
 *
 * 5. Loading indicator pada submit form add & edit (setButtonLoading).
 *
 * 6. Checkbox "Pilih Semua", checkbox individual, dan updateButtonStates().
 *
 * 7. Pencegahan double submit pada form hapus.
 *
 * 8. Reset tombol submit saat backdrop modal diklik.
 */
document.addEventListener('DOMContentLoaded', function () {

    /* ─── Inisialisasi Tombol Hapus & Total untuk Modal Tambah ─── */
    updateDeleteButtons('addModal');
    calculateGrandTotal('addModal');
    updateDeleteButtons('addModalProyek');
    calculateGrandTotal('addModalProyek');

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

    // Modal Tambah Proyek: tombol dengan id addItemBtn-addModalProyek
    var addItemBtnAddProyek = document.getElementById('addItemBtn-addModalProyek');
    if (addItemBtnAddProyek) {
        addItemBtnAddProyek.addEventListener('click', function () {
            addItemRow('addModalProyek');
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
    document.querySelectorAll('#addModal form, #addModalProyek form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                setButtonLoading(submitBtn, true, 'Menyimpan...');
            }
        });
    });

    /* ─── Form Submit dengan Loading Indicator: Modal Edit ─── */
    document.querySelectorAll('[id^="editModal-"] form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                setButtonLoading(submitBtn, true, 'Memperbarui...');
            }
        });
    });

    /* ─── Checkbox Pilih Semua ─── */
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

    /* ─── Listener Checkbox Individual ─── */
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

    /* ─── Form Hapus - Cegah Pengiriman Ganda ─── */
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
 *
 * Alur:
 * 1. Hitung jumlah checkbox ids[] tercentang.
 * 2. Perbarui teks #selectedCountText dengan jumlah terpilih.
 * 3. Tombol #delete-button: aktif bila count > 0, nonaktif + class
 *    opacity bila tidak ada pilihan.
 * 4. Tombol #printSelectedItem: ditampilkan bila ada pilihan,
 *    disembunyikan bila tidak ada.
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
