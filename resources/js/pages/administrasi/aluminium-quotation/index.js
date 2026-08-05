/**
 * Penawaran Aluminium (Aluminium Quotation) — Index Page JavaScript
 *
 * Modul ini menangani:
 * - Auto-generate nomor penawaran
 * - Manajemen dynamic groups & items (tambah/hapus/hitung ulang)
 * - Serialisasi groups ke JSON untuk form submission
 * - Validasi rekening pembayaran (wajib pilih minimal 1)
 * - Loading indicator pada tombol aksi
 * - Select all checkbox & bulk delete button state
 * - Error display pada modal
 */

/* ==========================================
 * HELPER: Meng-escape HTML
 * ========================================== */

/**
 * Meng-escape karakter HTML untuk keamanan saat menyisipkan string ke DOM.
 *
 * @param  {string} str  String yang akan di-escape
 * @return {string}      String yang sudah di-escape
 */
function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/* ==========================================
 * HELPER: Format Rupiah
 * ========================================== */

/**
 * Memformat angka sebagai Rupiah.
 *
 * @param  {number|string} value  Nilai angka
 * @return {string}               String format Rp
 */
function formatRp(value) {
    if (!value || isNaN(value)) return 'Rp 0';
    return 'Rp ' + parseInt(value).toLocaleString('id-ID');
}

/**
 * Menghapus pemisah ribuan dan mengembalikan angka murni.
 *
 * @param  {string} str  String angka dengan pemisah
 * @return {number}      Angka murni
 */
function parseAmount(str) {
    if (!str) return 0;
    return parseInt(str.toString().replace(/\./g, '').replace(/,/g, '')) || 0;
}

/**
 * Menerapkan format pemisah ribuan pada input harga.
 *
 * @param  {HTMLInputElement} input  Element input
 */
function formatPriceInput(input) {
    let raw = input.value.replace(/\./g, '').replace(/[^0-9]/g, '');
    if (raw === '') {
        input.value = '';
        return;
    }
    input.value = parseInt(raw).toLocaleString('id-ID');
}

/* ==========================================
 * ERROR DISPLAY PADA MODAL
 * ========================================== */

/**
 * Menampilkan pesan error di dalam modal.
 *
 * @param  {string} prefix       Prefix ID modal (contoh: 'add', 'editModal-1/1/ALU/26')
 * @param  {string} errorMessage  Pesan error
 */
