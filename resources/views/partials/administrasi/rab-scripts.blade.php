@push('scripts')
    <script>
        // Shared helper is loaded from resources/js/shared/form-submit.js

        // Reset loading state function (untuk error handling)
        function resetLoadingState(submitBtn) {
            if (window.resetFormSubmitState) {
                window.resetFormSubmitState();
            }

            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = submitBtn.dataset.originalText || 'Submit';
                submitBtn.classList.remove('opacity-70', 'cursor-not-allowed');
            }
        }

        // ==========================================
        // BULK DELETE FUNCTION
        // ==========================================

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
            } else {
                resetLoadingState(deleteBtn);
            }
        }

        // ==========================================
        // CENTRALIZED RECALCULATION
        // ==========================================

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

        // ==========================================
        // CATEGORY, SUBCATEGORY, ITEM MANAGEMENT
        // ==========================================

        // Add Category - dengan optional prefix dan categoryData untuk edit modal
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
                                                <span class="item-sub-total-price text-sm font-semibold text-info">Rp ${new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(item.sub_harga || 0).replace('Rp ', 'Rp ')}</span>
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
                                                                                                        <p class="text-sm text-text-heading"><strong>Total Sub-Kategori:</strong> <span class="sub-total-price font-bold text-lg text-info">Rp ${new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(subcategory.sub_harga || 0).replace('Rp ', 'Rp ')}</span></p>
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

        // Add Subcategory
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

        // Add Item
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

        // Remove Category
        function removeCategoryBlock(button) {
            const block = button.closest('.category-block');
            const rootContainer = block.closest('#rabCategoriesContainer, [id^="editRabCategoriesContainer"]');
            block.remove();
            if (rootContainer) recalculateAllTotals(rootContainer);
        }

        // Remove Subcategory
        function removeSubcategoryBlock(button) {
            const block = button.closest('.subcategory-block');
            const rootContainer = block.closest('#rabCategoriesContainer, [id^="editRabCategoriesContainer"]');
            block.remove();
            if (rootContainer) recalculateAllTotals(rootContainer);
        }

        // Remove Item
        function removeItemBlock(button) {
            const block = button.closest('.item-block');
            const rootContainer = block.closest('#rabCategoriesContainer, [id^="editRabCategoriesContainer"]');
            block.remove();
            if (rootContainer) recalculateAllTotals(rootContainer);
        }

        // ==========================================
        // PRICE CALCULATOR
        // ==========================================

        function attachPriceListeners() {
            // Gunakan event delegation pada document untuk menangkap semua input volume/harga
            // termasuk yang ditambahkan secara dinamis
            document.removeEventListener('input', handlePriceInput);
            document.addEventListener('input', handlePriceInput);
        }

        function handlePriceInput(e) {
            const target = e.target;
            if (target.classList.contains('item-volume') || target.classList.contains('item-unit-price')) {
                updatePricesForContext(target);
            }
        }

        /**
         * Ikat listener langsung pada elemen item block yang baru (untuk redundancy).
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

        function updatePricesForContext(element) {
            // Tentukan konteks: apakah ini add modal atau edit modal
            let container = element.closest('[id^="editRabCategoriesContainer"], #rabCategoriesContainer');

            if (!container) {
                // Fallback: try to find category block
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
                // Edit modal context
                const rabNumber = containerId.replace('editRabCategoriesContainer', '');
                updatePricesForEditModalContext(rabNumber);
            } else {
                // Add modal context
                updatePrices();
            }
        }

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

            // Update total kategori display
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

            // Update grand total if element exists
            const grandTotalElement = document.getElementById('grandTotalPrice');
            if (grandTotalElement) {
                grandTotalElement.textContent = new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(grandTotal);
            }

            // Update total kategori display
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

        // Initialize on document ready
        document.addEventListener('DOMContentLoaded', function() {
            // Select all checkbox logic
            const selectAllCheckbox = document.getElementById('selectAll');
            const itemCheckboxes = document.querySelectorAll('.item-checkbox');
            const deleteButton = document.querySelector('[data-delete-button]') || document.querySelector(
                'button[onclick*="openModal"][onclick*="deleteModal"]');

            // Function to update delete button state
            function updateDeleteButtonState() {
                const checkedCount = Array.from(itemCheckboxes).filter(cb => cb.checked).length;
                if (deleteButton) {
                    if (checkedCount > 0) {
                        deleteButton.disabled = false;
                        deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
                    } else {
                        deleteButton.disabled = true;
                        deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
                    }
                }
            }

            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    itemCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                    updateDeleteButtonState();
                });
            }

            // Update selectAll state when individual checkboxes change
            itemCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const allChecked = Array.from(itemCheckboxes).every(cb => cb.checked);
                    const someChecked = Array.from(itemCheckboxes).some(cb => cb.checked);

                    if (selectAllCheckbox) {
                        selectAllCheckbox.checked = allChecked;
                        selectAllCheckbox.indeterminate = someChecked && !allChecked;
                    }
                    updateDeleteButtonState();
                });
            });

            // Initialize price listeners
            attachPriceListeners();
            updatePrices();
            calculateAndUpdateGrandTotal();

            // Set initial delete button state
            updateDeleteButtonState();

            // Attach event listeners untuk semua form edit RAB
            // Form edit modal memiliki pattern: editRABForm{number}
            document.querySelectorAll('form[id^="editRABForm"]').forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('[type="submit"]');
                    if (submitBtn && !handleFormSubmit(submitBtn)) {
                        e.preventDefault();
                        return false;
                    }
                    const rabNumber = this.id.replace('editRABForm', '');
                    prepareEditRABSubmit(rabNumber);
                });
            });
        });

        // ==========================================
        // PREPARE DATA FOR SUBMISSION
        // ==========================================

        window.prepareRABSubmit = function() {
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
        };

        // ==========================================
        // FORM SUBMISSION HANDLER
        // ==========================================

        function editRAB(rabNumber) {
            openModal('editRABModal' + rabNumber);
        }

        const addRABForm = document.getElementById('addRABForm');
        if (addRABForm) {
            addRABForm.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('[type="submit"]');
                if (submitBtn && !handleFormSubmit(submitBtn)) {
                    e.preventDefault();
                    return false;
                }
                prepareRABSubmit();
            });
        }

        // ==========================================
        // PREPARE EDIT RAB DATA FOR SUBMISSION
        // ==========================================

        window.prepareEditRABSubmit = function(rabNumber) {
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
        };

        // ==========================================
        // HELPER: UPDATE GRAND TOTAL FOR ALL MODALS
        // ==========================================

        window.updatePricesForEditModal = function(grandTotalElementId) {
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

                // Update total kategori display
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
        };

        // ==========================================
        // CALCULATE AND UPDATE GRAND TOTAL
        // ==========================================

        window.calculateAndUpdateGrandTotal = function() {
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
        };

        window.calculateAndUpdateGrandTotalForEditModal = function(rabNumber) {
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
        };

        // ==========================================
        // MISCELLANEOUS COSTS MANAGEMENT
        // ==========================================

        window.addMiscCostItem = function(containerId) {
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
        };

        window.removeMiscCostItem = function(btn, containerId) {
            btn.closest('.misc-cost-item').remove();
            updateMiscCostsData(containerId);
        };

        window.updateMiscCostsData = function(containerId) {
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
        };
    </script>
@endpush
