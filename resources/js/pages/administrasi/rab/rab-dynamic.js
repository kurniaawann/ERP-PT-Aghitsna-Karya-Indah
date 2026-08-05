/**
 * RAB (Rencana Anggaran Biaya) - JavaScript Struktur Dinamis
 *
 * Modul ini menangani:
 * - Manajemen hierarki Kategori / Sub-Kategori / Item (tambah, hapus, edit)
 * - Perhitungan total harga secara realtime (item, sub-kategori, kategori, grand total)
 * - Manajemen Biaya Lain-Lain (Miscellaneous Costs)
 * - Persiapan data JSON untuk form submission (tambah & edit)
 *
 * Fungsi diekspos ke global (window) karena dipanggil dari inline handler Blade.
 */

/* ==========================================
 * PERHITUNGAN ULANG TERPUSAT
 * ========================================== */

/**
 * Menghitung ulang seluruh total pada kontainer yang baru dimodifikasi.
 *
 * Alur:
 * 1. Terima elemen kontainer root yang baru saja berubah (tambah/hapus blok).
 * 2. Tentukan konteks dari ID kontainer:
 *    - `rabCategoriesContainer` (modal tambah) -> panggil calculateAndUpdateGrandTotal().
 *    - `editRabCategoriesContainer{rabNumber}` (modal edit) -> panggil calculateAndUpdateGrandTotalForEditModal(rabNumber).
 * 3. Dengan demikian satu titik masuk ini menjaga konsistensi tampilan
 *    antara modal tambah dan modal edit setelah struktur dimanipulasi.
 *
 * @param {HTMLElement|null} contextContainer - Elemen kontainer root yang dimodifikasi.
 */
function recalculateAllTotals(contextContainer) {
    if (!contextContainer) return;
    const containerId = contextContainer.id;
    if (containerId === 'rabCategoriesContainer') {
        calculateAndUpdateGrandTotal();
    } else if (containerId.startsWith('editRabCategoriesContainer')) {
        const rabNumber = containerId.replace('editRabCategoriesContainer', '');
        calculateAndUpdateGrandTotalForEditModal(rabNumber);
    }
}

/* ==========================================
 * MANAJEMEN KATEGORI, SUB-KATEGORI, ITEM
 * ========================================== */

// Tambah Kategori - dengan prefix opsional dan categoryData untuk modal edit
/**
 * Menambahkan blok kategori baru beserta sub-kategori dan item (dengan prefill bila ada).
 *
 * Alur:
 * 1. Tentukan kontainer tujuan berdasarkan pola `prefixOrContainerId`:
 *    - `editRabCategoriesContainer...` -> kontainer langsung modal edit.
 *    - `edit-...` -> prefiks edit; kontainer dicari dari `editRabCategoriesContainer` + suffix.
 *    - ID kontainer biasa (mis. `rabCategoriesContainer`) -> kontainer modal tambah.
 *    - tanpa parameter -> fallback ke `rabCategoriesContainer`.
 * 2. Bangun elemen `.category-block` berisi input nama kategori (Romawi),
 *    kontainer sub-kategori, dan tombol hapus kategori.
 * 3. Isi ulang data existing dari `categoryData` (nama kategori, sub-kategori, item)
 *    ke dalam markup ketika mode edit (prefill value + sub_harga di data-sub-harga).
 * 4. Append blok ke kontainer, ikat listener harga, lalu hitung ulang total.
 *
 * @param {string|undefined} prefixOrContainerId - ID kontainer atau prefiks modal edit.
 * @param {Object|undefined} categoryData - Data kategori existing untuk prefill modal edit.
 */
function addCategoryBlock(prefixOrContainerId, categoryData) {
    let container, prefix;

    // Jika containerID dikirim dengan pattern 'editRabCategoriesContainer...'
    if (prefixOrContainerId && prefixOrContainerId.startsWith('editRabCategoriesContainer')) {
        container = document.getElementById(prefixOrContainerId);
        categoryData = categoryData || {};
    }
    // Jika containerID dikirim dengan pattern 'edit-...'
    else if (prefixOrContainerId && prefixOrContainerId.startsWith('edit-')) {
        prefix = prefixOrContainerId;
        container = document.getElementById('editRabCategoriesContainer' + prefix.replace('edit-', ''));
        categoryData = categoryData || {};
    }
    // Untuk add modal dengan containerId = 'rabCategoriesContainer'
    else if (prefixOrContainerId) {
        container = document.getElementById(prefixOrContainerId);
        categoryData = categoryData || {};
    }
    // Jika tidak ada parameter (add modal default)
    else {
        container = document.getElementById('rabCategoriesContainer');
        categoryData = {};
    }

    if (!container) {
        console.error('Container tidak ditemukan untuk addCategoryBlock', prefixOrContainerId);
        return;
    }

    const categoryBlock = document.createElement('div');
    categoryBlock.className = 'category-block border rounded p-3 mb-3';
    categoryBlock.innerHTML = `
        <div class="mb-3">
            <label class="block text-text-primary mb-1 text-sm font-semibold">Kategori (Romawi)</label>
            <div class="flex gap-2">
                <input type="text" class="flex-1 w-full border rounded p-2 category-name"
                    placeholder="Contoh: Pekerjaan Persiapan" value="${categoryData.category_name || ''}" required>
                <button type="button" onclick="removeCategoryBlock(this)" class="btn btn-sm btn-danger" title="Hapus kategori">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </div>

        <div class="subcategories-container space-y-3 mb-3">
            ${(categoryData.subcategories || []).map(subcategory => `
                                <div class="subcategory-block border rounded p-3 bg-gray-50">
                                    <div class="mb-3">
                                        <label class="block text-text-primary mb-1 text-sm font-semibold">Sub-Kategori (Angka)</label>
                                        <input type="text" class="w-full border rounded p-2 subcategory-name"
                                            placeholder="Contoh: Pembongkaran" value="${subcategory.subcategory_name || ''}" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="block text-text-primary mb-2 text-sm font-semibold">Item Pekerjaan (a, b, c...)</label>
                                        <div class="items-container space-y-2">
                                            ${(subcategory.items || []).map(item => `
                    <div class="item-block bg-surface-base rounded border border-border-strong p-3 flex flex-col gap-2"
                        data-sub-harga="${item.sub_harga || 0}">
                        <input type="text" class="w-full border border-border-strong rounded p-2 item-description bg-surface-base text-text-input"
                            placeholder="Masukkan item pekerjaan" value="${item.item_description || ''}" required>
                        <input type="number" class="w-full border border-border-strong rounded p-2 item-volume bg-surface-base text-text-input" placeholder="Vol"
                            min="0" step="0.01" value="${item.volume ?? 0}" required>
                        <input type="text" class="w-full border border-border-strong rounded p-2 item-unit bg-surface-base text-text-input" placeholder="Satuan"
                            value="${item.unit || ''}" maxlength="50" required>
                        <input type="number" class="w-full border border-border-strong rounded p-2 item-unit-price bg-surface-base text-text-input" placeholder="Harga"
                            min="0" step="0.01" value="${item.unit_price ?? 0}" required>
                        <div class="w-full px-3 py-2 bg-info-light border border-info-light rounded text-right">
                            <span class="item-sub-total-price text-sm font-semibold text-info">Rp ${new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(item.sub_harga || 0).replace('Rp\u00a0', 'Rp ')}</span>
                        </div>
                        <button type="button" onclick="removeItemBlock(this)" class="btn btn-sm bg-btn-delete hover:bg-btn-delete-hover text-white">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                `).join('')}
                                        </div>
                                        <button type="button" onclick="addItemBlock(this)" class="btn btn-sm btn-outline-secondary w-full mt-2">
                                            <i class="fa-solid fa-plus"></i> Tambah Item
                                        </button>
                                    </div>

                                    <div class="bg-info-light border border-info-light rounded p-3 mb-3">
                                        <p class="text-sm text-text-heading"><strong>Total Sub-Kategori:</strong> <span class="sub-total-price font-bold text-lg text-info">Rp ${new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(subcategory.sub_harga || 0).replace('Rp\u00a0', 'Rp ')}</span></p>
                                    </div>

                                    <button type="button" onclick="removeSubcategoryBlock(this)" class="btn btn-sm btn-outline-danger w-full">
                                        <i class="fa-solid fa-trash"></i> Hapus Sub-Kategori
                                    </button>
                                </div>
                            `).join('')}
        </div>

        <button type="button" onclick="addSubcategoryBlock(this)" class="btn btn-sm btn-outline-secondary w-full">
            <i class="fa-solid fa-plus"></i> Tambah Sub-Kategori
        </button>
    `;

    container.appendChild(categoryBlock);
    attachPriceListeners();
    recalculateAllTotals(container);
}

