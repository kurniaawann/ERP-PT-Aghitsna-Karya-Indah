/**
 * Barang Masuk (Stock In) - Page Module
 *
 * Mengelola semua interaksi halaman Barang Masuk:
 * - Dynamic items form (add/edit modals)
 * - Searchable dropdown barang
 * - Select all / bulk delete
 * - Form submission dengan double-submit prevention
 * - Currency formatting
 */

// ==========================================
// CURRENCY HELPERS (module-scoped)
// ==========================================

/**
 * Mem-parsing nilai string menjadi angka murni (integer).
 *
 * Semua karakter non-digit dihapus, lalu dikonversi ke integer.
 * Contoh: "Rp 1.250.000" → 1250000.
 *
 * @param {string|number|undefined} value  Nilai mentah dari input
 * @returns {number} Nilai numerik (0 jika kosong/tidak valid)
 */
function parseCurrencyValue(value) {
    const rawValue = String(value ?? '').replace(/[^0-9]/g, '');
    return rawValue ? parseInt(rawValue, 10) || 0 : 0;
}

/**
 * Memformat nilai input currency dengan format ribuan Indonesia (id-ID).
 *
 * Angka yang sudah di-parse diformat ulang ke dalam input,
 * misalnya 1250000 → "1.250.000". Input dibiarkan kosong bila kosong.
 * Di-ekspos sebagai window.formatCurrencyInput agar dapat dipakai
 * dari inline handler Blade.
 *
 * @param {HTMLInputElement} input  Elemen input yang nilainya akan diformat
 * @returns {void}
 */
function formatCurrencyInput(input) {
    const rawValue = String(input.value ?? '').replace(/[^0-9]/g, '');
    input.value = rawValue ? parseInt(rawValue, 10).toLocaleString('id-ID') : '';
}

window.formatCurrencyInput = formatCurrencyInput;

// ==========================================
// BULK DELETE
// ==========================================

/**
 * Submit form hapus (bulk delete) dengan loading state pada tombol konfirmasi.
 *
 * Dipanggil dari tombol konfirmasi modal (misal via inline onclick).
 * Menampilkan spinner "Menghapus...", menonaktifkan tombol, lalu submit form.
 * Di-ekspos sebagai window.submitDeleteForm.
 *
 * @returns {void}
 */
function submitDeleteForm() {
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
}

window.submitDeleteForm = submitDeleteForm;

// ==========================================
// DYNAMIC ITEMS FORM
// ==========================================

/**
 * Mengumpulkan data item dari setiap baris (.item-row) menjadi array objek.
 *
 * Alur (baris item → JSON):
 * 1. Pilih semua baris item (.item-row) di dalam container.
 * 2. Untuk tiap baris, baca nilai dari input: id_item (hidden), nama,
 *    quantity, harga modal, dan status checkbox "Dari Stok".
 * 3. Baris yang nama itemnya kosong dilewati (dianggap bukan item valid).
 * 4. Kembalikan array objek yang siap di-encode menjadi JSON
 *    (di-set ke hidden input items-json sebelum form disubmit).
 *
 * @param {HTMLElement} container  Container berisi baris-baris item
 * @returns {Array<{id_item: string|null, name_item: string, quantity: number, capital_price: number, from_stock: boolean}>}
 */
function serializeItemsData(container) {
    const itemRows = container.querySelectorAll('.item-row');
    const items = [];

    itemRows.forEach(row => {
        const fromStock = row.querySelector('.item-from-stock, .item-from-stock-edit');
        const nameInput = row.querySelector('.item-name');
        const qtyInput = row.querySelector('.item-qty');
        const capitalInput = row.querySelector('.item-capital');
        const hiddenInput = row.querySelector('.item-select-hidden');

        if (nameInput && nameInput.value.trim()) {
            items.push({
                id_item: hiddenInput ? hiddenInput.value : null,
                name_item: nameInput.value || '',
                quantity: parseInt(qtyInput?.value) || 0,
                capital_price: parseCurrencyValue(capitalInput?.value),
                from_stock: fromStock ? fromStock.checked : false,
            });
        }
    });

    return items;
}

/**
 * Menginisialisasi dropdown pencarian barang pada satu baris item.
 *
 * Alur (auto-fill):
 * 1. Saat search input di-focus, dropdown ditampilkan.
 * 2. Saat user mengetik, setiap opsi (.item-option) difilter berdasarkan
 *    teks data-search; opsi yang tidak cocok disembunyikan, dan area
 *    "no results" ditampilkan bila tidak ada hasil.
 * 3. Klik pada opsi mengisi otomatis (auto-fill): nama barang di-set ke
 *    search input dan input nama, id barang ke hidden input, serta harga
 *    modal ke input capital lalu diformat sebagai currency.
 * 4. Klik di luar baris menutup dropdown.
 *
 * @param {HTMLElement} row  Elemen baris item yang berisi dropdown
 * @returns {void}
 */