function showModalError(prefix, errorMessage) {
    const errorDiv = document.getElementById(prefix + 'ModalError');
    const errorText = document.getElementById(prefix + 'ModalErrorText');
    if (errorDiv && errorText) {
        errorText.textContent = errorMessage;
        errorDiv.classList.remove('hidden');
        errorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

/**
 * Menyembunyikan pesan error di dalam modal.
 *
 * @param  {string} prefix  Prefix ID modal
 */
function hideModalError(prefix) {
    const errorDiv = document.getElementById(prefix + 'ModalError');
    if (errorDiv) {
        errorDiv.classList.add('hidden');
    }
}

/* ==========================================
 * RESOLVE IDS BERDASARKAN PREFIX
 * ========================================== */

/**
 * Mendapatkan ID container, grand total, dan JSON input berdasarkan prefix.
 *
 * @param  {string} prefix  'add' atau 'edit-{quotationNumber}'
 * @return {object}         { container, grandTotal, jsonInput }
 */
function resolveIds(prefix) {
    if (prefix === 'add') {
        return {
            container: 'addGroupsContainer',
            grandTotal: 'addGrandTotal',
            jsonInput: 'addGroupsJson',
        };
    }
    const quotNum = prefix.replace(/^edit-/, '');
    return {
        container: `editGroupsContainer-${quotNum}`,
        grandTotal: `editGrandTotal-${quotNum}`,
        jsonInput: `editGroupsJson-${quotNum}`,
    };
}

/* ==========================================
 * MANAJEMEN ITEM
 * ========================================== */

/**
 * Menghitung ulang total_price satu item berdasarkan volume x unit_price.
 *
 * Alur:
 * 1. Baca nilai input volume (.item-volume) lalu parse sebagai float
 *    dengan koma sebagai pemisah desimal (format Indonesia);
 *    fallback 0 jika kosong atau tidak valid.
 * 2. Baca nilai input harga satuan (.item-unit-price) via parseAmount()
 *    yang membuang pemisah ribuan (titik).
 * 3. Hitung total = Math.round(volume * hargaSatuan) dan tampilkan
 *    di elemen .item-total-display dengan format Rupiah.
 * 4. Kembalikan nilai total untuk diakumulasi oleh recalcGroup().
 *
 * Catatan: nilai ini hanya preview client-side; saat submit server
 * (AluminiumQuotationService) menghitung ulang subtotal group dan
 * total_amount dari total_price item yang dikirim via JSON.
 *
 * @param  {HTMLElement} itemEl  Element item row
 * @return {number}              Total price item (pembulatan integer)
 */
function recalcItem(itemEl) {
    const volInput = itemEl.querySelector('.item-volume');
    const priceInput = itemEl.querySelector('.item-unit-price');
    const totalEl = itemEl.querySelector('.item-total-display');

    const vol = parseFloat((volInput.value || '0').replace(',', '.')) || 0;
    const price = parseAmount(priceInput.value);
    const total = Math.round(vol * price);

    if (totalEl) totalEl.textContent = formatRp(total);
    return total;
}

/**
 * Menghitung ulang subtotal satu group berdasarkan total semua items.
 *
 * Alur:
 * 1. Iterasi semua baris item (.item-row) di dalam group.
 * 2. Akumulasi hasil recalcItem() setiap item ke variabel subtotal.
 * 3. Tampilkan subtotal di elemen .group-subtotal dengan format Rupiah.
 * 4. Kembalikan subtotal untuk diakumulasi oleh updateGrandTotal().
 *
 * Server (AluminiumQuotationService::syncGroups) juga menghitung subtotal
 * yang sama dari total_price item saat data disimpan.
 *
 * @param  {HTMLElement} groupEl  Element group card
 * @return {number}               Subtotal group
 */
function recalcGroup(groupEl) {
    let subtotal = 0;
    groupEl.querySelectorAll('.item-row').forEach(item => {
        subtotal += recalcItem(item);
    });
    const subtotalEl = groupEl.querySelector('.group-subtotal');
    if (subtotalEl) subtotalEl.textContent = formatRp(subtotal);
    return subtotal;
}

/**
 * Menghitung ulang grand total dari semua groups.
 *
 * Alur:
 * 1. Resolve ID container dan elemen grand total dari prefix.
 * 2. Iterasi semua group card (.group-card) di dalam container.
 * 3. Akumulasi hasil recalcGroup() setiap group ke variabel grand.
 * 4. Tampilkan grand di elemen grand total dengan format Rupiah.
 *
 * Dipanggil dari berbagai event (input volume/harga, tambah/hapus item
 * maupun group) sehingga angka selalu sinkron dengan DOM. Server
 * menghitung ulang total_amount via AluminiumQuotationService dari
 * data JSON yang dikirim pada saat submit.
 *
 * @param  {string} prefix  'add' atau 'edit-{quotationNumber}'
 */
function updateGrandTotal(prefix) {
    const ids = resolveIds(prefix);
    const container = document.getElementById(ids.container);
    const grandTotEl = document.getElementById(ids.grandTotal);
    if (!container || !grandTotEl) return;

    let grand = 0;
    container.querySelectorAll('.group-card').forEach(groupEl => {
        grand += recalcGroup(groupEl);
    });
    grandTotEl.textContent = formatRp(grand);
}

/**
 * Menambahkan satu baris item ke dalam container items.
 *
 * Alur:
 * 1. Buat elemen div.item-row berisi input keterangan, volume, satuan,
 *    harga satuan, display total, dan tombol hapus.
 * 2. Jika ada prefillData, isi nilai input; description/unit di-escape
 *    via escHtml() demi keamanan terhadap injeksi HTML.
 * 3. Pasang listener pada input harga: format pemisah ribuan via
 *    formatPriceInput() lalu updateGrandTotal().
 * 4. Pasang listener pada input volume: filter karakter non-digit/desimal
 *    lalu updateGrandTotal().
 * 5. Append baris ke itemsContainer; jika ada harga pra-isi,
 *    hitung ulang grand total agar tampilan langsung akurat.
 *
 * @param  {HTMLElement} itemsContainer  Container items dalam group
 * @param  {string}      prefix          'add' atau 'edit-{quotationNumber}'
 * @param  {object}      prefillData     Data pra-isi (opsional)
 */
function addItem(itemsContainer, prefix, prefillData) {
    prefillData = prefillData || {};
    const itemEl = document.createElement('div');
    itemEl.className = 'item-row bg-surface-base border border-border-strong rounded-lg p-3 space-y-2';
    itemEl.innerHTML = `
    <div class="space-y-2">
        <div>
            <label class="block text-xs font-semibold text-text-label mb-1">Keterangan <span class="text-error">*</span></label>
            <input type="text" class="item-description w-full border border-border-strong rounded-lg px-3 py-2 text-sm text-text-input bg-surface-base"
                placeholder="Contoh: 5 x 130 x 300" required maxlength="255"
                oninvalid="this.setCustomValidity('Keterangan item harus diisi')"
                oninput="this.setCustomValidity('')"
                value="${escHtml(prefillData.description || '')}">
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="block text-xs font-semibold text-text-label mb-1">Volume</label>
                <input type="number" class="item-volume w-full border border-border-strong rounded-lg px-3 py-2 text-sm text-text-input bg-surface-base"
                    placeholder="-" min="0" step="0.01"
                    oninvalid="this.setCustomValidity('Volume harus berupa angka positif')"
                    oninput="this.setCustomValidity('')"
                    value="${prefillData.volume || ''}">
            </div>
            <div>
                <label class="block text-xs font-semibold text-text-label mb-1">Satuan</label>
                <input type="text" class="item-unit w-full border border-border-strong rounded-lg px-3 py-2 text-sm text-text-input bg-surface-base"
                    placeholder="-" maxlength="50"
                    oninvalid="this.setCustomValidity('Satuan maksimal 50 karakter')"
                    oninput="this.setCustomValidity('')"
                    value="${escHtml(prefillData.unit || '')}">
            </div>
        </div>
        <div>
            <label class="block text-xs font-semibold text-text-label mb-1">Harga Satuan <span class="text-error">*</span></label>
            <input type="text" class="item-unit-price w-full border border-border-strong rounded-lg px-3 py-2 text-sm text-right text-text-input bg-surface-base"
                placeholder="0" required
                oninvalid="this.setCustomValidity('Harga satuan harus diisi')"
                oninput="this.setCustomValidity('')"
                value="${prefillData.unit_price ? parseInt(prefillData.unit_price).toLocaleString('id-ID') : ''}">
        </div>
        <div>
            <label class="block text-xs font-semibold text-text-label mb-1">Jumlah</label>
            <div class="item-total-display text-sm font-semibold text-success border border-border-strong rounded-lg px-3 py-2 bg-surface-secondary text-right">
                ${formatRp(prefillData.total_price || 0)}
            </div>
        </div>
        <div>
            <button type="button" onclick="removeItem(this, '${prefix}')"
                class="w-full bg-btn-delete hover:bg-btn-delete-hover text-white px-3 py-2 rounded-lg text-sm flex items-center justify-center gap-2 transition-colors">
                <i class="fa-solid fa-trash"></i>
                <span>Hapus Item</span>
            </button>
        </div>
    </div>`;

    // Pasang event listener untuk kalkulasi otomatis
    const volInput = itemEl.querySelector('.item-volume');
    const priceInput = itemEl.querySelector('.item-unit-price');
    priceInput.addEventListener('input', () => {
        formatPriceInput(priceInput);
        updateGrandTotal(prefix);
    });
    volInput.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9.,]/g, '');
        updateGrandTotal(prefix);
    });

    itemsContainer.appendChild(itemEl);

    // Hitung ulang setelah ditambahkan (untuk data pra-isi)
    if (prefillData.unit_price) updateGrandTotal(prefix);
}