// Tambah Sub-Kategori
/**
 * Menambahkan blok sub-kategori baru di dalam kategori yang dipilih.
 *
 * Alur:
 * 1. Cari `.category-block` induk dari tombol yang diklik.
 * 2. Bangun elemen `.subcategory-block` berisi input nama sub-kategori,
 *    satu baris item default, tombol "Tambah Item", tampilan total sub-kategori,
 *    dan tombol "Hapus Sub-Kategori".
 * 3. Append ke kontainer `.subcategories-container`.
 * 4. Ikat listener harga dan hitung ulang total dari root kontainer
 *    (add atau edit modal).
 *
 * @param {HTMLElement} button - Tombol "Tambah Sub-Kategori" yang diklik.
 */
function addSubcategoryBlock(button) {
    const categoryBlock = button.closest('.category-block');
    const container = categoryBlock.querySelector('.subcategories-container');

    const subcategoryBlock = document.createElement('div');
    subcategoryBlock.className = 'subcategory-block border border-border-strong rounded p-3 bg-surface-secondary';
    subcategoryBlock.innerHTML = `
        <div class="mb-3">
            <label class="block text-text-primary mb-1 text-sm font-semibold">Sub-Kategori (Angka)</label>
            <input type="text" class="w-full border border-border-strong rounded p-2 subcategory-name bg-surface-base text-text-input"
                placeholder="Contoh: Pembongkaran" required>
        </div>

        <div class="mb-3">
            <label class="block text-text-primary mb-2 text-sm font-semibold">Item Pekerjaan (a, b, c...)</label>
            <div class="items-container space-y-2">
                <div class="item-block bg-surface-base rounded border border-border-strong p-3 flex flex-col gap-2">
                    <input type="text" class="w-full border border-border-strong rounded p-2 item-description bg-surface-base text-text-input" placeholder="Masukkan item pekerjaan" required>
                    <input type="number" class="w-full border border-border-strong rounded p-2 item-volume bg-surface-base text-text-input" placeholder="Vol" min="0" step="0.01" required>
                    <input type="text" class="w-full border border-border-strong rounded p-2 item-unit bg-surface-base text-text-input" placeholder="Satuan" maxlength="50" required>
                    <input type="number" class="w-full border border-border-strong rounded p-2 item-unit-price bg-surface-base text-text-input" placeholder="Harga" min="0" step="0.01" required>
                    <div class="w-full px-3 py-2 bg-info-light border border-info-light rounded text-right">
                        <span class="item-sub-total-price text-sm font-semibold text-info">Rp 0</span>
                    </div>
                    <button type="button" onclick="removeItemBlock(this)" class="btn btn-sm bg-btn-delete hover:bg-btn-delete-hover text-white">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
            <button type="button" onclick="addItemBlock(this)" class="btn btn-sm btn-outline-secondary w-full mt-2">
                <i class="fa-solid fa-plus"></i> Tambah Item
            </button>
        </div>

        <div class="bg-info-light border border-info-light rounded p-3 mb-3">
            <p class="text-sm text-text-heading"><strong>Total Sub-Kategori:</strong> <span class="sub-total-price font-bold text-lg text-info">Rp 0</span></p>
        </div>

        <button type="button" onclick="removeSubcategoryBlock(this)" class="btn btn-sm bg-btn-delete hover:bg-btn-delete-hover text-white w-full">
            <i class="fa-solid fa-trash"></i> Hapus Sub-Kategori
        </button>
    `;

    container.appendChild(subcategoryBlock);
    attachPriceListeners();
    const rootContainer = categoryBlock.closest('#rabCategoriesContainer, [id^="editRabCategoriesContainer"]');
    if (rootContainer) recalculateAllTotals(rootContainer);
}

