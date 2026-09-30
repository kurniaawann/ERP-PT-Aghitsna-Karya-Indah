/**
 * Invoice Barang - JavaScript Halaman Index
 *
 * Modul ini menangani:
 * - Searchable dropdown untuk pemilihan barang (mode tambah & edit)
 * - Toggle "Dari Stok" (mode tambah & edit)
 * - Validasi harga modal < harga jual
 * - Validasi stok mencukupi
 * - Tambah/hapus item rows secara dinamis
 * - Submit form dengan JSON items
 * - Checkbox select all untuk hapus massal
 */

/* global parseCurrencyInput, handleFormSubmit, resetFormSubmitState */

// ─── Currency Helper (kompatibel dengan handler oninput inline di Blade) ─────

/**
 * Format input angka sebagai mata uang Indonesia (Rp X.XXX).
 *
 * Hanya menyisakan digit lalu diformat ke ribuan (id-ID) saat pengguna
 * mengetik. Kompatibel dengan handler oninput inline di Blade.
 *
 * @param  {HTMLInputElement} input  Element input yang akan diformat
 */
function formatCurrencyInput(input) {
    if (!input) return;
    const numeric = String(input.value ?? '').replace(/[^\d]/g, '');
    input.value = numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '';
}
window.formatCurrencyInput = formatCurrencyInput;

// ─── Pembuat Opsi Item ─────────────────────────────────────────────────────

/**
 * Baca data barang dari blok <script type="application/json" id="itemsDataJson">,
 * dengan fallback ke window._itemsData.
 * @returns {Array}
 */
function readItemsData() {
    const el = document.getElementById('itemsDataJson');
    if (el) {
        try {
            return JSON.parse(el.textContent || '[]') || [];
        } catch (e) {
            // abaikan, fallback ke window._itemsData
        }
    }
    return window._itemsData || [];
}

/**
 * Membangun HTML opsi barang untuk dropdown pencarian.
 *
 * Alur:
 * - Baca data barang dari backend (array berisi id_item,
 *   name_item, capital_price, selling_price, quantity).
 * - Kelas opsi memakai prefix '-edit' untuk mode edit, else 'barang-option'.
 * - Setiap opsi menyimpan data barang di data-* attribute (data-value,
 *   data-name, data-capital, data-selling, data-stock, data-search) yang
 *   nantinya dipakai initSearchableDropdown / initSearchableDropdownEdit
 *   untuk auto-fill field baris.
 * - Kembalian berupa gabungan HTML string (join).
 *
 * @param  {string} prefix  '' untuk add modal, '-edit' untuk edit modal
 * @return {string} HTML string berisi daftar opsi barang
 */
function buildBarangOptionsHtml(prefix) {
    const items = readItemsData();
    if (!Array.isArray(items) || items.length === 0) return '';

    const optionClass = prefix === '-edit' ? 'barang-option-edit' : 'barang-option';

    return items.map(function (item) {
        const name = escapeHtml(item.name_item);
        return '<div class="p-3 hover:bg-primary-light cursor-pointer border-b border-border-light ' + optionClass + '" ' +
            'data-value="' + escapeHtml(item.id_item) + '" data-name="' + name + '" ' +
            'data-capital="' + item.capital_price + '" data-selling="' + item.selling_price + '" ' +
            'data-stock="' + item.quantity + '" data-search="' + escapeHtml(String(item.name_item).toLowerCase()) + '">' +
            '<div class="font-medium text-text-heading">' + name + '</div>' +
            '<div class="text-xs text-text-secondary mt-1">Stok: <span class="font-semibold text-primary">' + item.quantity + '</span> unit</div>' +
        '</div>';
    }).join('');
}

/**
 * Escape teks agar aman disisipkan ke HTML/atribut (mis. nama barang
 * yang mengandung tanda kutip seperti: Semen "Tiga Roda").
 *
 * @param  {*} value
 * @return {string}
 */
function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// ─── Status Tombol Simpan ────────────────────────────────────────────────────

/**
 * Cek apakah satu baris item valid untuk disubmit.
 *
 * - Baris "Dari Stok": harga diambil backend dari master barang (tidak bisa
 *   diubah di form), jadi yang dicek hanya qty tidak melebihi stok.
 * - Baris manual: harga modal harus lebih kecil dari harga jual (bila harga
 *   jual sudah diisi).
 *
 * @param  {HTMLElement} row     Elemen .barang-item-row / .barang-item-row-edit
 * @param  {string}      suffix  '' untuk modal tambah, '-edit' untuk modal edit
 * @return {boolean}
 */
function isItemRowValid(row, suffix) {
    const fromStock = row.querySelector('.barang-from-stock' + suffix)?.checked;

    if (fromStock) {
        const qty = parseInt(row.querySelector('.barang-item-qty' + suffix)?.value) || 0;
        const stock = parseInt(row.dataset.stock) || 0;
        return stock === 0 || qty <= stock;
    }

    const capital = parseCurrencyInput(row.querySelector('.barang-item-capital' + suffix)?.value) || 0;
    const selling = parseCurrencyInput(row.querySelector('.barang-item-selling' + suffix)?.value) || 0;
    return selling === 0 || capital < selling;
}

/**
 * Tampilkan/sembunyikan peringatan harga pada satu baris. Baris "Dari Stok"
 * tidak pernah menampilkan peringatan harga.
 *
 * @param  {HTMLElement} row
 * @param  {string}      suffix  '' atau '-edit'
 */
function updatePriceWarning(row, suffix) {
    const warning = row.querySelector('.barang-price-warning' + suffix);
    if (!warning) return;

    const fromStock = row.querySelector('.barang-from-stock' + suffix)?.checked;
    const capital = parseCurrencyInput(row.querySelector('.barang-item-capital' + suffix)?.value) || 0;
    const selling = parseCurrencyInput(row.querySelector('.barang-item-selling' + suffix)?.value) || 0;

    warning.classList.toggle('hidden', fromStock || !(capital >= selling && selling > 0));
}

/**
 * Aktif/nonaktifkan tombol submit beserta gaya visualnya.
 *
 * @param  {HTMLButtonElement|null} submitBtn
 * @param  {boolean}                enabled
 */
