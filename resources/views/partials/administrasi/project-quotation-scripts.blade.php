<script>
    // ═══════════════════════════════════════════════════════════════════════════════
    // Project Quotation — Dynamic Groups & Items JavaScript
    // Prefix conventions:
    //   'add'                   → addGroupsContainer, addGrandTotal, addGroupsJson
    //   'edit-{quotNumber}'     → editGroupsContainer-{quotNumber}, etc.
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Resolve container, grand-total display, and JSON hidden-input IDs from a prefix.
     */
    function resolveIds(prefix) {
        if (prefix === 'add') {
            return {
                container: 'addGroupsContainer',
                grandTotal: 'addGrandTotal',
                jsonInput: 'addGroupsJson',
            };
        }
        // prefix = 'edit-{quotationNumber}'
        const quotNum = prefix.replace(/^edit-/, '');
        return {
            container: `editGroupsContainer-${quotNum}`,
            grandTotal: `editGrandTotal-${quotNum}`,
            jsonInput: `editGroupsJson-${quotNum}`,
        };
    }

    // ─── Format number as Rp ──────────────────────────────────────────────────────
    function formatRp(value) {
        if (!value || isNaN(value)) return 'Rp 0';
        return 'Rp ' + parseInt(value).toLocaleString('id-ID');
    }

    // ─── Parse raw input (remove dots/commas used in thousand separators) ─────────
    function parseAmount(str) {
        if (!str) return 0;
        return parseInt(str.toString().replace(/\./g, '').replace(/,/g, '')) || 0;
    }

    // ─── Apply thousand-separator formatting to price inputs ─────────────────────
    function formatPriceInput(input) {
        let raw = input.value.replace(/\./g, '').replace(/[^0-9]/g, '');
        if (raw === '') {
            input.value = '';
            return;
        }
        input.value = parseInt(raw).toLocaleString('id-ID');
    }

    // ─── Recalculate single item's total_price ───────────────────────────────────
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

    // ─── Recalculate group subtotal ───────────────────────────────────────────────
    function recalcGroup(groupEl) {
        let subtotal = 0;
        groupEl.querySelectorAll('.item-row').forEach(item => {
            subtotal += recalcItem(item);
        });
        const subtotalEl = groupEl.querySelector('.group-subtotal');
        if (subtotalEl) subtotalEl.textContent = formatRp(subtotal);
        return subtotal;
    }

    // ─── Recalculate grand total ─────────────────────────────────────────────────
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

    // ─── Add one item row inside a group ─────────────────────────────────────────
    function addItem(itemsContainer, prefix, prefillData) {
        prefillData = prefillData || {};
        const itemEl = document.createElement('div');
        itemEl.className = 'item-row bg-white border border-gray-200 rounded-lg p-3 space-y-2';
        itemEl.innerHTML = `
        <div class="grid grid-cols-12 gap-2">
            {{-- Description --}}
            <div class="col-span-12 md:col-span-5">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Keterangan <span class="text-red-500">*</span></label>
                <input type="text" class="item-description w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                    placeholder="Contoh: 5 x 130 x 300" required
                    value="${escHtml(prefillData.description || '')}">
            </div>
            {{-- Volume --}}
            <div class="col-span-4 md:col-span-2">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Volume</label>
                <input type="text" class="item-volume w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-center"
                    placeholder="-"
                    value="${escHtml(prefillData.volume || '')}">
            </div>
            {{-- Unit --}}
            <div class="col-span-4 md:col-span-1">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Satuan</label>
                <input type="text" class="item-unit w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-center"
                    placeholder="-"
                    value="${escHtml(prefillData.unit || '')}">
            </div>
            {{-- Unit Price --}}
            <div class="col-span-6 md:col-span-2">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Harga <span class="text-red-500">*</span></label>
                <input type="text" class="item-unit-price w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-right"
                    placeholder="0" required
                    value="${prefillData.unit_price ? parseInt(prefillData.unit_price).toLocaleString('id-ID') : ''}">
            </div>
            {{-- Total --}}
            <div class="col-span-6 md:col-span-1">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Jumlah</label>
                <div class="item-total-display text-sm font-semibold text-green-700 border border-gray-200 rounded-lg px-2 py-2 bg-gray-50 text-right whitespace-nowrap">
                    ${formatRp(prefillData.total_price || 0)}
                </div>
            </div>
            {{-- Delete --}}
            <div class="col-span-12 md:col-span-1 flex items-end">
                <button type="button" onclick="removeItem(this, '${prefix}')"
                    class="w-full bg-red-500 hover:bg-red-600 text-white px-2 py-2 rounded-lg text-xs flex items-center justify-center gap-1 transition-colors">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </div>`;

        // Listeners
        const volInput = itemEl.querySelector('.item-volume');
        const priceInput = itemEl.querySelector('.item-unit-price');
        priceInput.addEventListener('input', () => {
            formatPriceInput(priceInput);
            updateGrandTotal(prefix);
        });
        volInput.addEventListener('input', () => updateGrandTotal(prefix));

        itemsContainer.appendChild(itemEl);

        // Recalc after adding (for pre-fill)
        if (prefillData.unit_price) updateGrandTotal(prefix);
    }

    // ─── Add one group card ───────────────────────────────────────────────────────
    function addGroup(prefix, prefillData) {
        prefillData = prefillData || {};
        const ids = resolveIds(prefix);
        const container = document.getElementById(ids.container);
        if (!container) return;

        const groupCount = container.querySelectorAll('.group-card').length + 1;

        const groupEl = document.createElement('div');
        groupEl.className = 'group-card border-2 border-gray-300 rounded-xl bg-gray-50 overflow-hidden shadow-sm';
        groupEl.innerHTML = `
        <div class="flex items-center justify-between bg-gray-200 px-4 py-2">
            <span class="font-bold text-sm text-gray-700">Kelompok ${groupCount}</span>
            <button type="button" onclick="removeGroup(this, '${prefix}')"
                class="text-red-500 hover:text-red-700 text-sm flex items-center gap-1 transition-colors">
                <i class="fa-solid fa-circle-minus"></i> Hapus Kelompok
            </button>
        </div>
        <div class="p-4 space-y-3">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Kelompok <span class="text-red-500">*</span></label>
                <input type="text" class="group-name w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                    placeholder="Contoh: P.1 Kayu Kamper Samarinda Oven" required
                    value="${escHtml(prefillData.name || '')}">
            </div>
            <div class="items-list space-y-2"></div>
            <button type="button" onclick="addItemToGroup(this, '${prefix}')"
                class="flex items-center gap-2 bg-white border-2 border-dashed border-gray-300 hover:border-primary hover:text-primary text-gray-500 px-4 py-2 rounded-lg text-sm w-full justify-center transition-all duration-200">
                <i class="fa-solid fa-plus"></i> Tambah Item
            </button>
            <div class="flex justify-end">
                <div class="text-sm font-semibold text-right">
                    Jumlah: <span class="group-subtotal text-green-700">Rp 0</span>
                </div>
            </div>
        </div>`;

        container.appendChild(groupEl);

        // Pre-fill items
        if (prefillData.items && prefillData.items.length > 0) {
            const itemsList = groupEl.querySelector('.items-list');
            prefillData.items.forEach(itemData => addItem(itemsList, prefix, itemData));
        }

        updateGrandTotal(prefix);
    }

    // ─── Add item to group via "+ Tambah Item" button ────────────────────────────
    function addItemToGroup(btn, prefix) {
        const groupEl = btn.closest('.group-card');
        const itemsList = groupEl.querySelector('.items-list');
        addItem(itemsList, prefix, {});
    }

    // ─── Remove item ──────────────────────────────────────────────────────────────
    function removeItem(btn, prefix) {
        btn.closest('.item-row').remove();
        updateGrandTotal(prefix);
    }

    // ─── Remove group ─────────────────────────────────────────────────────────────
    function removeGroup(btn, prefix) {
        btn.closest('.group-card').remove();
        // Renumber group labels
        const ids = resolveIds(prefix);
        document.querySelectorAll(`#${ids.container} .group-card`).forEach((card, idx) => {
            const lbl = card.querySelector('.flex.items-center.justify-between span');
            if (lbl) lbl.textContent = `Kelompok ${idx + 1}`;
        });
        updateGrandTotal(prefix);
    }

    // ─── Serialize all groups to JSON ────────────────────────────────────────────
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

    // ─── Prepare Add form submission ─────────────────────────────────────────────
    function prepareAddSubmit() {
        const groups = serializeGroups('add');
        if (groups.length === 0) {
            alert('Minimal 1 kelompok harus ditambahkan.');
            return false;
        }
        for (const g of groups) {
            if (!g.name) {
                alert('Nama kelompok tidak boleh kosong.');
                return false;
            }
            if (g.items.length === 0) {
                alert(`Kelompok "${g.name}" harus memiliki minimal 1 item.`);
                return false;
            }
        }
        document.getElementById('addGroupsJson').value = JSON.stringify(groups);

        // Show loading spinner
        const submitBtn = document.querySelector('#addQuotationForm button[type="submit"]');
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';
            submitBtn.disabled = true;
        }

        return true;
    }

    // ─── Prepare Edit form submission ────────────────────────────────────────────
    function prepareEditSubmit(quotNum) {
        const prefix = 'edit-' + quotNum;
        const groups = serializeGroups(prefix);
        if (groups.length === 0) {
            alert('Minimal 1 kelompok harus ditambahkan.');
            return false;
        }
        for (const g of groups) {
            if (!g.name) {
                alert('Nama kelompok tidak boleh kosong.');
                return false;
            }
            if (g.items.length === 0) {
                alert(`Kelompok "${g.name}" harus memiliki minimal 1 item.`);
                return false;
            }
        }
        document.getElementById('editGroupsJson-' + quotNum).value = JSON.stringify(groups);

        // Show loading spinner
        const submitBtn = document.querySelector('#editQuotationForm-' + quotNum + ' button[type="submit"]');
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';
            submitBtn.disabled = true;
        }

        return true;
    }

    // ─── Submit delete ────────────────────────────────────────────────────────────
    function submitDeleteForm() {
        document.getElementById('deleteForm').submit();
    }

    // ─── Export PDF (selected) ───────────────────────────────────────────────────
    function submitExportPdf() {
        const checkboxes = document.querySelectorAll('input[name="ids[]"]:checked');
        if (checkboxes.length === 0) {
            alert('Pilih minimal 1 penawaran untuk diekspor.');
            return;
        }
        const container = document.getElementById('exportPdfIdsContainer');
        container.innerHTML = '';
        checkboxes.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = cb.value;
            container.appendChild(input);
        });
        document.getElementById('exportPdfForm').submit();
    }

    // ─── Auto-populate Add modal number on open ──────────────────────────────────
    document.addEventListener('DOMContentLoaded', function() {
        const addBtn = document.querySelector('[data-modal-target="addModal"], [onclick*="addModal"]');

        // Fetch number when addModal opens
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(m) {
                if (m.target.id === 'addModal' && !m.target.classList.contains('hidden')) {
                    fetch('{{ route('project-quotation.getNextNumber') }}')
                        .then(r => r.json())
                        .then(data => {
                            const el = document.getElementById('addQuotationNumberDisplay');
                            if (el) el.textContent = data.quotation_number;
                        });
                }
            });
        });

        const addModal = document.getElementById('addModal');
        if (addModal) {
            observer.observe(addModal, {
                attributes: true,
                attributeFilter: ['class']
            });
        }

        // Select-all checkbox
        const selectAll = document.getElementById('selectAll');
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                document.querySelectorAll('input[name="ids[]"]').forEach(cb => {
                    cb.checked = selectAll.checked;
                });
            });
        }
    });

    // ─── HTML escape helper ───────────────────────────────────────────────────────
    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
</script>