// Tambah Item
/**
 * Menambahkan satu baris item pekerjaan baru di dalam sub-kategori.
 *
 * Alur:
 * 1. Cari `.subcategory-block` induk dari tombol yang diklik.
 * 2. Bangun elemen `.item-block` berisi input deskripsi, volume, satuan, harga,
 *    span subtotal (Rp 0), dan tombol hapus item.
 * 3. Append ke kontainer `.items-container`.
 * 4. Ikat listener volume/harga ke item baru via `attachPriceListenersToItem`
 *    (redundancy selain event delegation).
 * 5. Hitung ulang total dari root kontainer.
 *
 * @param {HTMLElement} button - Tombol "Tambah Item" yang diklik.
 */
function addItemBlock(button) {
    const subcategoryBlock = button.closest('.subcategory-block');
    const container = subcategoryBlock.querySelector('.items-container');

    const itemBlock = document.createElement('div');
    itemBlock.className = 'item-block bg-surface-base rounded border border-border-strong p-3 flex flex-col gap-2';
    itemBlock.innerHTML = `
        <input type="text" class="w-full border border-border-strong rounded p-2 item-description bg-surface-base text-text-input" placeholder="Masukkan item pekerjaan" required>
        <input type="number" class="w-full border border-border-strong rounded p-2 item-volume bg-surface-base text-text-input" placeholder="Vol" min="0" step="0.01" required>
        <input type="text" class="w-full border border-border-strong rounded p-2 item-unit bg-surface-base text-text-input" placeholder="Satuan" maxlength="50" required>
        <input type="number" class="w-full border border-border-strong rounded p-2 item-unit-price bg-surface-base text-text-input" placeholder="Harga" min="0" step="0.01" required>
        <div class="w-full px-3 py-2 bg-info-light border border-info-light rounded text-right">
            <span class="item-sub-total-price text-sm font-semibold text-info">Rp 0</span>
        </div>
        <button type="button" onclick="removeItemBlock(this)" class="btn btn-sm bg-btn-delete hover:bg-btn-delete-hover text-white">
            <i class="fa-solid fa-trash"></i>
        </button>
    `;

    container.appendChild(itemBlock);
    // Ikat event listener volume dan harga pada item yang baru ditambahkan
    attachPriceListenersToItem(itemBlock);
    const rootContainer = subcategoryBlock.closest('#rabCategoriesContainer, [id^="editRabCategoriesContainer"]');
    if (rootContainer) recalculateAllTotals(rootContainer);
}

// Hapus Kategori
/**
 * Menghapus blok kategori beserta seluruh sub-kategori dan item di dalamnya.
 *
 * @param {HTMLElement} button - Tombol hapus pada blok kategori.
 */
function removeCategoryBlock(button) {
    const block = button.closest('.category-block');
    const rootContainer = block.closest('#rabCategoriesContainer, [id^="editRabCategoriesContainer"]');
    block.remove();
    if (rootContainer) recalculateAllTotals(rootContainer);
}

// Hapus Sub-Kategori
/**
 * Menghapus blok sub-kategori beserta item-item di dalamnya.
 *
 * @param {HTMLElement} button - Tombol hapus pada blok sub-kategori.
 */
function removeSubcategoryBlock(button) {
    const block = button.closest('.subcategory-block');
    const rootContainer = block.closest('#rabCategoriesContainer, [id^="editRabCategoriesContainer"]');
    block.remove();
    if (rootContainer) recalculateAllTotals(rootContainer);
}

// Hapus Item
/**
 * Menghapus satu baris item pekerjaan.
 *
 * @param {HTMLElement} button - Tombol hapus pada baris item.
 */
function removeItemBlock(button) {
    const block = button.closest('.item-block');
    const rootContainer = block.closest('#rabCategoriesContainer, [id^="editRabCategoriesContainer"]');
    block.remove();
    if (rootContainer) recalculateAllTotals(rootContainer);
}

/* ==========================================
 * KALKULATOR HARGA
 * ========================================== */

/**
 * Mengikat event delegation untuk input volume/harga pada level document.
 *
 * Alur:
 * 1. Lepas listener lama (`removeEventListener`) agar tidak terjadi duplikasi
 *    saat fungsi dipanggil berulang kali.
 * 2. Pasang satu listener `input` baru yang menangkap seluruh elemen.
 * 3. Event delegation dipilih agar elemen yang ditambahkan dinamis
 *    (item/sub-kategori/kategori baru) tetap ikut terdeteksi tanpa
 *    harus mengikat listener satu per satu.
 */
function attachPriceListeners() {
    // Gunakan event delegation pada document untuk menangkap semua input volume/harga
    // termasuk yang ditambahkan secara dinamis
    document.removeEventListener('input', handlePriceInput);
    document.addEventListener('input', handlePriceInput);
}

/**
 * Handler event input terpusat untuk input volume (`item-volume`) dan harga (`item-unit-price`).
 *
 * Alur:
 * 1. Ambil target event (`e.target`).
 * 2. Jika target memiliki class `item-volume` atau `item-unit-price`,
 *    panggil `updatePricesForContext(target)` agar perhitungan
 *    mengikuti konteks modal (tambah vs edit).
 *
 * @param {Event} e - Event `input` yang dilempar oleh document.
 */
function handlePriceInput(e) {
    const target = e.target;
    if (target.classList.contains('item-volume') || target.classList.contains('item-unit-price')) {
        updatePricesForContext(target);
    }
}

/**
 * Ikat listener langsung pada elemen item block yang baru (untuk redundancy).
 *
 * Alur:
 * 1. Cari input `.item-volume` dan `.item-unit-price` di dalam itemBlock.
 * 2. Pasang listener `input` pada masing-masing yang memanggil
 *    `updatePricesForContext(this)`.
 * 3. Dibutuhkan sebagai cadangan karena elemen dibuat secara dinamis;
 *    dijalankan setelah `addItemBlock` dan saat render item dari data edit.
 *
 * @param {HTMLElement} itemBlock - Elemen `.item-block` yang baru ditambahkan.
 */
function attachPriceListenersToItem(itemBlock) {
    const volumeInput = itemBlock.querySelector('.item-volume');
    const priceInput = itemBlock.querySelector('.item-unit-price');

    if (volumeInput) {
        volumeInput.addEventListener('input', function() {
            updatePricesForContext(this);
        });
    }

    if (priceInput) {
        priceInput.addEventListener('input', function() {
            updatePricesForContext(this);
        });
    }
}