function setSubmitEnabled(submitBtn, enabled) {
    if (!submitBtn) return;
    submitBtn.disabled = !enabled;
    submitBtn.classList.toggle('opacity-50', !enabled);
    submitBtn.classList.toggle('cursor-not-allowed', !enabled);
}

/**
 * Hitung ulang status tombol Simpan modal ADD dari SELURUH baris item.
 *
 * Dipanggil setiap ada perubahan (input harga/qty, toggle Dari Stok, pilih
 * barang, tambah/hapus baris) sehingga tombol tidak terkunci permanen
 * setelah baris yang bermasalah diperbaiki atau dihapus.
 */
function refreshAddSubmitState() {
    const rows = document.querySelectorAll('#barang-items-list-add .barang-item-row');
    const allValid = Array.from(rows).every(function (row) { return isItemRowValid(row, ''); });
    setSubmitEnabled(document.getElementById('submit-btn-addModal'), allValid);
}

/**
 * Hitung ulang status tombol Simpan pada satu modal EDIT.
 *
 * @param  {string} invoiceNumber  Nomor invoice untuk identifikasi modal
 */
function refreshEditSubmitState(invoiceNumber) {
    const container = document.getElementById('barang-items-list-edit-' + invoiceNumber);
    if (!container) return;

    const rows = container.querySelectorAll('.barang-item-row-edit');
    const allValid = Array.from(rows).every(function (row) { return isItemRowValid(row, '-edit'); });
    setSubmitEnabled(document.getElementById('submit-btn-editModal-' + invoiceNumber), allValid);
}

/**
 * Pastikan minimal 1 rekening pembayaran dicentang di dalam modal.
 *
 * Bila belum ada rekening sama sekali, submit tidak diblok (PDF otomatis
 * memakai rekening aktif). Rekening hasil "Rekening Baru" ikut terhitung
 * karena pengecekan memakai name, bukan class.
 *
 * @param  {HTMLElement|null} modal
 * @return {boolean}
 */
function hasPaymentAccountSelected(modal) {
    const checkboxes = modal ? modal.querySelectorAll('[name="selected_payment_accounts[]"]') : [];
    if (checkboxes.length === 0) return true;
    return Array.from(checkboxes).some(function (cb) { return cb.checked; });
}

// ─── Submit Form Hapus ───────────────────────────────────────────────────────

/**
 * Submit form hapus massal dengan indikator loading pada tombol konfirmasi.
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

// ─── MODE TAMBAH: Dropdown yang Dapat Dicari ────────────────────────────────

/**
 * Inisialisasi dropdown barang yang dapat dicari untuk satu baris (mode ADD).
 *
 * Alur:
 * - Saat input pencarian fokus → dropdown ditampilkan.
 * - Saat mengetik → opsi difilter berdasarkan data-search (nama barang
 *   lowercase); bila tidak ada hasil tampilkan .barang-no-results.
 * - Saat opsi diklik → AUTO-FILL field baris:
 *   - searchInput = nama barang, hiddenInput (kode/id_item) = data-value.
 *   - capitalInput & sellingInput diisi harga modal/jual lalu diformat Rp.
 *   - row.dataset.stock = stok barang (dipakai validasi stok realtime).
 * - Klik di luar baris → dropdown ditutup.
 *
 * @param  {HTMLElement} row  Elemen .barang-item-row yang diinisialisasi
 */
