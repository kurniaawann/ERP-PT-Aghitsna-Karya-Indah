<script>
    @include('partials.shared.currency-utils-script')
    @include('partials.shared.delete-form-script')
    @include('partials.shared.select-all-script')

    document.addEventListener('DOMContentLoaded', function() {
        // Handle Form Submission for Add Modal
        const addForm = document.querySelector('#addModal form');
        if (addForm) {
            addForm.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (!handleFormSubmit(submitBtn)) {
                    e.preventDefault();
                    return false;
                }
            });
        }

        // Handle Form Submission for Edit Modals
        document.querySelectorAll('[id^="editModal-"] form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (!handleFormSubmit(submitBtn)) {
                    e.preventDefault();
                    return false;
                }
            });
        });

        function syncRowState(row) {
            const fromStock = row.querySelector('.barang-from-stock');
            const selectWrapper = row.querySelector('.barang-select-wrapper');
            const idInput = row.querySelector('.barang-item-id');
            const label = row.querySelector('.barang-item-dropdown-label');
            const nameInput = row.querySelector('.barang-item-name');
            const capitalInput = row.querySelector('.barang-item-capital');
            const sellingInput = row.querySelector('.barang-item-selling');

            if (!fromStock || !selectWrapper || !nameInput || !capitalInput || !sellingInput) {
                return;
            }

            if (fromStock.checked) {
                selectWrapper.style.display = 'block';
                nameInput.readOnly = true;
                capitalInput.readOnly = true;
                sellingInput.readOnly = true;
            } else {
                selectWrapper.style.display = 'none';
                idInput.value = '';
                label.textContent = '-- Pilih Barang --';
                nameInput.readOnly = false;
                capitalInput.readOnly = false;
                sellingInput.readOnly = false;
            }
        }

        function initSearchableDropdown(row) {
            const btn = row.querySelector('.barang-item-dropdown-btn');
            const menu = row.querySelector('.barang-item-dropdown-menu');
            const list = row.querySelector('.barang-item-dropdown-list');
            const searchInput = row.querySelector('.barang-item-search');
            const label = row.querySelector('.barang-item-dropdown-label');
            const idInput = row.querySelector('.barang-item-id');
            
            const nameInput = row.querySelector('.barang-item-name');
            const capitalInput = row.querySelector('.barang-item-capital');
            const sellingInput = row.querySelector('.barang-item-selling');

            if (!btn || !menu || !list) return;

            let page = 1;
            const limit = 10;
            let loading = false;
            let hasMore = true;
            let searchTimeout = null;

            function fetchItems(append = false) {
                if (loading || (!hasMore && append)) return;
                loading = true;

                const query = searchInput.value;
                const params = new URLSearchParams({
                    search: query,
                    page: String(page),
                    limit: String(limit),
                });

                if (!append) {
                    list.innerHTML = '<div class="p-2 text-sm text-text-secondary text-center">Memuat...</div>';
                }

                fetch('{{ route('item-invoice.items-dropdown') }}?' + params.toString())
                    .then(r => r.json())
                    .then(res => {
                        if (!append) list.innerHTML = '';
                        
                        const data = res.data || [];
                        if (data.length === 0 && !append) {
                            list.innerHTML = '<div class="p-2 text-sm text-text-secondary text-center py-4">Tidak ada data barang</div>';
                        } else {
                            data.forEach(item => {
                                const itemBtn = document.createElement('button');
                                itemBtn.type = 'button';
                                itemBtn.className = 'w-full text-left px-3 py-2 hover:bg-surface-secondary text-sm text-text-primary transition-colors border-b border-border-light last:border-0';
                                itemBtn.innerHTML = `
                                    <div class="font-semibold">${item.id_item} - ${item.name_item}</div>
                                    <div class="text-xs text-text-secondary">Stok: ${item.quantity}</div>
                                `;

                                itemBtn.addEventListener('click', () => {
                                    idInput.value = item.id_item;
                                    label.textContent = `${item.id_item} - ${item.name_item}`;
                                    
                                    nameInput.value = item.name_item;
                                    capitalInput.value = item.capital_price;
                                    sellingInput.value = item.selling_price;
                                    formatCurrencyInput(capitalInput);
                                    formatCurrencyInput(sellingInput);
                                    
                                    menu.classList.add('hidden');
                                });

                                list.appendChild(itemBtn);
                            });
                        }

                        hasMore = res.hasMore;
                        if (hasMore) page++;
                    })
                    .finally(() => {
                        loading = false;
                    });
            }

            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                // Close other menus
                document.querySelectorAll('.barang-item-dropdown-menu').forEach(m => {
                    if (m !== menu) m.classList.add('hidden');
                });
                
                menu.classList.toggle('hidden');
                if (!menu.classList.contains('hidden')) {
                    searchInput.focus();
                    if (page === 1) fetchItems();
                }
            });

            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    page = 1;
                    hasMore = true;
                    fetchItems();
                }, 300);
            });

            list.addEventListener('scroll', () => {
                if (list.scrollTop + list.clientHeight >= list.scrollHeight - 20) {
                    fetchItems(true);
                }
            });
        }

        function buildBarangRow(index) {
            return `
                <div class="barang-item-row mb-3 p-3 border rounded bg-surface-secondary">
                    <div class="flex items-center gap-2 mb-2">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="barang-from-stock accent-primary" name="items[${index}][from_stock]" value="1">
                            <span class="text-sm">Dari Stok</span>
                        </label>
                    </div>

                    {{-- Hidden input used for id_item --}}
                    <input type="hidden" name="items[${index}][id_item]" class="barang-item-id">

                    {{-- Custom searchable dropdown wrapper --}}
                    <div class="relative mb-2 barang-select-wrapper" style="display: none;">
                        <button type="button"
                            class="barang-item-dropdown-btn w-full px-3 py-2 border border-border-strong rounded bg-surface-base flex items-center justify-between focus:outline-none focus:ring-1 focus:ring-primary transition-colors">
                            <span class="barang-item-dropdown-label text-sm text-text-primary">-- Pilih Barang --</span>
                            <span class="text-text-secondary text-xs">▼</span>
                        </button>

                        <div class="barang-item-dropdown-menu absolute z-50 mt-1 w-full bg-surface-base border border-border-light rounded shadow-lg hidden">
                            <div class="p-2 border-b border-border-light">
                                <input type="text" class="barang-item-search w-full px-3 py-2 text-sm border border-border-strong rounded bg-surface-base text-text-input focus:outline-none focus:ring-1 focus:ring-primary"
                                    placeholder="Cari nama/kode barang...">
                            </div>
                            <div class="barang-item-dropdown-list max-h-48 overflow-y-auto">
                                <div class="p-2 text-sm text-text-secondary text-center dropdown-loading-placeholder">
                                    Memuat data...
                                </div>
                            </div>
                        </div>
                    </div>

                    <input type="text" name="items[${index}][name_item]" class="barang-item-name w-full border rounded p-2 mb-2" placeholder="Nama Barang *" required>

                    <div class="grid grid-cols-3 gap-2">
                        <input type="number" name="items[${index}][quantity]" class="barang-item-qty border rounded p-2" placeholder="Qty *" min="1" value="1" required>
                        <input type="text" inputmode="numeric" name="items[${index}][capital_price]" class="barang-item-capital border rounded p-2" placeholder="Harga Modal *" required>
                        <input type="text" inputmode="numeric" name="items[${index}][selling_price]" class="barang-item-selling border rounded p-2" placeholder="Harga Jual *" required>
                    </div>

                    <button type="button" class="remove-barang-item mt-2 bg-btn-delete text-white px-3 py-1 rounded hover:bg-btn-delete-hover w-full">
                        <i class="fa-solid fa-trash"></i> Hapus Item
                    </button>
                </div>
            `;
        }

        function initBarangRow(row) {
            syncRowState(row);
            initSearchableDropdown(row);

            const capitalInput = row.querySelector('.barang-item-capital');
            const sellingInput = row.querySelector('.barang-item-selling');

            if (capitalInput) {
                formatCurrencyInput(capitalInput);
                capitalInput.addEventListener('input', () => formatCurrencyInput(capitalInput));
            }

            if (sellingInput) {
                formatCurrencyInput(sellingInput);
                sellingInput.addEventListener('input', () => formatCurrencyInput(sellingInput));
            }
        }

        document.querySelectorAll('.barang-item-row').forEach(initBarangRow);

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('barang-from-stock')) {
                const row = e.target.closest('.barang-item-row');
                if (row) {
                    syncRowState(row);
                }
            }
        });

        // Close all dropdowns when clicking outside
        document.addEventListener('click', () => {
            document.querySelectorAll('.barang-item-dropdown-menu').forEach(m => m.classList.add('hidden'));
        });

        document.addEventListener('click', function(e) {
            const addButton = e.target.closest('.add-barang-item');
            if (addButton) {
                e.preventDefault();
                const targetId = addButton.dataset.target;
                const target = document.getElementById(targetId);

                if (!target) {
                    return;
                }

                const currentIndex = target.querySelectorAll('.barang-item-row').length;

                const html = buildBarangRow(currentIndex);
                target.insertAdjacentHTML('beforeend', html);

                const newRow = target.querySelectorAll('.barang-item-row')[currentIndex];
                if (newRow) {
                    initBarangRow(newRow);
                }
            }

            if (e.target.closest('.remove-barang-item')) {
                e.preventDefault();
                const row = e.target.closest('.barang-item-row');
                const container = row ? row.parentElement : null;

                if (!row || !container) {
                    return;
                }

                if (container.querySelectorAll('.barang-item-row').length <= 1) {
                    alert('Minimal harus ada 1 item!');
                    return;
                }

                row.remove();
            }
        });

        initSelectAll('selected_invoices[]');
    });
</script>