/**
 * Memperbarui harga berdasarkan konteks modal: tambah (add) atau edit.
 *
 * Alur:
 * 1. Cari kontainer root terdekat dari elemen:
 *    - `editRabCategoriesContainer...` -> modal edit.
 *    - `rabCategoriesContainer` -> modal tambah.
 * 2. Jika tidak ketemu, fallback cari `.category-block` lalu naik ke
 *    kontainer dengan id mengandung "Container".
 * 3. Jika tetap tidak ketemu, gunakan `updatePrices()` (default modal tambah).
 * 4. Tentukan konteks dari ID kontainer:
 *    - berawalan `editRabCategoriesContainer` -> ekstrak rabNumber,
 *      panggil `updatePricesForEditModalContext(rabNumber)`.
 *    - selain itu -> panggil `updatePrices()`.
 *
 * @param {HTMLElement} element - Elemen input volume/harga yang berubah.
 */
function updatePricesForContext(element) {
    // Tentukan konteks: apakah ini add modal atau edit modal
    let container = element.closest('[id^="editRabCategoriesContainer"], #rabCategoriesContainer');

    if (!container) {
        // Fallback: coba temukan blok kategori
        container = element.closest('.category-block')?.closest('[id*="Container"]');
    }

    if (!container) {
        // Jika masih tidak ketemu, gunakan updatePrices default
        updatePrices();
        return;
    }

    // Tentukan konteks (add atau edit)
    const containerId = container.id;

    if (containerId.startsWith('editRabCategoriesContainer')) {
        // Konteks modal edit
        const rabNumber = containerId.replace('editRabCategoriesContainer', '');
        updatePricesForEditModalContext(rabNumber);
    } else {
        // Konteks modal tambah
        updatePrices();
    }
}

/**
 * Menghitung dan memperbarui tampilan harga pada modal edit tertentu.
 *
 * Alur:
 * 1. Iterasi seluruh `.subcategory-block` pada `editRabCategoriesContainer{rabNumber}`.
 * 2. Untuk tiap item: hitung `itemTotal = volume x harga satuan`;
 *    jika hasilnya 0, gunakan `data-sub-harga` (sub_harga data lama) sebagai fallback.
 * 3. Tampilkan subtotal item dengan format Rupiah id-ID, lalu akumulasi
 *    menjadi total sub-kategori.
 * 4. Tampilkan total sub-kategori dan akumulasi ke grand total kategori.
 * 5. Update elemen `editTotalCategoriesPrice{rabNumber}`.
 * 6. Panggil `calculateAndUpdateGrandTotalForEditModal(rabNumber)` untuk
 *    menambahkan biaya lain-lain ke grand total.
 *
 * @param {string|number} rabNumber - Nomor RAB (suffix ID kontainer modal edit).
 */
function updatePricesForEditModalContext(rabNumber) {
    let grandTotal = 0;
    // HANYA hitung dari Edit Modal tertentu (editRabCategoriesContainer{rabNumber})
    const editModalContainer = document.getElementById('editRabCategoriesContainer' + rabNumber);

    if (editModalContainer) {
        const subcategoryBlocks = editModalContainer.querySelectorAll('.subcategory-block');

        subcategoryBlocks.forEach(block => {
            let totalPrice = 0;

            block.querySelectorAll('.item-block').forEach(itemBlock => {
                const volume = parseFloat(itemBlock.querySelector('.item-volume')?.value) || 0;
                const unitPrice = parseFloat(itemBlock.querySelector('.item-unit-price')?.value) ||
                    0;
                const itemTotal = volume * unitPrice;
                const itemPriceDisplay = itemBlock.querySelector('.item-sub-total-price');

                // Gunakan sub_harga dari data attribute jika volume*price = 0
                const displayTotal = itemTotal > 0 ? itemTotal : (parseInt(itemBlock.dataset.subHarga) || 0);

                if (itemPriceDisplay) {
                    itemPriceDisplay.textContent = new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 0
                    }).format(displayTotal);
                }

                totalPrice += displayTotal;
            });

            const priceDisplay = block.querySelector('.sub-total-price');

            if (priceDisplay) {
                priceDisplay.textContent = new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(totalPrice);
            }

            grandTotal += totalPrice;
        });
    }

    // Perbarui tampilan total kategori
    const totalCategoriesElement = document.getElementById('editTotalCategoriesPrice' + rabNumber);
    if (totalCategoriesElement) {
        totalCategoriesElement.textContent = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(grandTotal);
    }

    // Hitung dan update grand total keseluruhan (kategori + misc costs)
    calculateAndUpdateGrandTotalForEditModal(rabNumber);
}

/**
 * Menghitung dan memperbarui tampilan harga pada modal tambah.
 *
 * Alur:
 * 1. Iterasi seluruh `.subcategory-block` pada `rabCategoriesContainer`.
 * 2. Untuk tiap item: hitung `itemTotal = volume x harga satuan`;
 *    jika 0, gunakan `data-sub-harga` sebagai fallback, lalu tampilkan subtotal item.
 * 3. Akumulasi menjadi total sub-kategori dan tampilkan.
 * 4. Akumulasi seluruh sub-kategori menjadi grand total kategori.
 * 5. Update elemen `grandTotalPrice` dan `totalCategoriesPrice`.
 * 6. Panggil `calculateAndUpdateGrandTotal()` untuk menambahkan biaya lain-lain.
 */
