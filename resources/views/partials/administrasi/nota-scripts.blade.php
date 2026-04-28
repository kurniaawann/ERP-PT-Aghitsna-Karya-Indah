<script>
    // ==========================================
    // PREVENT DOUBLE SUBMIT & LOADING STATE
    // ==========================================

    let isSubmitting = false;

    function handleFormSubmit(submitBtn, originalText) {
        if (isSubmitting) return false;

        isSubmitting = true;

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';
            submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
        }

        return true;
    }

    // ==========================================
    // SELECT ALL CHECKBOX
    // ==========================================

    // Select All Checkbox
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="ids[]"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateButtonStates();
    });

    // Individual Checkbox
    document.querySelectorAll('input[name="ids[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('input[name="ids[]"]');
            const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

            selectAll.checked = checkboxes.length === checkedCheckboxes.length;
            updateButtonStates();
        });
    });

    // Update Delete Button and Print Button State
    function updateButtonStates() {
        const deleteButton = document.getElementById('delete-button');
        const printButton = document.getElementById('printDropdownButton');
        const selectedCountText = document.getElementById('selectedCountText');
        const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');
        const count = checkedCheckboxes.length;

        // Update selected count text
        if (selectedCountText) {
            selectedCountText.textContent = count;
        }

        if (deleteButton) {
            if (count > 0) {
                deleteButton.disabled = false;
                deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                deleteButton.disabled = true;
                deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        if (printButton) {
            if (count > 0) {
                printButton.disabled = false;
                printButton.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                printButton.disabled = true;
                printButton.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }
    }

    // Initialize button states
    updateButtonStates();

    // ==========================================
    // SUBMIT DELETE FORM
    // ==========================================

    function submitDeleteForm() {
        const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');
        if (checkedCheckboxes.length === 0) {
            alert('Tidak ada data yang dipilih!');
            return;
        }

        // Update confirm button in modal
        const confirmBtn = document.getElementById('confirm-btn-deleteModal');
        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
            confirmBtn.classList.add('opacity-70', 'cursor-not-allowed');
        }

        const form = document.getElementById('deleteForm');
        form.submit();
    }

    // ==========================================
    // PRINT SELECTED FUNCTION
    // ==========================================

    function printSelected() {
        const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

        if (checkedCheckboxes.length === 0) {
            alert('Tidak ada data yang dipilih!');
            return;
        }

        // Create a temporary form
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('nota.administrasi.export.pdf.selected') }}';
        form.style.display = 'none';

        // Add CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = '{{ csrf_token() }}';
        form.appendChild(csrfInput);

        // Add all checked IDs
        checkedCheckboxes.forEach(checkbox => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = checkbox.value;
            form.appendChild(input);
        });

        // Submit form
        document.body.appendChild(form);
        form.submit();

        // Close dropdown after submit
        const dropdownMenu = document.getElementById('printDropdownMenu');
        if (dropdownMenu) {
            dropdownMenu.classList.add('hidden');
        }
    }

    // ==========================================
    // DYNAMIC ITEM ROWS
    // ==========================================

    function updateDeleteButtons(modalId) {
        const container = document.getElementById('itemsContainer-' + modalId);
        const items = container.querySelectorAll('.item-row');
        const deleteButtons = container.querySelectorAll('.delete-btn');

        // Show delete buttons only if there are more than 1 items
        deleteButtons.forEach(btn => {
            if (items.length > 1) {
                btn.style.display = 'flex';
            } else {
                btn.style.display = 'none';
            }
        });
    }

    function addItemRow(modalId) {
        const container = document.getElementById('itemsContainer-' + modalId);
        const newRow = document.createElement('div');
        newRow.className =
            'item-row bg-white border-2 border-gray-300 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow';
        newRow.innerHTML = `
            <div class="space-y-3">
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Qty <span class="text-red-500">*</span></label>
                        <input type="number" name="item_banyaknya[]" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-center focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="0" min="1" required>
                    </div>
                    <div class="col-span-10">
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Nama Barang <span class="text-red-500">*</span></label>
                        <input type="text" name="item_nama_barang[]" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="Masukkan nama barang..." required>
                    </div>
                </div>

                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-9">
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Harga Satuan <span class="text-red-500">*</span></label>
                        <input type="text" name="item_harga_satuan[]" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-right price-input focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="0" required>
                    </div>
                    <div class="col-span-3 flex items-end">
                        <button type="button" onclick="removeItemRow(this)" class="delete-btn w-full bg-red-500 hover:bg-red-600 text-white px-3 py-2.5 rounded-lg text-sm font-medium shadow-sm transition-all duration-200 flex items-center justify-center gap-2">
                            <i class="fa-solid fa-trash"></i>
                            <span>Hapus</span>
                        </button>
                    </div>
                </div>
            </div>
        `;
        container.appendChild(newRow);

        // Initialize price formatting for the new input
        const newPriceInput = newRow.querySelector('.price-input');
        if (newPriceInput) {
            initPriceInput(newPriceInput);
        }

        // Update delete button visibility
        updateDeleteButtons(modalId);
    }

    function removeItemRow(button) {
        const itemRow = button.closest('.item-row');
        const container = itemRow.parentElement;
        const modalId = container.id.replace('itemsContainer-', '');

        // Remove the item
        itemRow.remove();

        // Update delete button visibility
        updateDeleteButtons(modalId);
    }

    // Initialize delete buttons on page load
    document.addEventListener('DOMContentLoaded', function() {
        // For add modal
        updateDeleteButtons('addModal');

        // For edit modals
        document.querySelectorAll('[id^="itemsContainer-editModal-"]').forEach(container => {
            const modalId = container.id.replace('itemsContainer-', '');
            updateDeleteButtons(modalId);
        });
    });

    // ==========================================
    // PRICE INPUT FORMATTING
    // ==========================================

    function formatRupiah(angka) {
        const numberString = angka.replace(/[^,\d]/g, '').toString();
        const split = numberString.split(',');
        const sisa = split[0].length % 3;
        let rupiah = split[0].substr(0, sisa);
        const ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            const separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }

        rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
        return rupiah;
    }

    function initPriceInput(input) {
        input.addEventListener('keyup', function(e) {
            this.value = formatRupiah(this.value);
        });

        input.addEventListener('blur', function() {
            if (this.value === '') {
                this.value = '0';
            }
        });

        input.addEventListener('focus', function() {
            if (this.value === '0') {
                this.value = '';
            }
        });
    }

    // Initialize all price inputs
    document.querySelectorAll('.price-input').forEach(input => {
        initPriceInput(input);
    });

    // ==========================================
    // FORM SUBMISSION HANDLERS
    // ==========================================

    // Add modal form
    const addForm = document.querySelector('#addModal form');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            handleFormSubmit(submitBtn);
        });
    }

    // Edit modal forms
    document.querySelectorAll('[id^="editModal-"] form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            handleFormSubmit(submitBtn);
        });
    });

    // Delete form - prevent double submission
    const deleteForm = document.getElementById('deleteForm');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
        });
    }

    // Reset isSubmitting when modal is closed
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-backdrop')) {
            isSubmitting = false;
        }
    });
</script>