/**
 * Menghapus satu baris item.
 *
 * Alur:
 * 1. Hapus elemen .item-row terdekat dari tombol yang diklik.
 * 2. Hitung ulang grand total (subtotal group ikut terhitung ulang
 *    karena updateGrandTotal memanggil recalcGroup pada tiap group).
 *
 * @param  {HTMLElement} button  Tombol hapus yang diklik
 * @param  {string}      prefix  'add' atau 'edit-{quotationNumber}'
 */
function removeItem(btn, prefix) {
    btn.closest('.item-row').remove();
    updateGrandTotal(prefix);
}

/* ==========================================
 * MANAJEMEN GROUP
 * ========================================== */

/**
 * Menambahkan satu group card baru.
 *
 * Alur:
 * 1. Resolve container dari prefix; hitung jumlah group saat ini + 1
 *    untuk label "Kelompok N".
 * 2. Bangun elemen group card berisi input nama kelompok, daftar item
 *    (.items-list), tombol "Tambah Item", dan display subtotal.
 * 3. Append ke container; jika prefillData memiliki items, render
 *    masing-masing item via addItem() (dipakai saat modal edit dibuka).
 * 4. Hitung ulang grand total.
 *
 * @param  {string} prefix       'add' atau 'edit-{quotationNumber}'
 * @param  {object} prefillData  Data pra-isi (opsional)
 */