function updatePrices() {
    let grandTotal = 0;
    // HANYA hitung dari Add Modal (rabCategoriesContainer)
    const addModalContainer = document.getElementById('rabCategoriesContainer');

    if (addModalContainer) {
        const subcategoryBlocks = addModalContainer.querySelectorAll('.subcategory-block');

        subcategoryBlocks.forEach(block => {
            let totalPrice = 0;

            block.querySelectorAll('.item-block').forEach(itemBlock => {
                const volume = parseFloat(itemBlock.querySelector('.item-volume')?.value) || 0;
                const unitPrice = parseFloat(itemBlock.querySelector('.item-unit-price')?.value) ||
                    0;
                const itemTotal = volume * unitPrice;
                const itemPriceDisplay = itemBlock.querySelector('.item-sub-total-price');

                // Gunakan sub_harga dari data attribute jika volume*price = 0
                const displayTotal = itemTotal > 0 ? itemTotal : (parseInt(itemBlock.dataset.subHarga) || 0);

                if (itemPriceDisplay) {
                    itemPriceDisplay.textContent = new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 0
                    }).format(displayTotal);
                }

                totalPrice += displayTotal;
            });

            const priceDisplay = block.querySelector('.sub-total-price');

            if (priceDisplay) {
                priceDisplay.textContent = new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(totalPrice);
            }

            grandTotal += totalPrice;
        });
    }

    // Perbarui grand total jika elemen ada
    const grandTotalElement = document.getElementById('grandTotalPrice');
    if (grandTotalElement) {
        grandTotalElement.textContent = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(grandTotal);
    }

    // Perbarui tampilan total kategori
    const totalCategoriesElement = document.getElementById('totalCategoriesPrice');
    if (totalCategoriesElement) {
        totalCategoriesElement.textContent = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(grandTotal);
    }

    // Hitung dan update grand total keseluruhan (kategori + misc costs)
    calculateAndUpdateGrandTotal();
}

/* ==========================================
 * INISIALISASI
 * ========================================== */

/**
 * Inisialisasi saat DOM siap:
 * 1. Pasang listener harga (event delegation).
 * 2. Hitung dan tampilkan harga awal (updatePrices + grand total)
 *    untuk form tambah yang mungkin sudah berisi data default.
 */
document.addEventListener('DOMContentLoaded', function () {
    // Initialize price listeners
    attachPriceListeners();
    updatePrices();
    calculateAndUpdateGrandTotal();
});

/* ==========================================
 * PREPARE DATA FOR SUBMISSION
 * ========================================== */

/**
 * Menyiapkan data RAB dari DOM (modal tambah) menjadi JSON untuk dikirim via hidden input.
 *
 * Alur:
 * 1. Iterasi seluruh `.category-block` pada `rabCategoriesContainer`.
 * 2. Untuk tiap kategori: ambil `category_name`, lalu iterasi `.subcategory-block`.
 * 3. Untuk tiap sub-kategori: ambil `subcategory_name`, iterasi `.item-block`,
 *    baca deskripsi/volume/satuan/harga, hitung `sub_harga = volume x harga`,
 *    dan akumulasi total sub-kategori.
 * 4. Susun struktur nested:
 *    categories = [{ category_name, subcategories: [{ subcategory_name, sub_harga, items[] }] }].
 * 5. Serialisasi ke JSON dan isi hidden input `rabDataInput`.
 * 6. Kumpulkan biaya lain-lain dari `miscCostsContainer`
 *    (item_order, item_name, amount) dan isi hidden input `miscCostsDataInput`.
 *
 * @returns {boolean} true agar proses submit dilanjutkan.
 */
function prepareRABSubmit() {
    const categories = [];

    document.querySelectorAll('#rabCategoriesContainer .category-block').forEach(function(categoryEl) {
        const categoryData = {
            category_name: categoryEl.querySelector('.category-name').value,
            subcategories: []
        };

        categoryEl.querySelectorAll('.subcategory-block').forEach(function(subEl) {
            let subHarga = 0;

            const subcategoryData = {
                subcategory_name: subEl.querySelector('.subcategory-name').value,
                items: []
            };

            subEl.querySelectorAll('.item-block').forEach(function(itemEl) {
                const volume = parseFloat(itemEl.querySelector('.item-volume')
                    ?.value) || 0;
                const unitPrice = parseFloat(itemEl.querySelector('.item-unit-price')
                    ?.value) || 0;
                const itemSubHarga = volume * unitPrice;

                const itemData = {
                    item_description: itemEl.querySelector('.item-description')
                        .value,
                    volume: volume,
                    unit: itemEl.querySelector('.item-unit').value,
                    unit_price: unitPrice,
                    sub_harga: itemSubHarga
                };
                subHarga += itemSubHarga;
                subcategoryData.items.push(itemData);
            });

            subcategoryData.sub_harga = subHarga;

            categoryData.subcategories.push(subcategoryData);
        });

        categories.push(categoryData);
    });

    document.getElementById('rabDataInput').value = JSON.stringify(categories);

    // Collect miscellaneous costs data
    const miscContainer = document.getElementById('miscCostsContainer');
    const miscCostsData = [];
    if (miscContainer) {
        miscContainer.querySelectorAll('.misc-cost-item').forEach(function(item, index) {
            miscCostsData.push({
                item_order: index + 1,
                item_name: item.querySelector('.misc-item-name').value,
                amount: parseInt(item.querySelector('.misc-item-amount').value) || 0
            });
        });
    }
    const miscInput = document.getElementById('miscCostsDataInput');
    if (miscInput) {
        miscInput.value = JSON.stringify(miscCostsData);
    }

    return true;
}

/* ==========================================
 * FORM SUBMISSION HANDLER
 * ========================================== */

/**
 * Membuka modal edit untuk RAB tertentu.
 *
 * @param {string|number} rabNumber - Nomor RAB (suffix ID modal `editRABModal`).
 */
function editRAB(rabNumber) {
    openModal('editRABModal' + rabNumber);
}

/* ==========================================
 * PREPARE EDIT RAB DATA FOR SUBMISSION
 * ========================================== */

/**
 * Menyiapkan data RAB dari DOM (modal edit) menjadi JSON untuk dikirim via hidden input.
 *
 * Alur:
 * 1. Tentukan ID target dari rabNumber:
 *    - kontainer kategori: `editRabCategoriesContainer{rabNumber}`
 *    - hidden input kategori: `editRabDataInput{rabNumber}`
 *    - kontainer misc: `editMiscCostsContainer{rabNumber}`
 *    - hidden input misc: `editMiscCostsDataInput{rabNumber}`
 * 2. DOM-walk identik dengan `prepareRABSubmit`: kategori -> sub-kategori -> item,
 *    hitung `sub_harga` tiap item (volume x harga) dan total tiap sub-kategori.
 * 3. Serialisasi struktur kategori ke JSON dan isi hidden input kategori.
 * 4. Kumpulkan biaya lain-lain (item_order, item_name, amount) dan isi hidden input misc.
 *
 * @param {string|number} rabNumber - Nomor RAB (suffix ID form edit).
 * @returns {boolean} true jika berhasil; false jika kontainer tidak ditemukan.
 */