function initSearchableDropdown(row) {
    const searchInput = row.querySelector('.barang-search-input');
    const dropdown = row.querySelector('.barang-dropdown');
    const options = row.querySelectorAll('.barang-option');
    const noResults = row.querySelector('.barang-no-results');
    const hiddenInput = row.querySelector('.barang-select-hidden');
    const nameInput = row.querySelector('.barang-item-name');
    const capitalInput = row.querySelector('.barang-item-capital');
    const sellingInput = row.querySelector('.barang-item-selling');

    if (!searchInput) return;

    searchInput.addEventListener('focus', function () {
        dropdown.classList.remove('hidden');
    });

    searchInput.addEventListener('input', function () {
        const searchTerm = this.value.toLowerCase();
        let hasResults = false;

        options.forEach(function (option) {
            const searchText = option.dataset.search || '';
            if (searchText.includes(searchTerm)) {
                option.style.display = 'block';
                hasResults = true;
            } else {
                option.style.display = 'none';
            }
        });

        const itemOptionsDiv = row.querySelector('.barang-options');
        if (hasResults) {
            noResults.classList.add('hidden');
            itemOptionsDiv.classList.remove('hidden');
        } else {
            noResults.classList.remove('hidden');
            itemOptionsDiv.classList.add('hidden');
        }
    });

    options.forEach(function (option) {
        option.addEventListener('click', function () {
            const value = this.dataset.value;
            const name = this.dataset.name;
            const capital = this.dataset.capital;
            const selling = this.dataset.selling;
            const stock = this.dataset.stock || 0;

            searchInput.value = name || '';
            hiddenInput.value = value || '';
            row.dataset.stock = stock;

            if (value) {
                capitalInput.value = capital;
                sellingInput.value = selling;
                formatCurrencyInput(capitalInput);
                formatCurrencyInput(sellingInput);
            }

            dropdown.classList.add('hidden');

            // Cek ulang stok (qty vs stok barang terpilih) & status tombol Simpan.
            row.querySelector('.barang-item-qty')?.dispatchEvent(new Event('input'));
            refreshAddSubmitState();
        });
    });

    document.addEventListener('click', function (e) {
        if (!row.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
}

// ─── MODE TAMBAH: Toggle Dari Stok ───────────────────────────────────────────

/**
 * Toggle mode "Dari Stok" pada baris item (mode ADD).
 *
 * Saat dicentang:
 * - Tampilkan dropdown pencarian barang dan sembunyikan input nama manual.
 * - Harga modal/jual menjadi readonly (diambil dari data barang yang dipilih).
 * Saat tidak dicentang:
 * - Tampilkan kembali input nama manual, kosongkan pilihan dropdown, dan
 *   kembalikan harga modal/jual menjadi editable.
 */
function toggleStockHandler() {
    const row = this.closest('.barang-item-row');
    const selectWrapper = row.querySelector('.barang-select-wrapper');
    const nameInput = row.querySelector('.barang-item-name');
    const capitalInput = row.querySelector('.barang-item-capital');
    const sellingInput = row.querySelector('.barang-item-selling');

    if (this.checked) {
        selectWrapper.style.display = 'block';
        nameInput.style.display = 'none';
        nameInput.value = '';
        nameInput.removeAttribute('required');
        capitalInput.readOnly = true;
        sellingInput.readOnly = true;
    } else {
        selectWrapper.style.display = 'none';
        const searchInput = row.querySelector('.barang-search-input');
        const hiddenInput = row.querySelector('.barang-select-hidden');
        if (searchInput) searchInput.value = '';
        if (hiddenInput) hiddenInput.value = '';
        nameInput.style.display = 'block';
        nameInput.setAttribute('required', '');
        capitalInput.readOnly = false;
        sellingInput.readOnly = false;
    }

    updatePriceWarning(row, '');
    refreshAddSubmitState();
}

// ─── MODE TAMBAH: Hapus Item ─────────────────────────────────────────────────

/**
 * Hapus baris item (mode ADD).
 *
 * Menjaga minimal 1 baris: bila tersisa ≤ 1 baris, tampilkan alert dan
 * batalkan penghapusan.
 *
 * @param  {Event} e  Event klik
 */
function removeItemHandler(e) {
    e.preventDefault();
    const itemsContainer = document.getElementById('barang-items-list-add');
    const remainingItems = itemsContainer.querySelectorAll('.barang-item-row');

    if (remainingItems.length <= 1) {
        alert('Minimal harus ada 1 item!');
        return;
    }

    this.closest('.barang-item-row').remove();

    // Baris bermasalah yang dihapus tidak boleh membuat tombol Simpan tetap terkunci.
    refreshAddSubmitState();
}

// ─── MODE TAMBAH: Validasi Harga ─────────────────────────────────────────────

/**
 * Inisialisasi validasi harga modal < harga jual (mode ADD).
 *
 * Alur:
 * - Setiap input harga modal/jual memicu validatePrices().
 * - Jika capital ≥ selling (dan selling > 0) pada baris manual: tampilkan
 *   .barang-price-warning.
 * - Status tombol submit dihitung ulang dari SEMUA baris lewat
 *   refreshAddSubmitState().
 *
 * @param  {HTMLElement} row  Elemen .barang-item-row yang divalidasi
 */
function initPriceValidation(row) {
    const capitalInput = row.querySelector('.barang-item-capital');
    const sellingInput = row.querySelector('.barang-item-selling');

    if (!capitalInput || !sellingInput) return;

    function validatePrices() {
        updatePriceWarning(row, '');
        refreshAddSubmitState();
    }

    capitalInput.addEventListener('input', validatePrices);
    sellingInput.addEventListener('input', validatePrices);
}

// ─── MODE TAMBAH: Validasi Stok ──────────────────────────────────────────────

/**
 * Inisialisasi validasi stok realtime per baris (mode ADD).
 *
 * Alur:
 * - Dipicu pada input qty dan perubahan checkbox "Dari Stok".
 * - Bila "Dari Stok" aktif dan stok tersedia (row.dataset.stock) > 0 dan
 *   qty > stok:
 *   - Tampilkan .barang-stock-warning dengan teks berisi sisa stok.
 * - Status tombol submit dihitung ulang dari SEMUA baris lewat
 *   refreshAddSubmitState() (stok & harga sekaligus, tanpa konflik).
 *
 * @param  {HTMLElement} row  Elemen .barang-item-row yang divalidasi
 */
function initStockValidation(row) {
    const qtyInput = row.querySelector('.barang-item-qty');
    const fromStockCheckbox = row.querySelector('.barang-from-stock');
    const stockWarning = row.querySelector('.barang-stock-warning');

    if (!qtyInput || !fromStockCheckbox || !stockWarning) return;

    function validateStock() {
        const isFromStock = fromStockCheckbox.checked;
        const qty = parseInt(qtyInput.value) || 0;
        const availableStock = parseInt(row.dataset.stock) || 0;
        const isOverStock = isFromStock && availableStock > 0 && qty > availableStock;

        stockWarning.classList.toggle('hidden', !isOverStock);
        if (isOverStock) {
            const warningText = stockWarning.querySelector('.barang-stock-warning-text');
            if (warningText) {
                warningText.textContent =
                    'Stok tersedia: ' + availableStock + ' unit. Qty (' + qty + ') melebihi stok yang tersedia!';
            }
        }

        refreshAddSubmitState();
    }

    qtyInput.addEventListener('input', validateStock);
    fromStockCheckbox.addEventListener('change', validateStock);
}

// ─── MODE TAMBAH: Pasang Listener ────────────────────────────────────────────

/**
 * Pasang listener item pada semua baris mode ADD.
 *
 * Alur:
 * - Tombol .remove-barang-item → removeItemHandler (listener lama dilepas
 *   dulu agar tidak dobel saat baris baru ditambahkan).
 * - Checkbox .barang-from-stock → toggleStockHandler.
 * - Setiap baris .barang-item-row → initSearchableDropdown(row).
 */
function attachItemListeners() {
    document.querySelectorAll('.remove-barang-item').forEach(function (btn) {
        btn.removeEventListener('click', removeItemHandler);
        btn.addEventListener('click', removeItemHandler);
    });

    document.querySelectorAll('.barang-from-stock').forEach(function (checkbox) {
        checkbox.removeEventListener('change', toggleStockHandler);
        checkbox.addEventListener('change', toggleStockHandler);
    });

    document.querySelectorAll('.barang-item-row').forEach(function (row) {
        initSearchableDropdown(row);
    });
}

// ─── MODE EDIT: Dropdown yang Dapat Dicari ───────────────────────────────────

/**
 * Inisialisasi dropdown barang yang dapat dicari (mode EDIT).
 *
 * Mirip initSearchableDropdown() tetapi memakai selector ber-suffix "-edit"
 * dan field name items[][...]. Saat opsi diklik, selain auto-fill harga
 * dan stok, id_item juga disimpan ke .barang-id-item-hidden untuk dikirim
 * saat submit.
 *
 * @param  {HTMLElement} row  Elemen .barang-item-row-edit yang diinisialisasi
 */
function initSearchableDropdownEdit(row) {
    const searchInput = row.querySelector('.barang-search-input-edit');
    const dropdown = row.querySelector('.barang-dropdown-edit');
    const options = row.querySelectorAll('.barang-option-edit');
    const noResults = row.querySelector('.barang-no-results-edit');
    const hiddenInput = row.querySelector('.barang-select-hidden-edit');
    const nameInput = row.querySelector('.barang-item-name-edit');
    const capitalInput = row.querySelector('.barang-item-capital-edit');
    const sellingInput = row.querySelector('.barang-item-selling-edit');
    const idItemHidden = row.querySelector('.barang-id-item-hidden');

    if (!searchInput) return;

    searchInput.addEventListener('focus', function () {
        dropdown.classList.remove('hidden');
    });

    searchInput.addEventListener('input', function () {
        const searchTerm = this.value.toLowerCase();
        let hasResults = false;

        options.forEach(function (option) {
            const searchText = option.dataset.search || '';
            if (searchText.includes(searchTerm)) {
                option.style.display = 'block';
                hasResults = true;
            } else {
                option.style.display = 'none';
            }
        });

        const itemOptionsDiv = row.querySelector('.barang-options-edit');
        if (hasResults) {
            noResults.classList.add('hidden');
            itemOptionsDiv.classList.remove('hidden');
        } else {
            noResults.classList.remove('hidden');
            itemOptionsDiv.classList.add('hidden');
        }
    });

    options.forEach(function (option) {
        option.addEventListener('click', function () {
            const value = this.dataset.value;
            const name = this.dataset.name;
            const capital = this.dataset.capital;
            const selling = this.dataset.selling;
            const stock = this.dataset.stock || 0;

            searchInput.value = name || '';
            hiddenInput.value = value || '';

            if (value) {
                capitalInput.value = capital;
                sellingInput.value = selling;
                idItemHidden.value = value;
                row.dataset.stock = stock;

                formatCurrencyInput(capitalInput);
                formatCurrencyInput(sellingInput);
            }

            dropdown.classList.add('hidden');

            // Cek ulang stok (qty vs stok barang terpilih) & status tombol Update.
            row.querySelector('.barang-item-qty-edit')?.dispatchEvent(new Event('input'));
            refreshEditSubmitStateForRow(row);
        });
    });

    document.addEventListener('click', function (e) {
        if (!row.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
}

// ─── MODE EDIT: Toggle Dari Stok ─────────────────────────────────────────────

/**
 * Toggle mode "Dari Stok" pada baris item (mode EDIT).
 *
 * Selain menyembunyikan/menampilkan dropdown & input nama, nilai hidden
 * .barang-from-stock-hidden dan .barang-id-item-hidden di-set ('true'/'false')
 * agar saat submit backend tahu item berasal dari stok atau manual.
 */
function toggleEditStockHandler() {
    const row = this.closest('.barang-item-row-edit');
    const selectWrapper = row.querySelector('.barang-select-wrapper-edit');
    const nameInput = row.querySelector('.barang-item-name-edit');
    const capitalInput = row.querySelector('.barang-item-capital-edit');
    const sellingInput = row.querySelector('.barang-item-selling-edit');
    const fromStockHidden = row.querySelector('.barang-from-stock-hidden');
    const idItemHidden = row.querySelector('.barang-id-item-hidden');

    if (this.checked) {
        selectWrapper.style.display = 'block';
        nameInput.style.display = 'none';
        nameInput.value = '';
        nameInput.removeAttribute('required');
        capitalInput.readOnly = true;
        sellingInput.readOnly = true;
        fromStockHidden.value = 'true';
    } else {
        selectWrapper.style.display = 'none';
        const searchInput = row.querySelector('.barang-search-input-edit');
        const hiddenInput = row.querySelector('.barang-select-hidden-edit');
        if (searchInput) searchInput.value = '';
        if (hiddenInput) hiddenInput.value = '';
        nameInput.style.display = 'block';
        nameInput.value = '';
        nameInput.setAttribute('required', '');
        capitalInput.readOnly = false;
        sellingInput.readOnly = false;
        fromStockHidden.value = 'false';
        if (idItemHidden) idItemHidden.value = '';
    }

    updatePriceWarning(row, '-edit');
    refreshEditSubmitStateForRow(row);
}

/**
 * Hitung ulang status tombol Update pada modal EDIT tempat baris berada.
 *
 * @param  {HTMLElement} row  Elemen .barang-item-row-edit
 */
function refreshEditSubmitStateForRow(row) {
    const container = row.closest('[id^="barang-items-list-edit-"]');
    if (container) {
        refreshEditSubmitState(container.id.replace('barang-items-list-edit-', ''));
    }
}

// ─── MODE EDIT: Validasi Harga ───────────────────────────────────────────────

/**
 * Inisialisasi validasi harga modal < harga jual (mode EDIT).
 *
 * Mirip initPriceValidation() tetapi menargetkan tombol submit per modal
 * (#submit-btn-editModal-{invoiceNumber}) dan hanya mengecek baris di dalam
 * container #barang-items-list-edit-{invoiceNumber}.
 *
 * @param  {HTMLElement} row            Elemen .barang-item-row-edit yang divalidasi
 * @param  {string}      invoiceNumber  Nomor invoice untuk identifikasi modal
 */
function initPriceValidationEdit(row, invoiceNumber) {
    const capitalInput = row.querySelector('.barang-item-capital-edit');
    const sellingInput = row.querySelector('.barang-item-selling-edit');

    if (!capitalInput || !sellingInput) return;

    function validatePrices() {
        updatePriceWarning(row, '-edit');
        refreshEditSubmitState(invoiceNumber);
    }

    capitalInput.addEventListener('input', validatePrices);
    sellingInput.addEventListener('input', validatePrices);
}

// ─── MODE EDIT: Validasi Stok ────────────────────────────────────────────────

/**
 * Inisialisasi validasi stok realtime per baris (mode EDIT).
 *
 * Sama seperti initStockValidation() tetapi per modal edit: warning
 * .barang-stock-warning-edit, tombol submit per modal, dan pengecekan
 * silang hanya pada baris di dalam modal tersebut.
 *
 * @param  {HTMLElement} row            Elemen .barang-item-row-edit yang divalidasi
 * @param  {string}      invoiceNumber  Nomor invoice untuk identifikasi modal
 */
function initStockValidationEdit(row, invoiceNumber) {
    const qtyInput = row.querySelector('.barang-item-qty-edit');
    const fromStockCheckbox = row.querySelector('.barang-from-stock-edit');
    const stockWarning = row.querySelector('.barang-stock-warning-edit');

    if (!qtyInput || !fromStockCheckbox || !stockWarning) return;

    function validateStock() {
        const isFromStock = fromStockCheckbox.checked;
        const qty = parseInt(qtyInput.value) || 0;
        const availableStock = parseInt(row.dataset.stock) || 0;
        const isOverStock = isFromStock && availableStock > 0 && qty > availableStock;

        stockWarning.classList.toggle('hidden', !isOverStock);
        if (isOverStock) {
            const warningText = stockWarning.querySelector('.barang-stock-warning-text-edit');
            if (warningText) {
                warningText.textContent =
                    'Stok tersedia: ' + availableStock + ' unit. Qty (' + qty + ') melebihi stok yang tersedia!';
            }
        }

        refreshEditSubmitState(invoiceNumber);
    }

    qtyInput.addEventListener('input', validateStock);
    fromStockCheckbox.addEventListener('change', validateStock);
}

// ─── MODE EDIT: Hapus Item ───────────────────────────────────────────────────

/**
 * Hapus baris item (mode EDIT) lalu re-index name field items.
 *
 * Alur:
 * - Baris minimal 1 dijaga (alert bila tersisa ≤ 1 baris).
 * - Hapus baris terdekat, lalu name seluruh input items[{index}][{field}]
 *   diurutkan ulang agar konsisten saat submit.
 *
 * @param  {Event} e  Event klik
 */
function removeEditItemHandler(e) {
    e.preventDefault();
    const itemsContainer = this.closest('[id^="barang-items-list-edit-"]');
    const remainingItems = itemsContainer.querySelectorAll('.barang-item-row-edit');

    if (remainingItems.length <= 1) {
        alert('Minimal harus ada 1 item!');
        return;
    }

    this.closest('.barang-item-row-edit').remove();

    itemsContainer.querySelectorAll('.barang-item-row-edit').forEach(function (row, index) {
        row.querySelectorAll('input[name^="items"]').forEach(function (input) {
            const match = input.name.match(/\[(\w+)\]$/);
            if (match) {
                const fieldName = match[1];
                input.name = 'items[' + index + '][' + fieldName + ']';
            }
        });
    });

    // Baris bermasalah yang dihapus tidak boleh membuat tombol Update tetap terkunci.
    refreshEditSubmitState(itemsContainer.id.replace('barang-items-list-edit-', ''));
}

// ─── MODE EDIT: Pasang Listener ──────────────────────────────────────────────

/**
 * Pasang listener item pada semua baris mode EDIT.
 *
 * Alur:
 * - Tombol .remove-barang-item-edit → removeEditItemHandler.
 * - Checkbox .barang-from-stock-edit → toggleEditStockHandler.
 * - Setiap baris .barang-item-row-edit → initSearchableDropdownEdit(row).
 */
function attachEditListeners() {
    document.querySelectorAll('.remove-barang-item-edit').forEach(function (btn) {
        btn.removeEventListener('click', removeEditItemHandler);
        btn.addEventListener('click', removeEditItemHandler);
    });

    document.querySelectorAll('.barang-from-stock-edit').forEach(function (checkbox) {
        checkbox.removeEventListener('change', toggleEditStockHandler);
        checkbox.addEventListener('change', toggleEditStockHandler);
    });

    document.querySelectorAll('.barang-item-row-edit').forEach(function (row) {
        initSearchableDropdownEdit(row);
    });
}

// ─── MODE TAMBAH: Tombol Tambah Item ─────────────────────────────────────────

/**
 * Inisialisasi tombol "Tambah Item" pada modal ADD.
 *
 * Membuat baris .barang-item-row baru lengkap dengan field qty, harga
 * modal/jual, dropdown pencarian, warning stok & harga. Setelah baris
 * dibuat, baris baru diinisialisasi: attachItemListeners() +
 * initPriceValidation() + initStockValidation().
 */
function initAddItemButton() {
    var addBtn = document.querySelector('.add-barang-item');
    if (!addBtn) return;

    addBtn.addEventListener('click', function (e) {
        e.preventDefault();
        var itemsContainer = document.getElementById('barang-items-list-add');
        var newItem = document.createElement('div');
        newItem.className = 'barang-item-row mb-3 p-3 border rounded bg-surface-secondary';
        newItem.innerHTML =
            '<div class="flex items-center gap-2 mb-2">' +
                '<label class="flex items-center gap-2">' +
                    '<input type="checkbox" class="barang-from-stock accent-primary">' +
                    '<span class="text-sm">Dari Stok</span>' +
                '</label>' +
            '</div>' +
            '<div class="relative mb-2 barang-select-wrapper" style="display: none;">' +
                '<input type="text" class="barang-search-input w-full border rounded-lg p-2 pr-10 focus:border-primary focus:ring-2 focus:ring-primary-light" placeholder="Cari barang..." autocomplete="off">' +
                '<i class="fa-solid fa-search absolute right-3 top-3 text-text-tertiary pointer-events-none"></i>' +
                '<div class="barang-dropdown absolute z-50 w-full bg-white border border-border-strong rounded-lg shadow-lg mt-1 max-h-64 overflow-y-auto hidden">' +
                    '<div class="barang-options">' +
                        '<div class="p-2 text-sm text-text-secondary hover:bg-surface-secondary cursor-pointer border-b" data-value="">-- Pilih Barang --</div>' +
                        buildBarangOptionsHtml('') +
                    '</div>' +
                    '<div class="barang-no-results p-4 text-center text-sm text-text-secondary hidden">' +
                        '<i class="fa-solid fa-search mb-2 text-2xl text-text-placeholder"></i>' +
                        '<p>Tidak ada barang ditemukan</p>' +
                    '</div>' +
                '</div>' +
            '</div>' +
            '<input type="hidden" class="barang-select-hidden">' +
            '<input type="text" class="barang-item-name w-full border rounded p-2 mb-2" placeholder="Nama Barang *" required ' +
                'oninvalid="this.setCustomValidity(\'Nama barang tidak boleh kosong\')" oninput="this.setCustomValidity(\'\')">' +
            '<div class="grid grid-cols-4 gap-2">' +
                '<input type="number" class="barang-item-qty border rounded p-2" placeholder="Qty *" required min="1" value="1" ' +
                    'oninvalid="this.setCustomValidity(\'Qty tidak boleh kosong\')" oninput="this.setCustomValidity(\'\')">' +
                '<input type="text" class="barang-item-satuan border rounded p-2" placeholder="Satuan" maxlength="50" ' +
                    'title="Satuan barang, contoh: zak, batang, pcs">' +
                '<input type="text" inputmode="numeric" class="barang-item-capital border rounded p-2" placeholder="Rp 0" required ' +
                    'oninvalid="this.setCustomValidity(\'Harga modal tidak boleh kosong\')" oninput="formatCurrencyInput(this); this.setCustomValidity(\'\')">' +
                '<input type="text" inputmode="numeric" class="barang-item-selling border rounded p-2" placeholder="Rp 0" required ' +
                    'oninvalid="this.setCustomValidity(\'Harga jual tidak boleh kosong\')" oninput="formatCurrencyInput(this); this.setCustomValidity(\'\')">' +
            '</div>' +
            '<p class="barang-stock-warning text-error text-sm mt-2 hidden">' +
                '<span class="font-semibold">Peringatan Stok:</span> <span class="barang-stock-warning-text">Stok Barang Tidak Cukup! Silahkan Sesuaikan Dengan Stok Yang Tersedia.</span>' +
            '</p>' +
            '<p class="barang-price-warning text-error text-sm mt-2 hidden">' +
                '<span class="font-semibold">Peringatan:</span> Harga modal tidak boleh lebih besar atau sama dengan harga jual!' +
            '</p>' +
            '<button type="button" class="remove-barang-item mt-2 bg-btn-delete text-white px-3 py-1 rounded hover:bg-btn-delete-hover w-full">' +
                '<i class="fa-solid fa-trash"></i> Hapus Item' +
            '</button>';

        itemsContainer.appendChild(newItem);
        attachItemListeners();
        initPriceValidation(newItem);
        initStockValidation(newItem);
    });
}

// ─── MODE TAMBAH: Submit Form ────────────────────────────────────────────────

/**
 * Inisialisasi submit form modal ADD dengan validasi lengkap.
 *
 * Alur:
 * - Validasi baris: baris manual dengan capital ≥ selling (dan selling > 0)
 *   atau baris stok dengan qty > stok → batalkan submit dan tampilkan alert.
 *   Harga baris "Dari Stok" tidak divalidasi (backend memakai harga master).
 * - Validasi rekening: bila ada rekening tapi belum ada yang dicentang,
 *   batalkan submit, tampilkan pesan, dan scroll ke bagian rekening.
 * - Serialisasi tiap baris ke item JSON: name_item, quantity, capital_price,
 *   selling_price, from_stock, id_item (id_item hanya diisi bila dari stok).
 * - Bila tidak ada item valid, batalkan submit (minimal 1 item lengkap).
 * - Tulis JSON ke #barang-items-json lalu panggil handleFormSubmit() untuk
 *   proteksi submit ganda.
 *
 * Referensi backend: app/Services/Finance/ItemInvoiceService.php
 * (normalizeInvoiceItems & processItemsForStore memvalidasi ulang + kurangi stok).
 */
function initAddFormSubmission() {
    var addModal = document.getElementById('addModal');
    if (!addModal) return;

    var addForm = addModal.querySelector('form');
    if (!addForm) return;

    addForm.addEventListener('submit', function (e) {
        var items = [];
        var itemRows = document.querySelectorAll('#barang-items-list-add .barang-item-row');

        var hasInvalidRow = Array.from(itemRows).some(function (row) {
            return !isItemRowValid(row, '');
        });

        if (hasInvalidRow) {
            e.preventDefault();
            alert('Periksa kembali item: harga modal harus lebih kecil dari harga jual dan qty tidak boleh melebihi stok.');
            return false;
        }

        if (!validatePaymentSelection()) {
            e.preventDefault();
            document.getElementById('payment-account-error')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            alert('Pilih minimal 1 rekening pembayaran.');
            return false;
        }

        itemRows.forEach(function (row) {
            var fromStockCheck = row.querySelector('.barang-from-stock');
            var hiddenSelect = row.querySelector('.barang-select-hidden');
            var itemName = row.querySelector('.barang-item-name').value;
            var qty = parseInt(row.querySelector('.barang-item-qty').value) || 0;
            var satuan = (row.querySelector('.barang-item-satuan')?.value || '').trim();
            var capital = parseCurrencyInput(row.querySelector('.barang-item-capital').value);
            var selling = parseCurrencyInput(row.querySelector('.barang-item-selling').value);

            if (qty > 0 && (itemName || (fromStockCheck.checked && hiddenSelect && hiddenSelect.value))) {
                items.push({
                    name_item: itemName,
                    quantity: qty,
                    satuan: satuan,
                    capital_price: capital,
                    selling_price: selling,
                    from_stock: fromStockCheck.checked,
                    id_item: fromStockCheck.checked ? (hiddenSelect ? hiddenSelect.value : null) : null
                });
            }
        });

        if (items.length === 0) {
            e.preventDefault();
            alert('Minimal harus ada 1 item dengan data lengkap!');
            return false;
        }

        document.getElementById('barang-items-json').value = JSON.stringify(items);

        var submitBtn = this.querySelector('button[type="submit"]');
        var originalText = submitBtn ? submitBtn.innerHTML : '';
        if (!handleFormSubmit(submitBtn, originalText, 'Menyimpan...')) {
            e.preventDefault();
            return false;
        }
    });
}

// ─── MODE EDIT: Tombol Tambah Item ───────────────────────────────────────────

/**
 * Inisialisasi tombol "Tambah Item" pada modal EDIT.
 *
 * Membuat baris .barang-item-row-edit baru dengan name items[{index}][...],
 * lalu pasang attachEditListeners() + initPriceValidationEdit() +
 * initStockValidationEdit() untuk baris baru.
 */
function initEditItemButtons() {
    document.querySelectorAll('.add-barang-item-edit').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var invoiceNumber = this.getAttribute('data-invoice-number');
            var itemsContainer = document.getElementById('barang-items-list-edit-' + invoiceNumber);
            var currentItems = itemsContainer.querySelectorAll('.barang-item-row-edit');
            var newIndex = currentItems.length;

            var newItem = document.createElement('div');
            newItem.className = 'barang-item-row-edit mb-3 p-3 border rounded bg-surface-secondary';
            newItem.setAttribute('data-index', newIndex);
            newItem.innerHTML =
                '<div class="flex items-center gap-2 mb-2">' +
                    '<label class="flex items-center gap-2">' +
                        '<input type="checkbox" class="barang-from-stock-edit accent-primary">' +
                        '<span class="text-sm">Dari Stok</span>' +
                    '</label>' +
                '</div>' +
                '<div class="relative mb-2 barang-select-wrapper-edit" style="display: none;">' +
                    '<input type="text" class="barang-search-input-edit w-full border rounded-lg p-2 pr-10 focus:border-primary focus:ring-2 focus:ring-primary-light" placeholder="Cari barang..." autocomplete="off">' +
                    '<i class="fa-solid fa-search absolute right-3 top-3 text-text-tertiary pointer-events-none"></i>' +
                    '<div class="barang-dropdown-edit absolute z-50 w-full bg-white border border-border-strong rounded-lg shadow-lg mt-1 max-h-64 overflow-y-auto hidden">' +
                        '<div class="barang-options-edit">' +
                            '<div class="p-2 text-sm text-text-secondary hover:bg-surface-secondary cursor-pointer border-b" data-value="">-- Pilih Barang --</div>' +
                            buildBarangOptionsHtml('-edit') +
                        '</div>' +
                        '<div class="barang-no-results-edit p-4 text-center text-sm text-text-secondary hidden">' +
                            '<i class="fa-solid fa-search mb-2 text-2xl text-text-placeholder"></i>' +
                            '<p>Tidak ada barang ditemukan</p>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<input type="hidden" class="barang-select-hidden-edit">' +
                '<input type="text" name="items[' + newIndex + '][name_item]" class="barang-item-name-edit w-full border rounded p-2 mb-2" placeholder="Nama Barang *" required ' +
                    'oninvalid="this.setCustomValidity(\'Nama barang tidak boleh kosong\')" oninput="this.setCustomValidity(\'\')">' +
                '<div class="grid grid-cols-4 gap-2">' +
                    '<input type="number" name="items[' + newIndex + '][quantity]" class="barang-item-qty-edit border rounded p-2" placeholder="Qty *" required min="1" value="1" ' +
                        'oninvalid="this.setCustomValidity(\'Qty tidak boleh kosong\')" oninput="this.setCustomValidity(\'\')">' +
                    '<input type="text" name="items[' + newIndex + '][satuan]" class="barang-item-satuan-edit border rounded p-2" placeholder="Satuan" maxlength="50" ' +
                        'title="Satuan barang, contoh: zak, batang, pcs">' +
                    '<input type="text" inputmode="numeric" name="items[' + newIndex + '][capital_price]" class="barang-item-capital-edit border rounded p-2" placeholder="Rp 0" required value="Rp 0" ' +
                        'oninvalid="this.setCustomValidity(\'Harga modal tidak boleh kosong\')" oninput="formatCurrencyInput(this); this.setCustomValidity(\'\')">' +
                    '<input type="text" inputmode="numeric" name="items[' + newIndex + '][selling_price]" class="barang-item-selling-edit border rounded p-2" placeholder="Rp 0" required value="Rp 0" ' +
                        'oninvalid="this.setCustomValidity(\'Harga jual tidak boleh kosong\')" oninput="formatCurrencyInput(this); this.setCustomValidity(\'\')">' +
                '</div>' +
                '<p class="barang-price-warning-edit text-error text-sm mt-2 hidden">' +
                    '<span class="font-semibold">Peringatan:</span> Harga modal tidak boleh lebih besar atau sama dengan harga jual!' +
                '</p>' +
                '<p class="barang-stock-warning-edit text-error text-sm mt-2 hidden">' +
                    '<span class="font-semibold">Peringatan Stok:</span> <span class="barang-stock-warning-text-edit">Stok Barang Tidak Cukup! Silahkan Sesuaikan Dengan Stok Yang Tersedia.</span>' +
                '</p>' +
                '<input type="hidden" name="items[' + newIndex + '][from_stock]" class="barang-from-stock-hidden" value="false">' +
                '<input type="hidden" name="items[' + newIndex + '][id_item]" class="barang-id-item-hidden" value="">' +
                '<button type="button" class="remove-barang-item-edit mt-2 bg-btn-delete text-white px-3 py-1 rounded hover:bg-btn-delete-hover w-full">' +
                    '<i class="fa-solid fa-trash"></i> Hapus Item' +
                '</button>';

            itemsContainer.appendChild(newItem);
            attachEditListeners();
            initPriceValidationEdit(newItem, invoiceNumber);
            initStockValidationEdit(newItem, invoiceNumber);
        });
    });
}

// ─── MODE EDIT: Submit Form ──────────────────────────────────────────────────

/**
 * Inisialisasi submit form semua modal EDIT dengan validasi item & rekening.
 *
 * Alur:
 * - Untuk tiap form di dalam [id^="editModal-"]:
 *   - Validasi semua baris .barang-item-row-edit pada container
 *     #barang-items-list-edit-{invoiceNumber} (harga baris manual, stok baris
 *     "Dari Stok"); bila ada yang invalid → tampilkan alert dan batalkan submit.
 *   - Bila ada rekening tapi belum ada yang dicentang → alert & batalkan.
 *   - Panggil handleFormSubmit(submitBtn, ..., 'Update...') untuk proteksi
 *     submit ganda.
 *
 * Referensi backend: app/Services/Finance/ItemInvoiceService.php
 * (processItemsForStore melakukan restorasi & pengurangan stok).
 */
function initEditFormSubmissions() {
    document.querySelectorAll('[id^="editModal-"] form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var modal = this.closest('[id^="editModal-"]');
            var invoiceNumber = modal ? modal.id.replace('editModal-', '') : '';
            var itemsContainer = document.getElementById('barang-items-list-edit-' + invoiceNumber);

            if (itemsContainer) {
                var hasInvalidRow = Array.from(itemsContainer.querySelectorAll('.barang-item-row-edit')).some(function (row) {
                    return !isItemRowValid(row, '-edit');
                });

                if (hasInvalidRow) {
                    e.preventDefault();
                    alert('Periksa kembali item: harga modal harus lebih kecil dari harga jual dan qty tidak boleh melebihi stok.');
                    return false;
                }
            }

            if (!validatePaymentSelectionEdit(invoiceNumber)) {
                e.preventDefault();
                alert('Pilih minimal 1 rekening pembayaran.');
                return false;
            }

            var submitBtn = this.querySelector('button[type="submit"]');
            var originalText = submitBtn ? submitBtn.innerHTML : '';

            if (!handleFormSubmit(submitBtn, originalText, 'Update...')) {
                e.preventDefault();
                return false;
            }
        });
    });
}