function addGroup(prefix, prefillData) {
    prefillData = prefillData || {};
    const ids = resolveIds(prefix);
    const container = document.getElementById(ids.container);
    if (!container) return;

    const groupCount = container.querySelectorAll('.group-card').length + 1;

    const groupEl = document.createElement('div');
    groupEl.className = 'group-card border-2 border-border-strong rounded-xl bg-surface-secondary overflow-hidden shadow-sm';
    groupEl.innerHTML = `
    <div class="flex items-center justify-between bg-surface-hover px-4 py-2">
        <span class="font-bold text-sm text-text-heading">Kelompok ${groupCount}</span>
        <button type="button" onclick="removeGroup(this, '${prefix}')"
            class="text-error hover:text-error text-sm flex items-center gap-1 transition-colors">
            <i class="fa-solid fa-circle-minus"></i> Hapus Kelompok
        </button>
    </div>
    <div class="p-4 space-y-3">
        <div>
            <label class="block text-xs font-semibold text-text-label mb-1">Nama Kelompok <span class="text-error">*</span></label>
            <input type="text" class="group-name w-full border border-border-strong rounded-lg px-3 py-2 text-sm text-text-input bg-surface-base"
                placeholder="Contoh: P.1 Kayu Kamper Samarinda Oven" required maxlength="255"
                oninvalid="this.setCustomValidity('Nama kelompok harus diisi')"
                oninput="this.setCustomValidity('')"
                value="${escHtml(prefillData.name || '')}">
        </div>
        <div class="items-list space-y-2"></div>
        <button type="button" onclick="addItemToGroup(this, '${prefix}')"
            class="flex items-center gap-2 bg-surface-base border-2 border-dashed border-border-strong hover:border-primary hover:text-primary text-text-secondary px-4 py-2 rounded-lg text-sm w-full justify-center transition-all duration-200">
            <i class="fa-solid fa-plus"></i> Tambah Item
        </button>
        <div class="flex justify-end">
            <div class="text-sm font-semibold text-right">
                Jumlah: <span class="group-subtotal text-green-700">Rp 0</span>
            </div>
        </div>
    </div>`;

    container.appendChild(groupEl);

    // Pra-isi items jika ada data
    if (prefillData.items && prefillData.items.length > 0) {
        const itemsList = groupEl.querySelector('.items-list');
        prefillData.items.forEach(itemData => addItem(itemsList, prefix, itemData));
    }

    updateGrandTotal(prefix);
}