function prepareEditRABSubmit(rabNumber) {
    const containerId = 'editRabCategoriesContainer' + rabNumber;
    const inputId = 'editRabDataInput' + rabNumber;
    const miscContainerId = 'editMiscCostsContainer' + rabNumber;
    const miscInputId = 'editMiscCostsDataInput' + rabNumber;

    const categories = [];
    const container = document.getElementById(containerId);
    if (!container) return false;

    container.querySelectorAll('.category-block').forEach(function(categoryEl) {
        const categoryData = {
            category_name: categoryEl.querySelector('.category-name').value,
            subcategories: []
        };

        categoryEl.querySelectorAll('.subcategory-block').forEach(function(subEl) {
            let subHarga = 0;

            const subcategoryData = {
                subcategory_name: subEl.querySelector('.subcategory-name').value,
                items: []
            };

            subEl.querySelectorAll('.item-block').forEach(function(itemEl) {
                const volume = parseFloat(itemEl.querySelector('.item-volume')
                    ?.value) || 0;
                const unitPrice = parseFloat(itemEl.querySelector('.item-unit-price')
                    ?.value) || 0;
                const itemSubHarga = volume * unitPrice;

                const itemData = {
                    item_description: itemEl.querySelector('.item-description')
                        .value,
                    volume: volume,
                    unit: itemEl.querySelector('.item-unit').value,
                    unit_price: unitPrice,
                    sub_harga: itemSubHarga
                };
                subHarga += itemSubHarga;
                subcategoryData.items.push(itemData);
            });

            subcategoryData.sub_harga = subHarga;

            categoryData.subcategories.push(subcategoryData);
        });

        categories.push(categoryData);
    });

    document.getElementById(inputId).value = JSON.stringify(categories);

    // Collect miscellaneous costs data
    const miscContainer = document.getElementById(miscContainerId);
    const miscCostsData = [];
    if (miscContainer) {
        miscContainer.querySelectorAll('.misc-cost-item').forEach(function(item, index) {
            miscCostsData.push({
                item_order: index + 1,
                item_name: item.querySelector('.misc-item-name').value,
                amount: parseInt(item.querySelector('.misc-item-amount').value) || 0
            });
        });
    }
    const miscInput = document.getElementById(miscInputId);
    if (miscInput) {
        miscInput.value = JSON.stringify(miscCostsData);
    }

    return true;
}

/* ==========================================
 * HELPER: UPDATE GRAND TOTAL FOR ALL MODALS
 * ========================================== */

/**
 * Memperbarui total harga pada modal edit berdasarkan ID elemen grand total.
 *
 * Alur:
 * 1. Ekstrak rabNumber dari `editGrandTotalPrice{rabNumber}`.
 * 2. Hitung total kategori dari `editRabCategoriesContainer{rabNumber}`
 *    (per item: volume x harga, fallback `data-sub-harga`; diakumulasi ke sub-kategori).
 * 3. Update elemen grand total dan `editTotalCategoriesPrice{rabNumber}`.
 * 4. Panggil `calculateAndUpdateGrandTotalForEditModal(rabNumber)` untuk
 *    memasukkan biaya lain-lain ke grand total.
 *
 * @param {string} grandTotalElementId - ID elemen grand total modal edit.
 */
function updatePricesForEditModal(grandTotalElementId) {
    let grandTotal = 0;
    // Extract RAB number dari element ID (editGrandTotalPrice123 -> 123)
    const rabNumber = grandTotalElementId.replace('editGrandTotalPrice', '');
    // HANYA hitung dari Edit Modal tertentu
    const editModalContainer = document.getElementById('editRabCategoriesContainer' + rabNumber);

    if (editModalContainer) {
        const subcategoryBlocks = editModalContainer.querySelectorAll('.subcategory-block');

        subcategoryBlocks.forEach(block => {
            let totalPrice = 0;

            block.querySelectorAll('.item-block').forEach(itemBlock => {
                const volume = parseFloat(itemBlock.querySelector('.item-volume')?.value) || 0;
                const unitPrice = parseFloat(itemBlock.querySelector('.item-unit-price')
                    ?.value) || 0;
                const itemTotal = volume * unitPrice;
                const itemPriceDisplay = itemBlock.querySelector('.item-sub-total-price');

                // Gunakan sub_harga dari data attribute jika volume*price = 0
                const displayTotal = itemTotal > 0 ? itemTotal : (parseInt(itemBlock.dataset.subHarga) || 0);

                if (itemPriceDisplay) {
                    itemPriceDisplay.textContent = new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 0
                    }).format(displayTotal);
                }

                totalPrice += displayTotal;
            });

            const priceDisplay = block.querySelector('.sub-total-price');

            if (priceDisplay) {
                priceDisplay.textContent = new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(totalPrice);
            }

            grandTotal += totalPrice;
        });
    }

    const grandTotalElement = document.getElementById(grandTotalElementId);
    if (grandTotalElement) {
        grandTotalElement.textContent = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(grandTotal);

        // Perbarui tampilan total kategori
        const totalCategoriesElement = document.getElementById('editTotalCategoriesPrice' + rabNumber);
        if (totalCategoriesElement) {
            totalCategoriesElement.textContent = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(grandTotal);
        }

        // Hitung dan update grand total keseluruhan (kategori + misc costs)
        calculateAndUpdateGrandTotalForEditModal(rabNumber);
    }
}

/* ==========================================
 * CALCULATE AND UPDATE GRAND TOTAL
 * ========================================== */

/**
 * Menghitung dan menampilkan grand total modal tambah (kategori + biaya lain-lain).
 *
 * Alur:
 * 1. Hitung totalCategories: iterasi `.subcategory-block` pada `rabCategoriesContainer`,
 *    untuk tiap item hitung volume x harga (fallback `data-sub-harga`),
 *    akumulasi ke subTotal lalu tampilkan ke `.sub-total-price`.
 * 2. Hitung totalMiscCosts: jumlahkan seluruh `.misc-item-amount` pada `miscCostsContainer`.
 * 3. Update elemen `totalCategoriesPrice`, `totalMiscCostsPrice`,
 *    dan `grandTotalPrice` (= totalCategories + totalMiscCosts) dengan format Rupiah.
 */
