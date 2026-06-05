<script>
    function parseCurrencyInput(value) {
        return parseInt(String(value || '').replace(/[^\d]/g, ''), 10) || 0;
    }

    function formatCurrencyInput(input) {
        if (!input) return;

        const numeric = String(input.value || '').replace(/[^\d]/g, '');
        input.value = numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '';
    }

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

    document.addEventListener('DOMContentLoaded', function() {
        const barangItemOptionsHtml = `
            <option value="">-- Pilih Barang --</option>
            @foreach ($items as $item)
                <option value="{{ $item->id_item }}" data-name="{{ e($item->name_item) }}"
                    data-capital="{{ $item->capital_price }}" data-selling="{{ $item->selling_price }}"
                    data-stock="{{ $item->quantity }}">
                    {{ e($item->name_item) }} (Stok: {{ $item->quantity }})
                </option>
            @endforeach
        `;

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function syncRowState(row) {
            const fromStock = row.querySelector('.barang-from-stock');
            const selectWrapper = row.querySelector('.barang-select-wrapper');
            const select = row.querySelector('.barang-item-select');
            const nameInput = row.querySelector('.barang-item-name');
            const capitalInput = row.querySelector('.barang-item-capital');
            const sellingInput = row.querySelector('.barang-item-selling');

            if (!fromStock || !selectWrapper || !select || !nameInput || !capitalInput || !sellingInput) {
                return;
            }

            if (fromStock.checked) {
                selectWrapper.style.display = 'block';
                nameInput.readOnly = true;
                capitalInput.readOnly = true;
                sellingInput.readOnly = true;
            } else {
                selectWrapper.style.display = 'none';
                select.value = '';
                nameInput.readOnly = false;
                capitalInput.readOnly = false;
                sellingInput.readOnly = false;
            }
        }

        function applySelectedItem(row) {
            const select = row.querySelector('.barang-item-select');
            const nameInput = row.querySelector('.barang-item-name');
            const capitalInput = row.querySelector('.barang-item-capital');
            const sellingInput = row.querySelector('.barang-item-selling');

            if (!select || !nameInput || !capitalInput || !sellingInput) {
                return;
            }

            const selectedOption = select.options[select.selectedIndex];

            if (selectedOption && selectedOption.value) {
                nameInput.value = selectedOption.dataset.name || '';
                capitalInput.value = selectedOption.dataset.capital || 0;
                sellingInput.value = selectedOption.dataset.selling || 0;
                formatCurrencyInput(capitalInput);
                formatCurrencyInput(sellingInput);
            }
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

                    <div class="relative mb-2 barang-select-wrapper" style="display: none;">
                        <select name="items[${index}][id_item]" class="barang-item-select w-full border rounded p-2">
                            ${barangItemOptionsHtml}
                        </select>
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
            applySelectedItem(row);

            const capitalInput = row.querySelector('.barang-item-capital');
            const sellingInput = row.querySelector('.barang-item-selling');

            if (capitalInput) {
                formatCurrencyInput(capitalInput);
            }

            if (sellingInput) {
                formatCurrencyInput(sellingInput);
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

            if (e.target.classList.contains('barang-item-select')) {
                const row = e.target.closest('.barang-item-row');
                if (row) {
                    applySelectedItem(row);
                }
            }
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

        const selectAllCheckbox = document.getElementById('selectAll');
        const invoiceCheckboxes = document.querySelectorAll('input[name="selected_invoices[]"]');
        const deleteButton = document.getElementById('delete-button');

        function updateDeleteButtonState() {
            const anyChecked = Array.from(invoiceCheckboxes).some(cb => cb.checked);
            if (deleteButton) {
                deleteButton.disabled = !anyChecked;
            }
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                invoiceCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateDeleteButtonState();
            });
        }

        invoiceCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (!this.checked && selectAllCheckbox) {
                    selectAllCheckbox.checked = false;
                }

                if (selectAllCheckbox) {
                    const allChecked = Array.from(invoiceCheckboxes).every(cb => cb.checked);
                    selectAllCheckbox.checked = allChecked;
                }

                updateDeleteButtonState();
            });
        });

        updateDeleteButtonState();
    });
</script>