// ─── VALIDASI AKUN PEMBAYARAN ───────────────────────────────────────────────

/**
 * Validasi pemilihan rekening pembayaran pada modal ADD.
 *
 * Hanya menampilkan/menyembunyikan pesan error; tombol Simpan TIDAK
 * dinonaktifkan (pengecekan akhir dilakukan saat submit agar pengguna
 * mendapat pesan yang jelas, bukan tombol yang diam saja).
 *
 * @return {boolean} true bila minimal 1 rekening dipilih (atau belum ada rekening)
 */
function validatePaymentSelection() {
    const isValid = hasPaymentAccountSelected(document.getElementById('addModal'));
    document.getElementById('payment-account-error')?.classList.toggle('hidden', isValid);
    return isValid;
}

/**
 * Validasi pemilihan rekening pembayaran pada modal EDIT.
 *
 * @param  {string} invoiceNumber  Nomor invoice untuk identifikasi modal
 * @return {boolean} true bila minimal 1 rekening dipilih (atau belum ada rekening)
 */
function validatePaymentSelectionEdit(invoiceNumber) {
    return hasPaymentAccountSelected(document.getElementById('editModal-' + invoiceNumber));
}

window.validatePaymentSelection = validatePaymentSelection;
window.validatePaymentSelectionEdit = validatePaymentSelectionEdit;