function calculateAndUpdateGrandTotal() {
    // Hitung total kategori HANYA dari Add Modal (rabCategoriesContainer)
    let totalCategories = 0;
    const addModalContainer = document.getElementById('rabCategoriesContainer');

    if (addModalContainer) {
        const subcategoryBlocks = addModalContainer.querySelectorAll('.subcategory-block');

        subcategoryBlocks.forEach(block => {
            let subTotal = 0;

            block.querySelectorAll('.item-block').forEach(itemBlock => {
                const volume = parseFloat(itemBlock.querySelector('.item-volume')?.value) || 0;
                const unitPrice = parseFloat(itemBlock.querySelector('.item-unit-price')
                    ?.value) || 0;
                const itemTotal = volume * unitPrice;
                const itemPriceDisplay = itemBlock.querySelector('.item-sub-total-price');

                const displayTotal = itemTotal > 0 ? itemTotal : (parseInt(itemBlock.dataset.subHarga) || 0);

                if (itemPriceDisplay) {
                    itemPriceDisplay.textContent = new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 0
                    }).format(displayTotal);
                }

                subTotal += displayTotal;
            });

            totalCategories += subTotal;

            const blockTotalDisplay = block.querySelector('.sub-total-price');
            if (blockTotalDisplay) {
                blockTotalDisplay.textContent = new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(subTotal);
            }
        });
    }

    // Hitung total misc costs HANYA dari Add Modal
    let totalMiscCosts = 0;
    const miscContainer = document.getElementById('miscCostsContainer');

    if (miscContainer) {
        const miscItems = miscContainer.querySelectorAll('.misc-cost-item');

        miscItems.forEach(function(item) {
            const amount = parseInt(item.querySelector('.misc-item-amount').value) || 0;
            totalMiscCosts += amount;
        });
    }

    // Update tampilan total kategori
    const totalCategoriesElement = document.getElementById('totalCategoriesPrice');
    if (totalCategoriesElement) {
        totalCategoriesElement.textContent = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(totalCategories);
    }

    // Update tampilan total misc costs
    const totalMiscCostsElement = document.getElementById('totalMiscCostsPrice');
    if (totalMiscCostsElement) {
        totalMiscCostsElement.textContent = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(totalMiscCosts);
    }

    // Update grand total
    const grandTotal = totalCategories + totalMiscCosts;
    const grandTotalElement = document.getElementById('grandTotalPrice');
    if (grandTotalElement) {
        grandTotalElement.textContent = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(grandTotal);
    }
}

/**
 * Menghitung dan menampilkan grand total modal edit tertentu (kategori + biaya lain-lain).
 *
 * Alur:
 * 1. Hitung totalCategories dari `editRabCategoriesContainer{rabNumber}`
 *    dengan pola yang sama seperti versi modal tambah
 *    (per item: volume x harga, fallback `data-sub-harga`; tampilkan ke `.sub-total-price`).
 * 2. Hitung totalMiscCosts dari `editMiscCostsContainer{rabNumber}`.
 * 3. Update elemen `editTotalCategoriesPrice`, `editTotalMiscCostsPrice`,
 *    dan `editGrandTotalPrice` (dengan suffix rabNumber).
 *
 * @param {string|number} rabNumber - Nomor RAB (suffix ID elemen modal edit).
 */
function calculateAndUpdateGrandTotalForEditModal(rabNumber) {
    // Hitung total kategori HANYA dari Edit Modal tertentu
    let totalCategories = 0;
    const editModalContainer = document.getElementById('editRabCategoriesContainer' + rabNumber);

    if (editModalContainer) {
        const subcategoryBlocks = editModalContainer.querySelectorAll('.subcategory-block');

        subcategoryBlocks.forEach(block => {
            let subTotal = 0;

            block.querySelectorAll('.item-block').forEach(itemBlock => {
                const volume = parseFloat(itemBlock.querySelector('.item-volume')?.value) || 0;
                const unitPrice = parseFloat(itemBlock.querySelector('.item-unit-price')
                    ?.value) || 0;
                const itemTotal = volume * unitPrice;
                const itemPriceDisplay = itemBlock.querySelector('.item-sub-total-price');

                const displayTotal = itemTotal > 0 ? itemTotal : (parseInt(itemBlock.dataset.subHarga) || 0);

                if (itemPriceDisplay) {
                    itemPriceDisplay.textContent = new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 0
                    }).format(displayTotal);
                }

                subTotal += displayTotal;
            });

            totalCategories += subTotal;

            const blockTotalDisplay = block.querySelector('.sub-total-price');
            if (blockTotalDisplay) {
                blockTotalDisplay.textContent = new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(subTotal);
            }
        });
    }

    // Hitung total misc costs HANYA dari Edit Modal tertentu
    let totalMiscCosts = 0;
    const miscContainerId = 'editMiscCostsContainer' + rabNumber;
    const miscContainer = document.getElementById(miscContainerId);
    if (miscContainer) {
        const miscItems = miscContainer.querySelectorAll('.misc-cost-item');
        miscItems.forEach(function(item) {
            const amount = parseInt(item.querySelector('.misc-item-amount').value) || 0;
            totalMiscCosts += amount;
        });
    }

    // Update tampilan total kategori
    const totalCategoriesElement = document.getElementById('editTotalCategoriesPrice' + rabNumber);
    if (totalCategoriesElement) {
        totalCategoriesElement.textContent = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(totalCategories);
    }

    // Update tampilan total misc costs
    const totalMiscCostsElement = document.getElementById('editTotalMiscCostsPrice' + rabNumber);
    if (totalMiscCostsElement) {
        totalMiscCostsElement.textContent = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(totalMiscCosts);
    }

    // Update grand total
    const grandTotal = totalCategories + totalMiscCosts;
    const grandTotalElement = document.getElementById('editGrandTotalPrice' + rabNumber);
    if (grandTotalElement) {
        grandTotalElement.textContent = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(grandTotal);
    }
}

/* ==========================================
 * MISCELLANEOUS COSTS MANAGEMENT
 * ========================================== */

