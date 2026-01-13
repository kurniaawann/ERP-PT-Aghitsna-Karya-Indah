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
        const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

        if (deleteButton) {
            if (checkedCheckboxes.length > 0) {
                deleteButton.disabled = false;
                deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                deleteButton.disabled = true;
                deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        if (printButton) {
            if (checkedCheckboxes.length > 0) {
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

        const form = document.getElementById('deleteForm');
        form.submit();
    }

    // ==========================================
    // DYNAMIC ITEM ROWS
    // ==========================================

    function addItemRow(modalId) {
        const container = document.getElementById('itemsContainer-' + modalId);
        const newRow = document.createElement('div');
        newRow.className = 'item-row border rounded p-3 bg-gray-50';
        newRow.innerHTML = `
            <div class="grid grid-cols-12 gap-2 mb-2">
                <div class="col-span-2">
                    <label class="block text-xs mb-1">Banyaknya</label>
                    <input type="number" name="item_banyaknya[]" class="w-full border rounded p-2 text-sm"
                        placeholder="Qty" min="1" required>
                </div>
                <div class="col-span-5">
                    <label class="block text-xs mb-1">Nama Barang</label>
                    <input type="text" name="item_nama_barang[]" class="w-full border rounded p-2 text-sm"
                        placeholder="Nama barang" required>
                </div>
                <div class="col-span-3">
                    <label class="block text-xs mb-1">Harga Satuan</label>
                    <input type="text" name="item_harga_satuan[]" class="w-full border rounded p-2 text-sm price-input"
                        placeholder="0" required>
                </div>
                <div class="col-span-2 flex items-end">
                    <button type="button" onclick="removeItemRow(this)"
                        class="w-full bg-red-500 hover:bg-red-600 text-white px-2 py-2 rounded text-sm">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        container.appendChild(newRow);

        // Initialize price formatting for the new input
        const newPriceInput = newRow.querySelector('.price-input');
        if (newPriceInput) {
            initPriceInput(newPriceInput);
        }
    }

    function removeItemRow(button) {
        const container = button.closest('.item-row').parentElement;
        if (container.children.length > 1) {
            button.closest('.item-row').remove();
        } else {
            alert('Minimal harus ada 1 item!');
        }
    }

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
            if (!handleFormSubmit(submitBtn, submitBtn.innerHTML)) {
                e.preventDefault();
            }
        });
    }

    // Edit modal forms
    document.querySelectorAll('[id^="editModal-"] form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (!handleFormSubmit(submitBtn, submitBtn.innerHTML)) {
                e.preventDefault();
            }
        });
    });

    // Reset isSubmitting when modal is closed
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-backdrop')) {
            isSubmitting = false;
        }
    });
</script>