/**
 * Menambahkan item baru ke group via tombol "+ Tambah Item".
 *
 * Alur:
 * 1. Cari group card terdekat dari tombol yang diklik.
 * 2. Ambil elemen .items-list di dalam group.
 * 3. Tambahkan satu item kosong via addItem().
 *
 * @param  {HTMLElement} btn    Tombol yang diklik
 * @param  {string}      prefix 'add' atau 'edit-{quotationNumber}'
 */
function addItemToGroup(btn, prefix) {
    const groupEl = btn.closest('.group-card');
    const itemsList = groupEl.querySelector('.items-list');
    addItem(itemsList, prefix, {});
}

/**
 * Menghapus satu group card.
 *
 * Alur:
 * 1. Hapus elemen .group-card yang menampung tombol yang diklik.
 * 2. Renumber ulang label "Kelompok N" pada semua group yang tersisa
 *    sesuai urutan DOM (idx + 1).
 * 3. Hitung ulang grand total.
 *
 * @param  {HTMLElement} button  Tombol hapus yang diklik
 * @param  {string}      prefix  'add' atau 'edit-{quotationNumber}'
 */
function removeGroup(btn, prefix) {
    btn.closest('.group-card').remove();
    // Nomor ulang label kelompok
    const ids = resolveIds(prefix);
    document.querySelectorAll(`#${ids.container} .group-card`).forEach((card, idx) => {
        const lbl = card.querySelector('.flex.items-center.justify-between span');
        if (lbl) lbl.textContent = `Kelompok ${idx + 1}`;
    });
    updateGrandTotal(prefix);
}

/* ==========================================
 * SERIALISASI GROUPS KE JSON
 * ========================================== */

/**
 * Mengubah semua groups + items dalam container menjadi array of objects.
 *
 * Alur (DOM-walk ke struktur JSON):
 * 1. Resolve container berdasarkan prefix.
 * 2. Iterasi setiap .group-card:
 *    a. Buat array kosong untuk items.
 *    b. Iterasi setiap .item-row di dalam group:
 *       - Baca description (trim) dan unit (trim, null bila kosong).
 *       - Baca volume sebagai string mentah (null bila kosong agar
 *         tidak terkirim string kosong).
 *       - Parse harga satuan via parseAmount() (buang pemisah ribuan).
 *       - Konversi volume koma -> titik lalu parse float.
 *       - total_price = Math.round(volume * hargaSatuan).
 *       - Push objek item {description, volume, unit, unit_price, total_price}.
 *    c. Push objek group {name, items}.
 * 3. Kembalikan array groups; hasil ini lalu di-JSON.stringify dan
 *    disimpan ke hidden input (addGroupsJson / editGroupsJson-{n})
 *    pada saat submit agar backend menerima struktur yang sama.
 *
 * @param  {string} prefix  'add' atau 'edit-{quotationNumber}'
 * @return {array}          Array of group objects
 */
function serializeGroups(prefix) {
    const ids = resolveIds(prefix);
    const container = document.getElementById(ids.container);
    if (!container) return [];

    const groups = [];
    container.querySelectorAll('.group-card').forEach(groupEl => {
        const items = [];
        groupEl.querySelectorAll('.item-row').forEach(itemEl => {
            const vol = itemEl.querySelector('.item-volume').value.trim();
            const unitPrice = parseAmount(itemEl.querySelector('.item-unit-price').value);
            const volNum = parseFloat(vol.replace(',', '.')) || 0;
            const totalPrice = Math.round(volNum * unitPrice);
            items.push({
                description: itemEl.querySelector('.item-description').value.trim(),
                volume: vol || null,
                unit: itemEl.querySelector('.item-unit').value.trim() || null,
                unit_price: unitPrice,
                total_price: totalPrice,
            });
        });
        groups.push({
            name: groupEl.querySelector('.group-name').value.trim(),
            items: items,
        });
    });
    return groups;
}