/**
 * Menambahkan baris biaya lain-lain (misc cost) baru.
 *
 * Alur:
 * 1. Default kontainer ke `miscCostsContainer` jika `containerId` kosong.
 * 2. Hitung jumlah item existing untuk menentukan urutan tampilan berikutnya.
 * 3. Bangun elemen `.misc-cost-item` berisi input nama & jumlah yang masing-masing
 *    memicu `updateMiscCostsData(containerId)` saat diinput, plus tombol hapus.
 * 4. Append ke kontainer lalu perbarui data misc costs (hidden input & grand total).
 *
 * @param {string} [containerId] - ID kontainer misc costs (add atau edit modal).
 */
function addMiscCostItem(containerId) {
    // Default ke add modal jika tidak ada container ID
    if (!containerId) {
        containerId = 'miscCostsContainer';
    }

    const container = document.getElementById(containerId);
    if (!container) return;

    const itemCount = container.querySelectorAll('.misc-cost-item').length + 1;
    const item = document.createElement('div');
    item.className = 'misc-cost-item bg-surface-base border border-border-strong rounded p-3 flex gap-2';
    item.innerHTML = `
        <div class="flex-1">
            <input type="text" class="w-full border border-border-strong rounded p-2 mb-2 misc-item-name bg-surface-base text-text-input" 
                placeholder="Nama biaya" value="" required maxlength="255"
                oninput="updateMiscCostsData('${containerId}')">
        </div>
        <div class="w-32">
            <input type="number" class="w-full border border-border-strong rounded p-2 mb-2 misc-item-amount bg-surface-base text-text-input" 
                placeholder="Jumlah" value="0" min="0" step="0.01" required
                oninput="updateMiscCostsData('${containerId}')">
        </div>
        <button type="button" class="btn btn-sm bg-btn-delete hover:bg-btn-delete-hover text-white h-full" onclick="removeMiscCostItem(this, '${containerId}')">
            <i class="fa-solid fa-trash"></i>
        </button>
    `;
    container.appendChild(item);
    updateMiscCostsData(containerId);
}

/**
 * Menghapus baris biaya lain-lain.
 *
 * Alur:
 * 1. Hapus elemen `.misc-cost-item` terdekat dari tombol.
 * 2. Panggil `updateMiscCostsData(containerId)` untuk menyesuaikan
 *    hidden input dan grand total.
 *
 * @param {HTMLElement} btn - Tombol hapus baris misc cost.
 * @param {string} containerId - ID kontainer misc costs.
 */
function removeMiscCostItem(btn, containerId) {
    btn.closest('.misc-cost-item').remove();
    updateMiscCostsData(containerId);
}

/**
 * Menyimpan data biaya lain-lain ke hidden input dan menghitung ulang grand total.
 *
 * Alur:
 * 1. Default kontainer ke `miscCostsContainer` jika `containerId` kosong.
 * 2. Tentukan ID hidden input berdasarkan kontainer:
 *    - `editMiscCostsContainer{rabNumber}` -> `editMiscCostsDataInput{rabNumber}`.
 *    - selain itu -> `miscCostsDataInput` (modal tambah).
 * 3. Susun array { item_order, item_name, amount } dari seluruh `.misc-cost-item`
 *    dan serialisasi ke JSON pada hidden input.
 * 4. Panggil `calculateAndUpdateGrandTotal[ForEditModal]` sesuai konteks
 *    agar total kategori + biaya lain-lain tampil realtime.
 *
 * @param {string} [containerId] - ID kontainer misc costs (add atau edit modal).
 */
function updateMiscCostsData(containerId) {
    // Default ke add modal jika tidak ada container ID
    if (!containerId) {
        containerId = 'miscCostsContainer';
    }

    const container = document.getElementById(containerId);
    const miscItems = container.querySelectorAll('.misc-cost-item');
    let miscTotal = 0;

    miscItems.forEach(function(item) {
        const amount = parseInt(item.querySelector('.misc-item-amount').value) || 0;
        miscTotal += amount;
    });

    // Tentukan hidden input ID berdasarkan container ID
    let hiddenInputId;
    if (containerId.startsWith('editMiscCostsContainer')) {
        // Extract RAB number dari container ID
        const rabNumber = containerId.replace('editMiscCostsContainer', '');
        hiddenInputId = 'editMiscCostsDataInput' + rabNumber;
    } else {
        hiddenInputId = 'miscCostsDataInput';
    }

    // Save misc costs data to hidden input
    const miscCostsData = [];
    miscItems.forEach(function(item, index) {
        miscCostsData.push({
            item_order: index + 1,
            item_name: item.querySelector('.misc-item-name').value,
            amount: parseInt(item.querySelector('.misc-item-amount').value) || 0
        });
    });
    const hiddenInput = document.getElementById(hiddenInputId);
    if (hiddenInput) {
        hiddenInput.value = JSON.stringify(miscCostsData);
    }

    // Update grand total keseluruhan
    if (containerId.startsWith('editMiscCostsContainer')) {
        const rabNumber = containerId.replace('editMiscCostsContainer', '');
        calculateAndUpdateGrandTotalForEditModal(rabNumber);
    } else {
        calculateAndUpdateGrandTotal();
    }
}

/* ==========================================
 * EXPOSE KE GLOBAL (inline onclick/oninput Blade)
 * ========================================== */

/**
 * Mengekspos seluruh fungsi ke global scope (window) agar dapat dipanggil
 * dari inline `onclick`/`oninput` pada template Blade.
 */
window.addCategoryBlock = addCategoryBlock;
window.addSubcategoryBlock = addSubcategoryBlock;
window.addItemBlock = addItemBlock;
window.removeCategoryBlock = removeCategoryBlock;
window.removeSubcategoryBlock = removeSubcategoryBlock;
window.removeItemBlock = removeItemBlock;
window.editRAB = editRAB;
window.attachPriceListeners = attachPriceListeners;
window.updatePrices = updatePrices;
window.updatePricesForEditModal = updatePricesForEditModal;
window.recalculateAllTotals = recalculateAllTotals;
window.addMiscCostItem = addMiscCostItem;
window.removeMiscCostItem = removeMiscCostItem;
window.updateMiscCostsData = updateMiscCostsData;
window.prepareRABSubmit = prepareRABSubmit;
window.prepareEditRABSubmit = prepareEditRABSubmit;
window.calculateAndUpdateGrandTotal = calculateAndUpdateGrandTotal;
window.calculateAndUpdateGrandTotalForEditModal = calculateAndUpdateGrandTotalForEditModal;