// ─── CHECKBOX SELECT ALL ─────────────────────────────────────────────────────

/**
 * Inisialisasi checkbox "Pilih Semua" untuk hapus massal.
 *
 * Alur:
 * - Saat #selectAll berubah → semua checkbox invoice mengikuti.
 * - Saat checkbox per baris berubah → status selectAll disinkronkan, dan
 *   tombol hapus #delete-button diaktifkan/dinonaktifkan berdasarkan ada
 *   tidaknya checkbox yang tercentang.
 */
function initSelectAllCheckbox() {
    var selectAllCheckbox = document.getElementById('selectAll');
    var invoiceCheckboxes = document.querySelectorAll('input[name="selected_invoices[]"]');
    var deleteButton = document.getElementById('delete-button');

    function updateDeleteButtonState() {
        var anyChecked = Array.from(invoiceCheckboxes).some(function (cb) { return cb.checked; });
        if (deleteButton) {
            deleteButton.disabled = !anyChecked;
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            invoiceCheckboxes.forEach(function (checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateDeleteButtonState();
        });
    }

    invoiceCheckboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            if (!this.checked && selectAllCheckbox) {
                selectAllCheckbox.checked = false;
            }

            if (selectAllCheckbox) {
                var allChecked = Array.from(invoiceCheckboxes).every(function (cb) { return cb.checked; });
                selectAllCheckbox.checked = allChecked;
            }

            updateDeleteButtonState();
        });
    });

    updateDeleteButtonState();
}