/* ==========================================
 * SUBMIT FORM: TAMBAH
 * ========================================== */

/**
 * Menyiapkan dan memvalidasi form tambah sebelum submission.
 * Dipanggil dari onsubmit modal add.
 *
 * Alur:
 * 1. Sembunyikan error modal add.
 * 2. Serialisasi groups via serializeGroups('add').
 * 3. Validasi: minimal 1 group; nama group tidak boleh kosong;
 *    setiap group minimal punya 1 item.
 * 4. Tulis hasil JSON.stringify(groups) ke hidden input #addGroupsJson.
 * 5. Tampilkan loading spinner pada tombol submit via handleFormSubmit()
 *    (dari shared module) untuk mencegah double submit.
 *
 * @return {boolean} true jika valid, false jika gagal
 */
function prepareAddSubmit() {
    hideModalError('add');

    const groups = serializeGroups('add');
    const jsonInput = document.getElementById('addGroupsJson');

    // Validasi groups
    if (groups.length === 0) {
        showModalError('add', 'Minimal 1 kelompok harus ditambahkan.');
        return false;
    }

    for (const g of groups) {
        if (!g.name) {
            showModalError('add', 'Nama kelompok tidak boleh kosong.');
            return false;
        }
        if (g.items.length === 0) {
            showModalError('add', `Kelompok "${g.name}" harus memiliki minimal 1 item.`);
            return false;
        }
    }

    // Atur JSON
    jsonInput.value = JSON.stringify(groups);

    // Indikator loading
    const submitBtn = document.getElementById('submit-btn-addModal');
    if (submitBtn) {
        const originalText = submitBtn.innerHTML;
        if (!handleFormSubmit(submitBtn, originalText, 'Menyimpan...')) {
            return false;
        }
    }

    return true;
}

/* ==========================================
 * SUBMIT FORM: EDIT
 * ========================================== */

/**
 * Menyiapkan dan memvalidasi form edit sebelum submission.
 * Dipanggil dari onsubmit modal edit.
 *
 * Alur:
 * 1. Sembunyikan error modal edit (prefix editModal-{quotNum}).
 * 2. Serialisasi groups via serializeGroups('edit-{quotNum}').
 * 3. Validasi sama seperti prepareAddSubmit (minimal 1 group,
 *    nama group wajib, tiap group minimal 1 item).
 * 4. Tulis hasil JSON.stringify(groups) ke hidden input
 *    #editGroupsJson-{quotNum}.
 * 5. Tampilkan loading spinner pada tombol submit via handleFormSubmit().
 *
 * @param  {string} quotNum  Nomor penawaran
 * @return {boolean}         true jika valid, false jika gagal
 */
function prepareEditSubmit(quotNum) {
    const prefix = 'edit-' + quotNum;

    hideModalError('editModal-' + quotNum);

    const groups = serializeGroups(prefix);
    const jsonInput = document.getElementById('editGroupsJson-' + quotNum);

    // Validasi groups
    if (groups.length === 0) {
        showModalError('editModal-' + quotNum, 'Minimal 1 kelompok harus ditambahkan.');
        return false;
    }

    for (const g of groups) {
        if (!g.name) {
            showModalError('editModal-' + quotNum, 'Nama kelompok tidak boleh kosong.');
            return false;
        }
        if (g.items.length === 0) {
            showModalError('editModal-' + quotNum, `Kelompok "${g.name}" harus memiliki minimal 1 item.`);
            return false;
        }
    }

    // Atur JSON
    jsonInput.value = JSON.stringify(groups);

    // Indikator loading
    const submitBtn = document.getElementById('submit-btn-editModal-' + quotNum);
    if (submitBtn) {
        const originalText = submitBtn.innerHTML;
        if (!handleFormSubmit(submitBtn, originalText, 'Memperbarui...')) {
            return false;
        }
    }

    return true;
}

