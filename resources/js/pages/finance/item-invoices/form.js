/**
 * Invoice Barang - Halaman Form Tambah/Edit JavaScript
 *
 * Modul ini menangani:
 * - Baris item dinamis (tambah dari <template id="item-row-template">, hapus;
 *   minimal 1 baris)
 * - Mode "Dari stok": pencarian barang (combobox; panel `fixed` di <body> agar
 *   tidak terpotong tabel ber-overflow), harga modal/jual terkunci mengikuti
 *   Data Barang, info & validasi stok (qty barang yang sama di beberapa baris
 *   dijumlahkan, sama seperti pengurangan stok di server)
 * - Mode manual: validasi harga modal < harga jual
 * - Total per baris, total tfoot & ringkasan (modal, profit, total invoice)
 * - Validasi rekening pembayaran (minimal 1 bila ada rekening)
 * - Serialisasi item saat submit (input baris tidak ber-name):
 *   tambah → JSON di #items-json, edit → input hidden items[i][field]
 * - Loading state saat submit (anti double-submit)
 */

/* global parseCurrencyInput, formatRupiah, handleFormSubmit, resetFormSubmitState */

const MAX_OPTIONS = 50;

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('item-invoice-form');
    const tbody = document.getElementById('item-rows');
    const template = document.getElementById('item-row-template');
    if (!form || !tbody || !template) return;

    const isEdit = form.dataset.mode === 'edit';
    // Invoice lunas: <fieldset disabled> → nilai tersimpan hanya ditampilkan
    const locked = Boolean(form.querySelector('fieldset:disabled'));
    const payload = document.getElementById('items-payload');
    const paymentError = document.getElementById('payment-account-error');

    const items = readItems();
    const itemsById = new Map(items.map(function (item) { return [String(item.id_item), item]; }));

    // ==========================================
    // DATA & HELPER BARIS
    // ==========================================

    /**
     * Baca data barang dari <script type="application/json" id="itemsDataJson">.
     * quantity = stok tersedia untuk invoice ini.
     *
     * @returns {Array<{id_item: string, name_item: string, capital_price: number, selling_price: number, quantity: number}>}
     */
    function readItems() {
        const el = document.getElementById('itemsDataJson');
        try {
            const data = JSON.parse(el ? el.textContent : '[]');
            return Array.isArray(data) ? data : [];
        } catch (e) {
            return [];
        }
    }

    /**
     * @returns {HTMLTableRowElement[]}
     */
    function getRows() {
        return Array.from(tbody.querySelectorAll('.invoice-item-row'));
    }

    /**
     * Baca nilai satu baris item.
     *
     * @param {HTMLElement} row
     * @returns {{fromStock: boolean, id: string, item: object|null, name: string, qty: number, satuan: string, capital: number, selling: number}}
     */
    function readRow(row) {
        const fromStock = row.querySelector('.item-from-stock').checked;
        const id = row.querySelector('.item-id').value;
        const item = fromStock ? itemsById.get(id) || null : null;

        return {
            fromStock: fromStock,
            id: item ? id : '',
            item: item,
            name: fromStock ? (item ? item.name_item : '') : row.querySelector('.item-name').value.trim(),
            qty: parseInt(row.querySelector('.item-qty').value, 10) || 0,
            satuan: row.querySelector('.item-satuan').value.trim(),
            capital: parseCurrencyInput(row.querySelector('.item-capital').value) || 0,
            selling: parseCurrencyInput(row.querySelector('.item-selling').value) || 0,
        };
    }

    /**
     * Format input harga menjadi "Rp 1.000" (kosong tetap kosong agar
     * validasi required bawaan browser tetap berjalan).
     *
     * @param {HTMLInputElement} input
     * @param {number|null} [value]  Nilai baru; default dari isi input.
     * @returns {void}
     */
    function setPrice(input, value) {
        if (value === undefined) {
            const digits = String(input.value || '').replace(/[^\d]/g, '');
            input.value = digits === '' ? '' : formatRupiah(parseInt(digits, 10));
            return;
        }
        input.value = value === null ? '' : formatRupiah(value);
    }

    /**
     * Terapkan mode sumber barang (Dari stok / manual) pada tampilan baris:
     * pencarian vs nama manual, harga terkunci, dan atribut required.
     *
     * @param {HTMLElement} row
     * @returns {void}
     */
    function applySource(row) {
        const fromStock = row.querySelector('.item-from-stock').checked;

        row.querySelector('.item-picker').classList.toggle('hidden', !fromStock);
        row.querySelector('.item-name-field').classList.toggle('hidden', fromStock);
        row.querySelector('.item-name').required = !fromStock;

        ['.item-capital', '.item-selling'].forEach(function (selector) {
            const input = row.querySelector(selector);
            input.readOnly = fromStock;
            input.required = !fromStock;
        });

        if (fromStock && !locked) {
            // Harga barang dari stok selalu mengikuti Data Barang (server juga memakai harga master)
            const item = itemsById.get(row.querySelector('.item-id').value);
            if (item) fillFromItem(row, item);
        }
    }

    /**
     * Isi baris dengan barang dari stok yang dipilih.
     *
     * @param {HTMLElement} row
     * @param {object} item
     * @returns {void}
     */
    function fillFromItem(row, item) {
        row.querySelector('.item-id').value = item.id_item;
        row.querySelector('.item-search').value = item.name_item;
        setPrice(row.querySelector('.item-capital'), Number(item.capital_price) || 0);
        setPrice(row.querySelector('.item-selling'), Number(item.selling_price) || 0);
    }

    // ==========================================
    // VALIDASI & TOTAL
    // ==========================================

    /**
     * Isi semua elemen ringkasan dengan kunci tertentu (kartu Ringkasan & tfoot).
     *
     * @param {string} key   Nilai atribut data-summary.
     * @param {string} text
     * @returns {NodeListOf<HTMLElement>}
     */
    function setSummary(key, text) {
        const elements = form.querySelectorAll('[data-summary="' + key + '"]');
        elements.forEach(function (el) { el.textContent = text; });
        return elements;
    }

    /**
     * Tandai status valid tiap baris (info stok, peringatan harga, ring merah).
     * Pesan "belum memilih barang" berwarna merah setelah pencarian ditinggalkan
     * tanpa memilih barang, atau setelah percobaan simpan.
     *
     * @param {boolean} strict true saat submit (tampilkan semua kesalahan)
     * @returns {HTMLElement|null} kontrol pertama yang tidak valid
     */
    function validateRows(strict) {
        if (locked) return null;

        const rows = getRows();
        const data = rows.map(readRow);
        let firstInvalid = null;

        // Total qty per barang (beberapa baris boleh memakai barang yang sama)
        const qtyById = new Map();
        const rowsById = new Map();
        data.forEach(function (d) {
            if (!d.item) return;
            qtyById.set(d.id, (qtyById.get(d.id) || 0) + d.qty);
            rowsById.set(d.id, (rowsById.get(d.id) || 0) + 1);
        });

        rows.forEach(function (row, index) {
            const d = data[index];
            const info = row.querySelector('.item-stock-info');
            const search = row.querySelector('.item-search');
            const qtyInput = row.querySelector('.item-qty');
            const sellingInput = row.querySelector('.item-selling');
            const priceWarning = row.querySelector('.item-price-warning');

            let infoText = '';
            let infoError = false;
            let missingItem = false;
            let overStock = false;
            let priceInvalid = false;

            if (d.fromStock) {
                if (!d.item) {
                    missingItem = true;
                    infoText = 'Pilih barang dari daftar stok.';
                    infoError = strict || (search.value.trim() !== '' && search !== activeSearch);
                } else {
                    const stock = Number(d.item.quantity) || 0;
                    const totalQty = qtyById.get(d.id) || 0;
                    overStock = totalQty > stock;

                    if (overStock) {
                        infoError = true;
                        infoText = rowsById.get(d.id) > 1
                            ? 'Total qty ' + totalQty + ' melebihi stok (' + stock + ' unit).'
                            : 'Melebihi stok (tersedia ' + stock + ' unit).';
                    } else {
                        infoText = 'Stok tersedia: ' + stock + ' unit';
                    }
                }
            } else {
                // Server menolak item manual dengan harga modal ≥ harga jual
                priceInvalid = d.capital >= d.selling;
            }

            const showPrice = priceInvalid && (strict || d.selling > 0);

            info.textContent = infoText;
            info.classList.toggle('text-red-600', infoError);
            info.classList.toggle('text-gray-500', !infoError);
            search.classList.toggle('is-invalid', missingItem && infoError);
            qtyInput.classList.toggle('is-invalid', overStock);
            sellingInput.classList.toggle('is-invalid', showPrice);
            priceWarning.classList.toggle('hidden', !showPrice);

            if (!firstInvalid) {
                if (missingItem) firstInvalid = search;
                else if (overStock) firstInvalid = qtyInput;
                else if (priceInvalid) firstInvalid = sellingInput;
            }
        });

        return firstInvalid;
    }

    /**
     * Hitung ulang total tiap baris, tfoot & ringkasan, lalu validasi baris.
     *
     * @returns {void}
     */
    function recalculate() {
        const rows = getRows();
        let qty = 0;
        let capital = 0;
        let selling = 0;

        rows.forEach(function (row) {
            const d = readRow(row);
            const total = d.selling * d.qty;

            row.querySelector('.item-total').textContent = formatRupiah(total);
            qty += d.qty;
            capital += d.capital * d.qty;
            selling += total;
        });

        const profit = selling - capital;

        setSummary('rows', rows.length.toLocaleString('id-ID'));
        setSummary('qty', qty.toLocaleString('id-ID'));
        setSummary('capital', formatRupiah(capital));
        setSummary('selling', formatRupiah(selling));
        setSummary('profit', (profit < 0 ? '− ' : '') + formatRupiah(Math.abs(profit))).forEach(function (el) {
            el.classList.toggle('text-emerald-600', profit >= 0);
            el.classList.toggle('text-red-600', profit < 0);
        });

        // Minimal 1 item: tombol hapus baris terakhir dinonaktifkan
        rows.forEach(function (row) {
            row.querySelector('.remove-row-btn').disabled = rows.length <= 1;
        });

        validateRows(form.dataset.submitted === '1');
    }

    /**
     * Cek rekening pembayaran: minimal 1 dipilih (lolos bila belum ada rekening
     * sama sekali, sama seperti validasi lama).
     *
     * @param {boolean} showError
     * @returns {boolean}
     */
    function validatePaymentAccounts(showError) {
        const checkboxes = form.querySelectorAll('[name="selected_payment_accounts[]"]');
        const valid = checkboxes.length === 0 || Array.from(checkboxes).some(function (cb) { return cb.checked; });

        if (paymentError && (showError || valid)) paymentError.classList.toggle('hidden', valid);
        return valid;
    }

    // ==========================================
    // PENCARIAN BARANG (COMBOBOX "DARI STOK")
    // ==========================================

    const panel = document.createElement('div');
    panel.className = 'fi-select-panel hidden';
    panel.id = 'item-picker-panel';
    panel.setAttribute('role', 'listbox');
    document.body.appendChild(panel);

    let activeSearch = null;
    let activeIndex = -1;

    /**
     * @returns {HTMLButtonElement[]} opsi yang bisa dipilih
     */
    function optionButtons() {
        return Array.from(panel.querySelectorAll('.fi-select-option:not([disabled])'));
    }

    /**
     * Tandai opsi aktif (navigasi keyboard).
     *
     * @param {number} index
     * @returns {void}
     */
    function highlight(index) {
        const buttons = optionButtons();
        if (buttons.length === 0) {
            activeIndex = -1;
            return;
        }

        activeIndex = Math.max(0, Math.min(buttons.length - 1, index));
        buttons.forEach(function (button, i) {
            button.classList.toggle('bg-gray-50', i === activeIndex);
        });
        buttons[activeIndex].scrollIntoView({ block: 'nearest' });
        activeSearch.setAttribute('aria-activedescendant', buttons[activeIndex].id);
    }

    /**
     * Render daftar barang sesuai teks pencarian.
     *
     * @returns {void}
     */
    function renderOptions() {
        const row = activeSearch.closest('.invoice-item-row');
        const selected = itemsById.get(row.querySelector('.item-id').value);

        // Baru dibuka dengan barang terpilih → tampilkan semua barang
        const text = activeSearch.value.trim().toLowerCase();
        const term = selected && activeSearch.value === selected.name_item ? '' : text;
        const matches = items.filter(function (item) {
            return term === '' || String(item.name_item).toLowerCase().includes(term);
        });

        panel.textContent = '';

        if (matches.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'px-3 py-6 text-center text-sm text-gray-500';
            empty.textContent = items.length === 0 ? 'Belum ada Data Barang.' : 'Tidak ada barang ditemukan.';
            panel.appendChild(empty);
        }

        matches.slice(0, MAX_OPTIONS).forEach(function (item, index) {
            const stock = Number(item.quantity) || 0;
            const button = document.createElement('button');
            button.type = 'button';
            button.id = 'item-picker-option-' + index;
            button.className = 'fi-select-option' + (selected && selected.id_item === item.id_item ? ' is-selected' : '');
            button.setAttribute('role', 'option');
            button.dataset.id = item.id_item;
            button.disabled = stock <= 0;

            const name = document.createElement('span');
            name.className = 'min-w-0 truncate';
            name.textContent = item.name_item;

            const meta = document.createElement('span');
            meta.className = 'shrink-0 text-xs ' + (stock > 0 ? 'text-gray-500' : 'text-red-600');
            meta.textContent = stock > 0 ? 'Stok ' + stock.toLocaleString('id-ID') : 'Stok habis';

            button.append(name, meta);
            panel.appendChild(button);
        });

        if (matches.length > MAX_OPTIONS) {
            const more = document.createElement('div');
            more.className = 'px-3 py-2 text-xs text-gray-500';
            more.textContent = (matches.length - MAX_OPTIONS) + ' barang lainnya — ketik untuk mempersempit pencarian.';
            panel.appendChild(more);
        }

        activeIndex = -1;
        activeSearch.removeAttribute('aria-activedescendant');
    }

    /**
     * Posisikan panel di bawah input pencarian (atau di atasnya bila ruang
     * bawah tidak cukup).
     *
     * @returns {void}
     */
    function positionPanel() {
        if (!activeSearch) return;

        const gap = 4;
        const margin = 8;
        const rect = activeSearch.getBoundingClientRect();

        panel.style.width = Math.max(rect.width, 288) + 'px';
        const width = panel.offsetWidth;
        const height = panel.offsetHeight;

        const left = Math.max(margin, Math.min(rect.left, window.innerWidth - width - margin));
        let top = rect.bottom + gap;
        if (top + height > window.innerHeight - margin && rect.top - height - gap >= margin) {
            top = rect.top - height - gap;
        }

        panel.style.left = left + 'px';
        panel.style.top = top + 'px';
    }

    /**
     * Buka / perbarui panel untuk input pencarian tertentu.
     *
     * @param {HTMLInputElement} search
     * @returns {void}
     */
    function openPicker(search) {
        if (search.disabled) return;

        activeSearch = search;
        renderOptions();
        panel.classList.remove('hidden');
        positionPanel();
        search.setAttribute('aria-expanded', 'true');
        search.setAttribute('aria-controls', panel.id);
    }

    /**
     * Tutup panel; teks pencarian dikembalikan ke nama barang terpilih.
     *
     * @returns {void}
     */
    function closePicker() {
        if (!activeSearch) return;

        const search = activeSearch;
        activeSearch = null;
        panel.classList.add('hidden');
        search.setAttribute('aria-expanded', 'false');
        search.removeAttribute('aria-activedescendant');

        const item = itemsById.get(search.closest('.invoice-item-row').querySelector('.item-id').value);
        if (item) search.value = item.name_item;
    }

    /**
     * Pilih barang untuk baris pada input pencarian aktif.
     *
     * @param {string} id
     * @returns {void}
     */
    function chooseItem(id) {
        const item = itemsById.get(String(id));
        if (!activeSearch || !item || (Number(item.quantity) || 0) <= 0) return;

        const row = activeSearch.closest('.invoice-item-row');
        fillFromItem(row, item);
        closePicker();
        recalculate();

        const qtyInput = row.querySelector('.item-qty');
        qtyInput.focus();
        qtyInput.select();
    }

    // Klik pada panel tidak memindahkan fokus dari input pencarian
    panel.addEventListener('mousedown', function (e) { e.preventDefault(); });
    panel.addEventListener('click', function (e) {
        const option = e.target.closest('.fi-select-option');
        if (option && !option.disabled) chooseItem(option.dataset.id);
    });

    window.addEventListener('resize', positionPanel);
    window.addEventListener('scroll', positionPanel, true);

    // ==========================================
    // TAMBAH & HAPUS BARIS
    // ==========================================

    /**
     * Tambah satu baris item kosong (mode manual) lalu fokus ke nama barang.
     *
     * @returns {void}
     */
    function addRow() {
        const fragment = template.content.cloneNode(true);
        const row = fragment.querySelector('.invoice-item-row');
        tbody.appendChild(fragment);

        applySource(row);
        recalculate();
        row.querySelector('.item-name').focus();
    }

    document.querySelectorAll('[data-add-item-row]').forEach(function (btn) {
        btn.addEventListener('click', addRow);
    });

    tbody.addEventListener('click', function (e) {
        const removeBtn = e.target.closest('.remove-row-btn');
        if (!removeBtn || getRows().length <= 1) return;

        const row = removeBtn.closest('.invoice-item-row');
        if (activeSearch && row.contains(activeSearch)) closePicker();
        row.remove();
        recalculate();
    });

    // ==========================================
    // EVENT INPUT BARIS
    // ==========================================

    tbody.addEventListener('change', function (e) {
        if (!e.target.classList.contains('item-from-stock')) return;

        const row = e.target.closest('.invoice-item-row');
        const nameInput = row.querySelector('.item-name');
        const search = row.querySelector('.item-search');

        row.querySelector('.item-id').value = '';
        if (e.target.checked) {
            // Nama yang sudah diketik dipakai sebagai kata kunci pencarian stok
            search.value = nameInput.value.trim();
            nameInput.value = '';
            setPrice(row.querySelector('.item-capital'), null);
            setPrice(row.querySelector('.item-selling'), null);
        } else {
            search.value = '';
        }

        applySource(row);

        // Fokus dulu (membuka pencarian) agar validasi tidak langsung menandai merah
        if (e.target.checked) {
            search.focus();
        } else {
            nameInput.focus();
        }

        recalculate();
    });

    tbody.addEventListener('input', function (e) {
        const input = e.target;
        const row = input.closest('.invoice-item-row');
        if (!row) return;

        if (input.classList.contains('item-capital') || input.classList.contains('item-selling')) {
            setPrice(input);
        }

        if (input.classList.contains('item-search')) {
            // Teks berubah → pilihan lama tidak berlaku sampai barang dipilih lagi
            row.querySelector('.item-id').value = '';
            setPrice(row.querySelector('.item-capital'), null);
            setPrice(row.querySelector('.item-selling'), null);
            openPicker(input);
        }

        recalculate();
    });

    tbody.addEventListener('focusin', function (e) {
        if (e.target.classList.contains('item-search')) openPicker(e.target);
    });

    tbody.addEventListener('focusout', function (e) {
        if (e.target !== activeSearch) return;

        closePicker();
        validateRows(form.dataset.submitted === '1');
    });

    tbody.addEventListener('keydown', function (e) {
        const search = e.target;
        if (!search.classList.contains('item-search')) return;

        const isOpen = activeSearch === search;

        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            if (!isOpen) {
                openPicker(search);
                highlight(0);
            } else {
                highlight(activeIndex + (e.key === 'ArrowDown' ? 1 : -1));
            }
        } else if (e.key === 'Enter') {
            // Enter di pencarian tidak pernah men-submit form
            e.preventDefault();
            const buttons = optionButtons();
            if (isOpen && buttons.length > 0) {
                chooseItem((buttons[activeIndex] || buttons[0]).dataset.id);
            }
        } else if (e.key === 'Escape' && isOpen) {
            e.preventDefault();
            closePicker();
        }
    });

    // ==========================================
    // REKENING PEMBAYARAN
    // ==========================================

    form.addEventListener('change', function (e) {
        if (e.target.name === 'selected_payment_accounts[]') validatePaymentAccounts(false);
    });

    // ==========================================
    // SUBMIT
    // ==========================================

    /**
     * Susun data item sesuai format request lama: JSON (store) atau
     * items[i][field] (update).
     *
     * @returns {void}
     */
    function serializeItems() {
        const data = getRows().map(function (row) {
            const d = readRow(row);
            return {
                name_item: d.name,
                quantity: d.qty,
                satuan: d.satuan,
                capital_price: d.capital,
                selling_price: d.selling,
                from_stock: d.fromStock,
                id_item: d.fromStock ? d.id : null,
            };
        });

        if (!isEdit) {
            document.getElementById('items-json').value = JSON.stringify(data);
            return;
        }

        payload.textContent = '';
        data.forEach(function (item, index) {
            Object.keys(item).forEach(function (key) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'items[' + index + '][' + key + ']';
                input.value = key === 'from_stock' ? (item[key] ? 'true' : 'false') : (item[key] ?? '');
                payload.appendChild(input);
            });
        });
    }

    form.addEventListener('submit', function (e) {
        closePicker();
        form.dataset.submitted = '1';

        const firstInvalid = validateRows(true);
        if (firstInvalid) {
            e.preventDefault();
            firstInvalid.focus();
            if (typeof window.showToast === 'function') {
                window.showToast('Periksa kembali item invoice yang ditandai merah.', 'error');
            }
            return false;
        }

        if (!validatePaymentAccounts(true)) {
            e.preventDefault();
            paymentError.scrollIntoView({ block: 'center', behavior: 'smooth' });
            if (typeof window.showToast === 'function') {
                window.showToast('Pilih minimal 1 rekening pembayaran.', 'error');
            }
            return false;
        }

        serializeItems();

        const submitBtn = form.querySelector('button[type="submit"]');
        if (!handleFormSubmit(submitBtn, submitBtn ? submitBtn.innerHTML : '')) {
            e.preventDefault();
            return false;
        }
    });

    // Kembali lewat tombol Back (bfcache) → tombol Simpan aktif lagi
    window.addEventListener('pageshow', function (e) {
        if (e.persisted && typeof resetFormSubmitState === 'function') resetFormSubmitState();
    });

    // ==========================================
    // INISIALISASI
    // ==========================================

    getRows().forEach(applySource);
    recalculate();
});