// ─── INISIALISASI ────────────────────────────────────────────────────────────

/**
 * Inisialisasi seluruh modul setelah DOM siap.
 *
 * Alur:
 * - Mode tambah: attachItemListeners, inisialisasi dropdown/harga/stok per
 *   baris, tombol tambah item, dan submit form.
 * - Mode edit: attachEditListeners, inisialisasi dropdown/harga/stok per
 *   baris di tiap container edit, tombol tambah item, dan submit form.
 * - Validasi rekening pembayaran (modal ADD & semua modal EDIT).
 * - Checkbox select all untuk hapus massal.
 * - Reset status submit saat navigasi kembali (pageshow).
 */
document.addEventListener('DOMContentLoaded', function () {
    // Mode tambah
    attachItemListeners();
    document.querySelectorAll('.barang-item-row').forEach(function (row) {
        initSearchableDropdown(row);
        initPriceValidation(row);
        initStockValidation(row);
    });
    initAddItemButton();
    initAddFormSubmission();

    // Mode edit
    attachEditListeners();
    document.querySelectorAll('[id^="barang-items-list-edit-"]').forEach(function (container) {
        var invoiceNumber = container.id.replace('barang-items-list-edit-', '');
        container.querySelectorAll('.barang-item-row-edit').forEach(function (row) {
            initPriceValidationEdit(row, invoiceNumber);
            initStockValidationEdit(row, invoiceNumber);
        });
    });
    initEditItemButtons();
    initEditFormSubmissions();

    // Validasi akun pembayaran (termasuk checkbox hasil "Rekening Baru")
    validatePaymentSelection();
    document.getElementById('addModal')?.addEventListener('change', function (e) {
        if (e.target.matches('[name="selected_payment_accounts[]"]')) {
            validatePaymentSelection();
        }
    });

    // Umum
    initSelectAllCheckbox();

    // Reset status submit form saat halaman ditampilkan (navigasi kembali/maju),
    // lalu hitung ulang status tombol dari kondisi form yang sebenarnya.
    window.addEventListener('pageshow', function () {
        resetFormSubmitState();
        refreshAddSubmitState();
        document.querySelectorAll('[id^="barang-items-list-edit-"]').forEach(function (container) {
            refreshEditSubmitState(container.id.replace('barang-items-list-edit-', ''));
        });
    });
});