/* ==========================================
 * VALIDASI REKENING PEMBAYARAN
 * ========================================== */

/**
 * Memvalidasi rekening pembayaran dalam modal tertentu.
 * Tombol submit akan di-disable jika tidak ada checkbox yang dicentang.
 *
 * Alur:
 * 1. Cari modal berdasarkan modalId; jika tidak ada, langsung return.
 * 2. Kumpulkan semua checkbox .payment-account-checkbox di dalam modal
 *    dan cek apakah minimal satu tercentang.
 * 3. Tentukan tombol submit berdasarkan modalId (addModal vs edit).
 * 4. Disable / beri class opacity saat tidak ada yang dipilih.
 * 5. Kembalikan status anyChecked.
 *
 * @param  {string} modalId  ID modal (contoh: 'addModal' atau 'editModal-1/1/ALU/26')
 */
function validatePaymentSelection(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return false;

    const checkboxes = modal.querySelectorAll('.payment-account-checkbox');
    const anyChecked = Array.from(checkboxes).some(cb => cb.checked);

    // Cari submit button berdasarkan modal ID
    let submitBtn;
    if (modalId === 'addModal') {
        submitBtn = document.getElementById('submit-btn-addModal');
    } else {
        submitBtn = document.getElementById('submit-btn-' + modalId);
    }

    if (submitBtn) {
        submitBtn.disabled = !anyChecked;
        submitBtn.classList.toggle('opacity-50', !anyChecked);
        submitBtn.classList.toggle('cursor-not-allowed', !anyChecked);
    }

    return anyChecked;
}

/* ==========================================
 * SUBMIT FORM HAPUS DENGAN LOADING
 * ========================================== */

/**
 * Submit form hapus dengan loading indicator.
 * Dipanggil dari onclick pada modal konfirmasi hapus.
 *
 * Alur:
 * 1. Set tombol konfirmasi (#confirm-btn-deleteModal) ke state loading:
 *    spinner, disabled, dan class opacity/cursor-not-allowed.
 * 2. Submit form #deleteForm.
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

/* ==========================================
 * STATUS TOMBOL HAPUS
 * ========================================== */

/**
 * Memperbarui status tombol hapus berdasarkan checkbox yang dipilih.
 */
function updateDeleteButtonState() {
    const deleteBtn = document.getElementById('delete-button');
    const anyChecked = Array.from(document.querySelectorAll('input[name="ids[]"]')).some(cb => cb.checked);
    if (deleteBtn) {
        deleteBtn.disabled = !anyChecked;
        deleteBtn.classList.toggle('opacity-50', !anyChecked);
        deleteBtn.classList.toggle('cursor-not-allowed', !anyChecked);
    }
}

/* ==========================================
 * INISIALISASI HALAMAN
 * ========================================== */

/**
 * Inisialisasi seluruh interaksi halaman saat DOM siap.
 *
 * Bagian-bagian penting:
 *
 * 1. Override openModal global: setiap kali modal add/edit dibuka,
 *    error lama disembunyikan dan status checkbox rekening diperbarui.
 *
 * 2. Auto-generate nomor penawaran (MutationObserver):
 *    Alur:
 *    a. Observer dipasang pada #addModal dengan filter atribut 'class'.
 *    b. Setiap kali class berubah, periksa apakah modal menjadi tampil
 *       (tidak mengandung kelas 'hidden').
 *    c. Jika tampil, fetch URL dari meta aluminium-quotation-get-next-number.
 *    d. Isi #addQuotationNumberDisplay dengan quotation_number hasil fetch,
 *       sehingga nomor otomatis selalu fresh setiap kali modal dibuka.
 *
 * 3. Checkbox "Pilih Semua": tandai/batalkan semua checkbox ids[] dan
 *    perbarui tombol hapus.
 *
 * 4. Checkbox individual: sinkronkan status select all.
 *
 * 5. Validasi rekening pembayaran untuk modal add dan semua modal edit.
 *
 * 6. Form hapus: cegah double submit saat tombol sedang disabled.
 */