function initSearchableDropdown(row) {
    const searchInput = row.querySelector('.item-search-input');
    const dropdown = row.querySelector('.item-dropdown');
    const options = row.querySelectorAll('.item-option');
    const noResults = row.querySelector('.no-results');
    const hiddenInput = row.querySelector('.item-select-hidden');
    const nameInput = row.querySelector('.item-name');
    const capitalInput = row.querySelector('.item-capital');

    if (!searchInput || !dropdown) return;

    searchInput.addEventListener('focus', function () {
        dropdown.classList.remove('hidden');
    });

    searchInput.addEventListener('input', function () {
        const searchTerm = this.value.toLowerCase();
        let hasResults = false;

        options.forEach(option => {
            const searchText = option.dataset.search || '';
            if (searchText.includes(searchTerm)) {
                option.style.display = 'block';
                hasResults = true;
            } else {
                option.style.display = 'none';
            }
        });

        const itemOptionsDiv = row.querySelector('.item-options');
        if (hasResults) {
            noResults.classList.add('hidden');
            itemOptionsDiv.classList.remove('hidden');
        } else {
            noResults.classList.remove('hidden');
            itemOptionsDiv.classList.add('hidden');
        }
    });

    options.forEach(option => {
        option.addEventListener('click', function () {
            const value = this.dataset.value;
            const name = this.dataset.name;
            const capital = this.dataset.capital;

            searchInput.value = name || '';
            hiddenInput.value = value || '';

            if (value) {
                nameInput.value = name;
                capitalInput.value = capital;
                formatCurrencyInput(capitalInput);
            }

            dropdown.classList.add('hidden');
        });
    });

    document.addEventListener('click', function (e) {
        if (!row.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
}

/**
 * Memasang event listener untuk setiap baris item di dalam container.
 *
 * Alur:
 * 1. Tombol hapus item (remove-item): mencegah default, menghapus baris,
 *    lalu menampilkan kembali tombol hapus bila tersisa lebih dari 1 baris.
 * 2. Checkbox "Dari Stok": menampilkan/menyembunyikan wrapper dropdown
 *    pilih barang, mengalihkan atribut required pada input nama, dan
 *    mereset nilai nama, capital, serta hidden input saat mode berubah.
 * 3. Input harga modal (item-capital): diformat sebagai currency saat
 *    pertama diisi dan setiap kali user mengetik.
 * 4. Memanggil initSearchableDropdown() untuk tiap baris.
 *
 * @param {HTMLElement} container  Container daftar item (.items-list)
 * @returns {void}
 */
function attachItemListeners(container) {
    const itemRows = container.querySelectorAll('.item-row');

    itemRows.forEach(row => {
        const removeBtn = row.querySelector('.remove-item');
        const fromStockCheckbox = row.querySelector('.item-from-stock, .item-from-stock-edit');
        const capitalInput = row.querySelector('.item-capital, .item-capital-edit');

        if (removeBtn) {
            removeBtn.addEventListener('click', function (e) {
                e.preventDefault();
                const itemsList = row.closest('.items-list') || row.closest('[id^="items-list"]');
                row.remove();
                if (itemsList && itemsList.querySelectorAll('.item-row').length > 1) {
                    itemsList.querySelectorAll('.remove-item').forEach(btn => (btn.style.display = 'block'));
                }
            });
        }

        if (fromStockCheckbox) {
            fromStockCheckbox.addEventListener('change', function () {
                const selectWrapper = row.querySelector('.item-select-wrapper');
                const nameInput = row.querySelector('.item-name');
                const capitalInput = row.querySelector('.item-capital');

                if (this.checked) {
                    selectWrapper.style.display = 'block';
                    nameInput.style.display = 'none';
                    nameInput.removeAttribute('required');
                } else {
                    selectWrapper.style.display = 'none';
                    nameInput.style.display = 'block';
                    nameInput.setAttribute('required', '');
                    nameInput.value = '';
                    capitalInput.value = '';
                    row.querySelector('.item-select-hidden').value = '';
                }
            });
        }

        if (capitalInput) {
            if (capitalInput.value) {
                formatCurrencyInput(capitalInput);
            }
            capitalInput.addEventListener('input', function () {
                formatCurrencyInput(this);
            });
        }

        initSearchableDropdown(row);
    });
}

// ==========================================
// BUILD ITEM OPTION HTML (from window.STOCK_IN_ITEMS)
// ==========================================
// Markup kartu item di bawah ini SAMA dengan Blade
// components/inventory/stock-in/item-row.blade.php (mode tambah).

const SEARCH_ICON_SVG = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon" class="shrink-0 pointer-events-none absolute start-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400">'
    + '<path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />'
    + '</svg>';

const TRASH_ICON_SVG = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon" class="shrink-0 h-5 w-5">'
    + '<path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />'
    + '</svg>';

const REQUIRED_MARK = '<sup class="ms-0.5 font-medium text-red-600">*</sup>';

/**
 * Escape teks sebelum disisipkan ke HTML/atribut (mis. nama barang yang
 * mengandung tanda kutip seperti `Pipa 1/2"`).
 *
 * @param {*} value
 * @returns {string}
 */
function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    })[ch]);
}

