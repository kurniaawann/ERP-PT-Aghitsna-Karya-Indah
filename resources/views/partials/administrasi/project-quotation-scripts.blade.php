<script>
    // ═══════════════════════════════════════════════════════════════════════════════
    // Project Quotation — Dynamic Flat Items JavaScript
    // Prefix conventions:
    //   'add'                   → addItemsContainer, addGrandTotal, addItemsJson
    //   'edit-{quotNumber}'     → editItemsContainer-{quotNumber}, etc.
    // ═══════════════════════════════════════════════════════════════════════════════

    // ─── Global state ──────────────────────────────────────────────────────────────
    let addItemsStore = [];
    let addNextItemId = 1;

    // Edit items are stored per quotation number
    let editItemsStore = window.editItemsStore || {};
    let editNextItemId = {};

    // ─── Show/Hide error in modal ──────────────────────────────────────────────────
    function showModalError(prefix, errorMessage) {
        const errorDiv = document.getElementById(prefix + 'ModalError');
        const errorText = document.getElementById(prefix + 'ModalErrorText');
        if (errorDiv && errorText) {
            errorText.textContent = errorMessage;
            errorDiv.classList.remove('hidden');
            errorDiv.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
        }
    }

    function hideModalError(prefix) {
        const errorDiv = document.getElementById(prefix + 'ModalError');
        if (errorDiv) {
            errorDiv.classList.add('hidden');
        }
    }

    // ─── Set Button Loading State ─────────────────────────────────────────────────
    function setButtonLoading(buttonId, loading = true) {
        const button = document.getElementById(buttonId);
        if (!button) return;

        if (loading) {
            button.disabled = true;
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Memproses...';
            button.classList.add('opacity-70', 'cursor-not-allowed');
        } else {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText || button.innerHTML;
            button.classList.remove('opacity-70', 'cursor-not-allowed');
        }
    }

    // Note: Modal data loading handled by MutationObserver in DOMContentLoaded section below

    // ─── Resolve container, grand-total display, and JSON input IDs ───────────────
    function resolveIds(prefix) {
        if (prefix === 'add') {
            return {
                container: 'addItemsContainer',
                grandTotal: 'addGrandTotal',
                jsonInput: 'addItemsJson',
            };
        }
        // prefix = 'edit-{quotationNumber}'
        const quotNum = prefix.replace(/^edit-/, '');
        return {
            container: `editItemsContainer-${quotNum}`,
            grandTotal: `editGrandTotal-${quotNum}`,
            jsonInput: `editItemsJson-${quotNum}`,
        };
    }

    // ─── Format number as Rp ────────────────────────────────────────────────────────
    function formatRp(value) {
        const n = parseAmount(value);
        return 'Rp ' + n.toLocaleString('id-ID');
    }

    // ─── Parse raw input (remove dots/commas) ─────────────────────────────────────
    function parseAmount(str) {
        if (!str) return 0;
        return parseInt(str.toString().replace(/\./g, '').replace(/,/g, '')) || 0;
    }

    // ─── Apply thousand-separator formatting to price inputs ──────────────────────
    function formatPriceInput(input) {
        let raw = input.value.replace(/\./g, '').replace(/[^0-9]/g, '');
        if (raw === '') {
            input.value = '';
            return;
        }
        input.value = parseInt(raw).toLocaleString('id-ID');
    }

    // ─── Recalculate single item's total_price ────────────────────────────────────
    function recalcItem(itemEl) {
        const volInput = itemEl.querySelector('.item-volume');
        const priceInput = itemEl.querySelector('.item-unit-price');
        const totalEl = itemEl.querySelector('.item-total-display');

        const vol = parseFloat((volInput.value || '1').replace(',', '.')) || 1;
        const price = parseAmount(priceInput.value);
        const total = Math.round(vol * price);

        if (totalEl) totalEl.textContent = formatRp(total);
    }

    // ─── Get items store for prefix ────────────────────────────────────────────────
    function getItemsStore(prefix) {
        if (prefix === 'add') {
            return addItemsStore;
        }
        const quotNum = prefix.replace(/^edit-/, '');
        return editItemsStore[quotNum] || [];
    }

    function setItemsStore(prefix, items) {
        if (prefix === 'add') {
            addItemsStore = items;
        } else {
            const quotNum = prefix.replace(/^edit-/, '');
            editItemsStore[quotNum] = items;
        }
    }

    // ─── ADD ITEM ──────────────────────────────────────────────────────────────────
    window.addItem = function(prefix) {
        const items = getItemsStore(prefix);

        let newId;
        if (prefix === 'add') {
            newId = addNextItemId++;
        } else {
            const quotNum = prefix.replace(/^edit-/, '');
            if (!editNextItemId[quotNum]) {
                editNextItemId[quotNum] = 1;
            }
            newId = editNextItemId[quotNum]++;
        }

        const newItem = {
            id: newId,
            order_number: items.length + 1,
            description: '',
            volume: '',
            unit: '',
            unit_price: '',
            total_price: 0
        };

        items.push(newItem);
        setItemsStore(prefix, items);
        renderItems(prefix);
    }

    // ─── REMOVE ITEM ───────────────────────────────────────────────────────────────
    window.removeItem = function(prefix, itemId) {
        let items = getItemsStore(prefix);
        items = items.filter(i => i.id !== itemId);

        // Reorder
        items.forEach((item, idx) => {
            item.order_number = idx + 1;
        });

        setItemsStore(prefix, items);
        renderItems(prefix);
    }

    // ─── UPDATE ITEM FIELD ─────────────────────────────────────────────────────────
    window.updateItemField = function(prefix, itemId, field, value, render = true) {
        const items = getItemsStore(prefix);
        const item = items.find(i => i.id === itemId);
        if (!item) return;

        item[field] = value;

        // Calculate total_price
        const vol = parseFloat((item.volume || '1').toString().replace(',', '.')) || 1;
        const price = parseAmount(item.unit_price);
        item.total_price = Math.round(vol * price);

        setItemsStore(prefix, items);

        if (render) {
            renderItems(prefix);
            return;
        }

        // Update only the relevant DOM nodes to avoid losing focus/caret
        const ids = resolveIds(prefix);
        const container = document.getElementById(ids.container);
        if (!container) return;
        const itemEl = container.querySelector(`[data-item-id="${itemId}"]`);
        if (itemEl) {
            const totalEl = itemEl.querySelector('.item-total-display');
            if (totalEl) totalEl.textContent = formatRp(item.total_price);
        }

        // Update grand total
        updateGrandTotal(prefix);
    }

    // ─── RENDER ALL ITEMS ──────────────────────────────────────────────────────────
    function renderItems(prefix) {
        const ids = resolveIds(prefix);
        const container = document.getElementById(ids.container);
        if (!container) return;

        const items = getItemsStore(prefix);

        container.innerHTML = '';

        if (items.length === 0) {
            container.innerHTML = `
                <div class="text-center text-gray-500 p-4 border-2 border-dashed rounded-lg">
                    Belum ada item. Klik tombol "Tambah Item" untuk menambahkan.
                </div>
            `;
            updateGrandTotal(prefix);
            return;
        }

        items.forEach((item, idx) => {
            const itemCard = document.createElement('div');
            itemCard.className = 'bg-gray-50 rounded-lg p-4';
            itemCard.setAttribute('data-item-id', item.id);
            itemCard.innerHTML = `
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold text-sm text-gray-700">
                        <i class="fa-solid fa-circle-dot text-primary mr-1"></i>
                        Item ${idx + 1}
                    </h4>
                    <button type="button" onclick="removeItem('${prefix}', ${item.id})"
                        class="text-error hover:bg-red-100 px-2 py-1 rounded-md transition-colors duration-200">
                        <i class="fa-solid fa-trash-can"></i> Hapus
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-3">
                        <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Keterangan</label>
                        <input type="text" value="${escHtml(item.description || '')}"
                            oninput="updateItemField('${prefix}', ${item.id}, 'description', this.value, false)"
                            onchange="updateItemField('${prefix}', ${item.id}, 'description', this.value)"
                            class="w-full border rounded-md p-2 text-sm"
                            placeholder="Deskripsi item">
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Volume</label>
                            <input type="text" value="${escHtml(item.volume || '')}"
                                oninput="updateItemField('${prefix}', ${item.id}, 'volume', this.value, false)"
                                onchange="updateItemField('${prefix}', ${item.id}, 'volume', this.value)"
                                class="w-full border rounded-md p-2 text-sm item-volume"
                                placeholder="1">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Satuan</label>
                            <input type="text" value="${escHtml(item.unit || '')}"
                                oninput="updateItemField('${prefix}', ${item.id}, 'unit', this.value, false)"
                                onchange="updateItemField('${prefix}', ${item.id}, 'unit', this.value)"
                                class="w-full border rounded-md p-2 text-sm"
                                placeholder="unit">
                        </div>
                            <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Harga Satuan</label>
                                <input type="text" value="${item.unit_price ? parseAmount(item.unit_price).toLocaleString('id-ID') : ''}"
                                    oninput="formatPriceInput(this); updateItemField('${prefix}', ${item.id}, 'unit_price', this.value, false)"
                                    onchange="updateItemField('${prefix}', ${item.id}, 'unit_price', this.value)"
                                    class="w-full border rounded-md p-2 text-sm item-unit-price"
                                    placeholder="0">
                        </div>
                    </div>

                    <div class="bg-blue-50 rounded-md p-2 flex justify-between items-center">
                        <span class="text-xs font-medium text-gray-600">Total Harga:</span>
                        <span class="font-bold text-primary item-total-display">${formatRp(item.total_price)}</span>
                    </div>
                </div>
            `;

            container.appendChild(itemCard);
        });

        updateGrandTotal(prefix);
    }

    // ─── UPDATE GRAND TOTAL ────────────────────────────────────────────────────────
    function updateGrandTotal(prefix) {
        const ids = resolveIds(prefix);
        const grandTotalEl = document.getElementById(ids.grandTotal);
        if (!grandTotalEl) return;

        const items = getItemsStore(prefix);
        const grandTotal = items.reduce((sum, item) => sum + item.total_price, 0);

        grandTotalEl.textContent = formatRp(grandTotal);
    }

    // ─── FETCH NEXT QUOTATION NUMBER ───────────────────────────────────────────────
    function fetchNextQuotationNumber() {
        fetch('{{ route('project-quotation.getNextNumber') }}')
            .then(response => response.json())
            .then(data => {
                const displayEl = document.getElementById('addQuotationNumberDisplay');
                if (displayEl) {
                    displayEl.textContent = data.quotation_number;
                }
            })
            .catch(error => {
                console.error('Error fetching next quotation number:', error);
            });
    }

    // ─── FETCH QUOTATION DATA (FOR EDIT) ───────────────────────────────────────────
    function fetchQuotationData(quotNum) {
        fetch(`/project-quotation/${encodeURIComponent(quotNum)}`)
            .then(response => {
                if (!response.ok) throw new Error('Failed to fetch quotation data');
                return response.json();
            })
            .then(data => {
                // Initialize items store
                if (!editItemsStore[quotNum]) {
                    editItemsStore[quotNum] = [];
                }

                // Load items from API
                editItemsStore[quotNum] = (data.items || []).map((item, idx) => ({
                    id: idx + 1,
                    order_number: item.order_number || (idx + 1),
                    description: item.description || '',
                    volume: item.volume || '',
                    unit: item.unit || '',
                    unit_price: item.unit_price || 0,
                    total_price: item.total_price || 0
                }));

                // Set next item ID
                if (editItemsStore[quotNum].length > 0) {
                    editNextItemId[quotNum] = Math.max(...editItemsStore[quotNum].map(i => i.id), 0) + 1;
                } else {
                    editNextItemId[quotNum] = 1;
                }

                // Render items
                renderItems('edit-' + quotNum);
            })
            .catch(error => {
                console.error('Error fetching quotation data:', error);
                showModalError('edit-' + quotNum, 'Gagal memuat data penawaran. Silakan coba lagi.');
            });
    }

    // ─── PREPARE SUBMIT (ADD) ──────────────────────────────────────────────────────
    function prepareAddSubmit() {
        hideModalError('add');

        if (addItemsStore.length === 0) {
            showModalError('add', 'Minimal harus ada 1 item');
            return false;
        }

        // Validate items
        for (let i = 0; i < addItemsStore.length; i++) {
            const item = addItemsStore[i];
            if (!item.description || item.description.trim() === '') {
                showModalError('add', `Item ${i + 1}: Keterangan harus diisi`);
                return false;
            }
            if (!item.unit_price || parseAmount(item.unit_price) <= 0) {
                showModalError('add', `Item ${i + 1}: Harga satuan harus lebih dari 0`);
                return false;
            }
        }

        // Set JSON
        const ids = resolveIds('add');
        const jsonInput = document.getElementById(ids.jsonInput);
        if (jsonInput) {
            jsonInput.value = JSON.stringify(addItemsStore);
        }

        // Set button to loading state
        setButtonLoading('submit-btn-addModal', true);
        return true;
    }

    // ─── PREPARE SUBMIT (EDIT) ─────────────────────────────────────────────────────
    function prepareEditSubmit(quotNum) {
        const errorPrefix = 'edit-' + quotNum;
        hideModalError(errorPrefix);

        const items = editItemsStore[quotNum] || [];

        if (items.length === 0) {
            showModalError(errorPrefix, 'Minimal harus ada 1 item');
            return false;
        }

        // Validate items
        for (let i = 0; i < items.length; i++) {
            const item = items[i];
            if (!item.description || item.description.trim() === '') {
                showModalError(errorPrefix, `Item ${i + 1}: Keterangan harus diisi`);
                return false;
            }
            if (!item.unit_price || parseAmount(item.unit_price) <= 0) {
                showModalError(errorPrefix, `Item ${i + 1}: Harga satuan harus lebih dari 0`);
                return false;
            }
        }

        // Set JSON
        const ids = resolveIds('edit-' + quotNum);
        const jsonInput = document.getElementById(ids.jsonInput);
        if (jsonInput) {
            jsonInput.value = JSON.stringify(items);
        }

        // Set button to loading state
        setButtonLoading('submit-btn-editModal-' + quotNum, true);
        return true;
    }

    // ─── SUBMIT DELETE FORM ────────────────────────────────────────────────────────
    function submitDeleteForm() {
        const checkboxes = document.querySelectorAll('#deleteForm input[name="ids[]"]:checked');
        if (checkboxes.length === 0) {
            alert('Pilih minimal 1 penawaran untuk dihapus');
            closeModal('deleteModal');
            return false;
        }

        // Set delete button to loading state
        setButtonLoading('confirm-btn-deleteModal', true);

        // Submit form
        setTimeout(() => {
            document.getElementById('deleteForm').submit();
        }, 100);
    }

    // ─── UPDATE DELETE BUTTON STATE ────────────────────────────────────────────────
    function updateDeleteButtonState() {
        const deleteButton = document.getElementById('delete-button');
        const checkedCheckboxes = document.querySelectorAll('#deleteForm input[name="ids[]"]:checked');

        if (deleteButton) {
            if (checkedCheckboxes.length > 0) {
                deleteButton.disabled = false;
                deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
                deleteButton.classList.add('hover:bg-btn-delete-hover');
            } else {
                deleteButton.disabled = true;
                deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
                deleteButton.classList.remove('hover:bg-btn-delete-hover');
            }
        }
    }

    // ─── AUTO-POPULATE MODALS & SELECT ALL ────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function() {
        // ═══ Auto-fetch next quotation number for Add modal ═══
        const addModal = document.getElementById('addModal');
        if (addModal) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(m) {
                    if (m.target.id === 'addModal' && !m.target.classList.contains('hidden')) {
                        hideModalError('add');
                        fetchNextQuotationNumber();
                        addItemsStore = [];
                        addNextItemId = 1;
                        renderItems('add');
                    }
                });
            });
            observer.observe(addModal, {
                attributes: true,
                attributeFilter: ['class']
            });
        }

        // ═══ Auto-fetch quotation data for Edit modals ═══
        document.querySelectorAll('[id^="editModal-"]').forEach(function(editModal) {
            const quotNum = editModal.id.replace('editModal-', '');
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(m) {
                    if (!m.target.classList.contains('hidden')) {
                        hideModalError('edit-' + quotNum);
                        fetchQuotationData(quotNum);
                    }
                });
            });
            observer.observe(editModal, {
                attributes: true,
                attributeFilter: ['class']
            });
        });

        // ═══ Select All checkbox ═══
        const selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('#deleteForm input[name="ids[]"]');
                checkboxes.forEach(cb => {
                    cb.checked = this.checked;
                });
                updateDeleteButtonState();
            });
        }

        // Add event listeners to individual checkboxes
        const checkboxes = document.querySelectorAll('#deleteForm input[name="ids[]"]');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allCheckboxes = document.querySelectorAll(
                    '#deleteForm input[name="ids[]"]');
                const checkedCheckboxes = document.querySelectorAll(
                    '#deleteForm input[name="ids[]"]:checked');

                // Update select all checkbox state
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = allCheckboxes.length === checkedCheckboxes
                        .length;
                }

                updateDeleteButtonState();
            });
        });

        // Initialize delete button state on page load
        updateDeleteButtonState();
    });

    // ─── HTML ESCAPE HELPER ────────────────────────────────────────────────────────
    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
</script>