document.addEventListener('DOMContentLoaded', function() {

    // ─── Override openModal untuk menyembunyikan error & init checkboxes ───
    const originalOpenModal = window.openModal;
    if (typeof originalOpenModal === 'function') {
        window.openModal = function(id) {
            originalOpenModal(id);

            if (id === 'addModal') {
                hideModalError('add');
                validatePaymentSelection('addModal');
            } else if (id.startsWith('editModal-')) {
                const quotNum = id.replace('editModal-', '');
                hideModalError('editModal-' + quotNum);
                validatePaymentSelection('editModal-' + quotNum);
            }
        };
    }

    // ─── Auto-generate nomor penawaran saat addModal dibuka ───
    const addModal = document.getElementById('addModal');
    const getNextNumberUrl = document.querySelector('meta[name="aluminium-quotation-get-next-number"]')?.content;
    if (addModal && getNextNumberUrl) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(m) {
                if (m.target.id === 'addModal' && !m.target.classList.contains('hidden')) {
                    fetch(getNextNumberUrl)
                        .then(r => r.json())
                        .then(data => {
                            const el = document.getElementById('addQuotationNumberDisplay');
                            if (el) el.textContent = data.quotation_number;
                        });
                }
            });
        });
        observer.observe(addModal, { attributes: true, attributeFilter: ['class'] });
    }

    // ─── Checkbox Pilih Semua ───
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('input[name="ids[]"]').forEach(cb => {
                cb.checked = selectAll.checked;
            });
            updateDeleteButtonState();
        });
    }

    // ─── Listener checkbox individual ───
    document.querySelectorAll('input[name="ids[]"]').forEach(cb => {
        cb.addEventListener('change', function() {
            if (selectAll && !this.checked) {
                selectAll.checked = false;
            }
            if (selectAll) {
                const allChecked = Array.from(document.querySelectorAll('input[name="ids[]"]')).every(c => c.checked);
                selectAll.checked = allChecked;
            }
            updateDeleteButtonState();
        });
    });

    updateDeleteButtonState();

    // ─── Validasi rekening pembayaran: Modal Tambah ───
    document.querySelectorAll('#addModal .payment-account-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            validatePaymentSelection('addModal');
        });
    });
    validatePaymentSelection('addModal');

    // ─── Validasi rekening pembayaran: Modal Edit ───
    document.querySelectorAll('[id^="editModal-"]').forEach(modal => {
        const modalId = modal.id;
        modal.querySelectorAll('.payment-account-checkbox').forEach(cb => {
            cb.addEventListener('change', function() {
                validatePaymentSelection(modalId);
            });
        });
        validatePaymentSelection(modalId);
    });

    // ─── Form Hapus: Cegah Double Submit ───
    const deleteForm = document.getElementById('deleteForm');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('confirm-btn-deleteModal');
            if (submitBtn && submitBtn.disabled) {
                e.preventDefault();
                return false;
            }
        });
    }
});

// ─── Reset isSubmitting Flag Saat Halaman Dimuat Kembali ──────────────────
/**
 * Reset status submit & tombol hapus saat halaman dimuat dari cache
 * (browser back/forward navigation).
 *
 * Memanggil resetFormSubmitState() (shared) agar flag isSubmitting
 * tidak terkunci, dan updateDeleteButtonState() agar tombol hapus
 * mengikuti kondisi checkbox terkini.
 */
window.addEventListener('pageshow', function() {
    resetFormSubmitState();
    updateDeleteButtonState();
});

// ─── Expose ke global scope agar bisa dipanggil dari inline onclick di Blade ──
window.addGroup = addGroup;
window.addItemToGroup = addItemToGroup;
window.removeItem = removeItem;
window.removeGroup = removeGroup;
window.prepareAddSubmit = prepareAddSubmit;
window.prepareEditSubmit = prepareEditSubmit;
window.submitDeleteForm = submitDeleteForm;