/**
 * Membangun HTML dropdown pilih barang dari window.STOCK_IN_ITEMS.
 *
 * Menghasilkan wrapper dropdown yang menyertakan search input dan
 * daftar opsi. Setiap opsi menyimpan data-value (id_item), data-name,
 * data-capital, data-stock, dan data-search untuk keperluan auto-fill
 * dan filtering.
 *
 * @returns {string} String HTML dropdown barang
 */
function buildItemDropdownHtml() {
    const items = window.STOCK_IN_ITEMS || [];
    let optionsHtml = '';

    items.forEach(item => {
        optionsHtml += `
            <div class="item-option cursor-pointer rounded-md px-3 py-2 hover:bg-gray-50"
                data-value="${escapeHtml(item.id_item)}"
                data-name="${escapeHtml(item.name_item)}"
                data-capital="${escapeHtml(item.capital_price)}"
                data-stock="${escapeHtml(item.quantity)}"
                data-search="${escapeHtml(String(item.name_item ?? '').toLowerCase())}">
                <div class="text-sm font-medium text-gray-950">${escapeHtml(item.name_item)}</div>
                <div class="text-sm text-gray-500">${escapeHtml(item.id_item)} · Stok: ${escapeHtml(item.quantity)} unit</div>
            </div>`;
    });

    return `
        <div class="item-select-wrapper relative" style="display: none;">
            <span class="mb-2 block text-sm font-medium leading-6 text-gray-950">Barang${REQUIRED_MARK}</span>
            <div class="relative">
                ${SEARCH_ICON_SVG}
                <input type="text" class="item-search-input fi-control ps-10" placeholder="Cari nama barang..."
                    autocomplete="off" aria-label="Cari barang">
            </div>

            <div class="item-dropdown absolute z-50 mt-1 hidden max-h-64 w-full overflow-y-auto rounded-lg bg-white p-1 shadow-lg ring-1 ring-gray-950/5">
                <div class="item-options">${optionsHtml}</div>
                <div class="no-results hidden px-3 py-6 text-center text-sm text-gray-500">
                    Tidak ada barang ditemukan
                </div>
            </div>
        </div>`;
}

/**
 * Membangun HTML untuk satu kartu item baru pada form dinamis.
 *
 * Kartu terdiri dari header (nomor item via CSS counter + tombol hapus),
 * checkbox "Barang sudah ada di Data Barang" (Dari Stok), dropdown barang
 * (buildItemDropdownHtml), hidden input id item, input nama barang baru,
 * qty, dan harga modal.
 *
 * @returns {string} String HTML kartu item baru
 */
function buildNewItemRowHtml() {
    return `
        <div class="item-row rounded-xl bg-white shadow-sm ring-1 ring-gray-950/10">
            <div class="flex min-h-[3rem] items-center justify-between gap-3 rounded-t-xl border-b border-gray-100 bg-gray-50 px-4 py-2">
                <span class="fi-repeater-item-title text-sm font-medium text-gray-950"></span>
                <button type="button" title="Hapus item"
                    class="remove-item h-8 w-8 rounded-lg p-1.5 text-gray-400 transition duration-75 hover:bg-red-50 hover:text-red-600">
                    ${TRASH_ICON_SVG}
                    <span class="sr-only">Hapus item</span>
                </button>
            </div>

            <div class="grid gap-y-4 p-4">
                <label class="flex cursor-pointer items-start gap-3">
                    <input type="checkbox" class="item-from-stock fi-checkbox mt-1">
                    <span class="grid">
                        <span class="text-sm font-medium text-gray-950">Barang sudah ada di Data Barang</span>
                        <span class="text-sm text-gray-500">Bila tidak dicentang, nama yang diisi akan dibuat sebagai barang baru.</span>
                    </span>
                </label>

                ${buildItemDropdownHtml()}

                <input type="hidden" class="item-select-hidden">

                <div class="item-name-field grid gap-y-2">
                    <span class="text-sm font-medium leading-6 text-gray-950">Nama Barang Baru${REQUIRED_MARK}</span>
                    <input type="text" class="item-name fi-control" placeholder="Masukkan nama barang" aria-label="Nama barang baru"
                        required
                        oninvalid="this.setCustomValidity('Nama barang tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="grid content-start gap-y-2">
                        <span class="text-sm font-medium leading-6 text-gray-950">Jumlah (unit)${REQUIRED_MARK}</span>
                        <input type="number" class="item-qty fi-control" placeholder="0" aria-label="Jumlah" required min="1" value="1"
                            oninvalid="this.setCustomValidity('Qty tidak boleh kosong')"
                            oninput="this.setCustomValidity('')">
                    </div>

                    <div class="grid content-start gap-y-2">
                        <span class="text-sm font-medium leading-6 text-gray-950">Harga Modal / unit${REQUIRED_MARK}</span>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-sm text-gray-500">Rp</span>
                            <input type="text" inputmode="numeric" class="item-capital fi-control ps-10" placeholder="0"
                                aria-label="Harga modal per unit" required min="0"
                                oninvalid="this.setCustomValidity('Harga modal tidak boleh kosong')"
                                oninput="formatCurrencyInput(this); this.setCustomValidity('')">
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
}

// ==========================================
// INITIALIZATION
// ==========================================

/**
 * Inisialisasi halaman Barang Masuk (Stock In).
 *
 * Alur:
 * 1. Tombol "add-item" menambahkan baris item baru dan memasang listener.
 * 2. Listener dipasang pada container items-list utama dan semua modal edit.
 * 3. Submit form modal tambah/edit: baris item di-serialisasi via
 *    serializeItemsData() menjadi JSON dan di-set ke hidden input,
 *    lalu handleFormSubmit() mencegah double-submit.
 * 4. Checkbox select all dan checkbox per item mengontrol status tombol
 *    hapus bulk delete.
 */
document.addEventListener('DOMContentLoaded', function () {
    // ------------------------------------------
    // Dynamic Items - Add Modal
    // ------------------------------------------

    const addItemBtn = document.getElementById('add-item');
    if (addItemBtn) {
        addItemBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const itemsContainer = document.getElementById('items-list');
            const newItem = document.createElement('div');
            newItem.innerHTML = buildNewItemRowHtml();
            const newRow = newItem.firstElementChild;
            itemsContainer.appendChild(newRow);
            attachItemListeners(itemsContainer);
        });
    }

    const itemsContainer = document.getElementById('items-list');
    if (itemsContainer) {
        attachItemListeners(itemsContainer);
    }

    // ------------------------------------------
    // Dynamic Items - Edit Modals
    // ------------------------------------------

    document.querySelectorAll('[id^="items-list-"]').forEach(list => {
        attachItemListeners(list);
    });

    // ------------------------------------------
    // Form Submission - Add Modal
    // ------------------------------------------

    const addForm = document.querySelector('#addModal form');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            const container = document.getElementById('items-list');
            if (container) {
                const items = serializeItemsData(container);
                document.getElementById('items-json').value = JSON.stringify(items);
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            if (!handleFormSubmit(submitBtn, originalText)) {
                e.preventDefault();
                return false;
            }
        });
    }

    // ------------------------------------------
    // Form Submission - Edit Modals
    // ------------------------------------------

    document.querySelectorAll('[id^="editModal-"] form').forEach(editForm => {
        editForm.addEventListener('submit', function (e) {
            const container = this.querySelector('[id^="items-list-"]');
            if (container) {
                const items = serializeItemsData(container);
                const hiddenInput = this.querySelector('[id^="items-json-"]');
                if (hiddenInput) {
                    hiddenInput.value = JSON.stringify(items);
                }
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            if (!handleFormSubmit(submitBtn, originalText)) {
                e.preventDefault();
                return false;
            }
        });
    });

    // ------------------------------------------
    // Select All / Bulk Delete Checkboxes
    // ------------------------------------------

    const selectAllCheckbox = document.getElementById('selectAll');
    const itemCheckboxes = document.querySelectorAll('input[name="selected_stock_ins[]"]');
    const deleteButton = document.getElementById('delete-button');

    /**
     * Mengupdate status disabled tombol hapus berdasarkan checkbox terpilih.
     * Tombol dinonaktifkan saat tidak ada checkbox yang dicentang.
     */
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
