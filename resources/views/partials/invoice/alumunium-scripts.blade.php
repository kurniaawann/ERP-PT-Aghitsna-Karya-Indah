<script>
    // ==========================================
    // LIVE CALCULATION FUNCTIONS
    // ==========================================

    // Calculate individual row total for ADD modal
    function calculateRowTotal(input) {
        const row = input.closest('.item-row');
        const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
        const harga = parseFloat(row.querySelector('.item-harga')?.value) || 0;
        const total = volume * harga;

        const totalSpan = row.querySelector('.item-total');
        if (totalSpan) {
            totalSpan.textContent = 'Rp ' + total.toLocaleString('id-ID');
        }

        updateInvoiceTotal();
    }

    // Calculate edit modal row total
    function calculateEditRowTotal(input) {
        const row = input.closest('.item-row-edit');
        const volume = parseFloat(row.querySelector('.item-volume-edit')?.value) || 0;
        const harga = parseFloat(row.querySelector('.item-harga-edit')?.value) || 0;
        const total = volume * harga;

        const totalSpan = row.querySelector('.item-total-edit');
        if (totalSpan) {
            totalSpan.textContent = 'Rp ' + total.toLocaleString('id-ID');
        }

        updateEditInvoiceTotal(input);
    }

    // Calculate grand total for ADD modal
    function updateInvoiceTotal() {
        let grandTotal = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
            const harga = parseFloat(row.querySelector('.item-harga')?.value) || 0;
            grandTotal += (volume * harga);
        });

        const totalPreview = document.getElementById('invoice-total-preview');
        if (totalPreview) {
            totalPreview.textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');
        }

        // Update terbilang
        const wordsElement = document.getElementById('invoice-total-words');
        if (wordsElement && grandTotal > 0) {
            wordsElement.textContent = 'Terbilang: ' + numberToWords(grandTotal) + ' rupiah';
        } else if (wordsElement) {
            wordsElement.textContent = '';
        }

        // Recalculate discount and DP when total changes
        calculateDiscount();
    }

    // Calculate Discount
    function calculateDiscount() {
        const discountType = document.getElementById('discount-type')?.value;
        const discountValue = parseFloat(document.getElementById('discount-value')?.value) || 0;

        // Get base total
        let baseTotal = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
            const harga = parseFloat(row.querySelector('.item-harga')?.value) || 0;
            baseTotal += (volume * harga);
        });

        let discountAmount = 0;
        if (discountType && discountValue > 0) {
            if (discountType === 'percentage') {
                discountAmount = (baseTotal * discountValue) / 100;
            } else {
                discountAmount = discountValue;
            }
        }

        const totalAfterDiscount = baseTotal - discountAmount;

        // Update UI
        const discountAmountEl = document.getElementById('discount-amount');
        const totalAfterDiscountEl = document.getElementById('total-after-discount');

        if (discountAmountEl) {
            discountAmountEl.textContent = 'Rp ' + discountAmount.toLocaleString('id-ID');
        }
        if (totalAfterDiscountEl) {
            totalAfterDiscountEl.textContent = 'Rp ' + totalAfterDiscount.toLocaleString('id-ID');
        }

        // Recalculate DP based on new total after discount
        calculateDP();
    }

    // Calculate DP
    function calculateDP() {
        const dpType = document.getElementById('dp-type')?.value;
        const dpValue = parseFloat(document.getElementById('dp-value')?.value) || 0;

        // Get base total
        let baseTotal = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
            const harga = parseFloat(row.querySelector('.item-harga')?.value) || 0;
            baseTotal += (volume * harga);
        });

        // Check if there's discount
        const discountType = document.getElementById('discount-type')?.value;
        const discountValue = parseFloat(document.getElementById('discount-value')?.value) || 0;

        let discountAmount = 0;
        if (discountType && discountValue > 0) {
            if (discountType === 'percentage') {
                discountAmount = (baseTotal * discountValue) / 100;
            } else {
                discountAmount = discountValue;
            }
        }

        const totalAfterDiscount = baseTotal - discountAmount;
        const calculationBase = totalAfterDiscount > 0 ? totalAfterDiscount : baseTotal;

        let dpAmount = 0;
        if (dpType && dpValue > 0) {
            if (dpType === 'percentage') {
                dpAmount = (calculationBase * dpValue) / 100;
            } else {
                dpAmount = dpValue;
            }
        }

        // Update UI
        const dpAmountEl = document.getElementById('dp-amount');
        if (dpAmountEl) {
            dpAmountEl.textContent = 'Rp ' + dpAmount.toLocaleString('id-ID');
        }
    }

    // Validate DP Percentage
    function validateDPPercentage() {
        const dpType = document.getElementById('dp-type')?.value;
        const dpValueInput = document.getElementById('dp-value');
        
        if (!dpValueInput) return;
        
        if (dpType === 'percentage') {
            const dpValue = parseFloat(dpValueInput.value) || 0;
            if (dpValue > 100) {
                dpValueInput.setCustomValidity('DP persentase tidak boleh lebih dari 100%');
                dpValueInput.reportValidity();
                dpValueInput.value = 100; // Cap at 100%
            } else {
                dpValueInput.setCustomValidity('');
            }
        } else {
            dpValueInput.setCustomValidity('');
        }
    }

    // Calculate edit modal total
    function updateEditInvoiceTotal(input) {
        const modal = input.closest('[id^="editModal-"]');
        if (!modal) return;

        const invoiceId = modal.id.replace('editModal-', '');
        let grandTotal = 0;

        modal.querySelectorAll('.item-row-edit').forEach(row => {
            const volume = parseFloat(row.querySelector('.item-volume-edit')?.value) || 0;
            const harga = parseFloat(row.querySelector('.item-harga-edit')?.value) || 0;
            grandTotal += (volume * harga);
        });

        const totalPreview = document.getElementById('invoice-total-preview-edit-' + invoiceId);
        if (totalPreview) {
            totalPreview.textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');
        }
    }

    // Convert number to Indonesian words (simple version)
    function numberToWords(num) {
        if (num === 0) return 'nol';

        const units = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan'];
        const teens = ['sepuluh', 'sebelas', 'dua belas', 'tiga belas', 'empat belas', 'lima belas',
            'enam belas', 'tujuh belas', 'delapan belas', 'sembilan belas'
        ];
        const tens = ['', '', 'dua puluh', 'tiga puluh', 'empat puluh', 'lima puluh',
            'enam puluh', 'tujuh puluh', 'delapan puluh', 'sembilan puluh'
        ];

        if (num >= 1000000000) {
            return Math.floor(num / 1000000000) + ' miliar ' + numberToWords(num % 1000000000);
        }
        if (num >= 1000000) {
            return Math.floor(num / 1000000) + ' juta ' + numberToWords(num % 1000000);
        }
        if (num >= 1000) {
            const thousands = Math.floor(num / 1000);
            const remainder = num % 1000;
            if (thousands === 1) {
                return 'seribu ' + numberToWords(remainder);
            }
            return numberToWords(thousands) + ' ribu ' + numberToWords(remainder);
        }
        if (num >= 100) {
            const hundreds = Math.floor(num / 100);
            const remainder = num % 100;
            if (hundreds === 1) {
                return 'seratus ' + numberToWords(remainder);
            }
            return units[hundreds] + ' ratus ' + numberToWords(remainder);
        }
        if (num >= 20) {
            return tens[Math.floor(num / 10)] + ' ' + units[num % 10];
        }
        if (num >= 10) {
            return teens[num - 10];
        }
        return units[num];
    }

    // ==========================================
    // PREVENT DOUBLE SUBMIT & LOADING STATE
    // ==========================================

    let isSubmitting = false;

    function handleFormSubmit(submitBtn) {
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
    // BULK DELETE FUNCTION
    // ==========================================

    function submitDeleteForm() {
        const form = document.getElementById('deleteForm');
        if (form) {
            form.submit();
        }
    }

    // ==========================================
    // PAYMENT ACCOUNT VALIDATION
    // ==========================================

    function validatePaymentSelection() {
        const checkboxes = document.querySelectorAll('.payment-account-checkbox:checked');
        const errorDiv = document.getElementById('payment-account-error');

        if (checkboxes.length === 0) {
            errorDiv?.classList.remove('hidden');
            return false;
        } else {
            errorDiv?.classList.add('hidden');
            return true;
        }
    }

    // Override form submit to validate payment accounts
    function validateFormBeforeSubmit(form) {
        const checkboxes = form.querySelectorAll('.payment-account-checkbox:checked');
        if (checkboxes.length === 0) {
            const errorDiv = document.getElementById('payment-account-error');
            errorDiv?.classList.remove('hidden');
            errorDiv?.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
            return false;
        }
        return true;
    }

    // ==========================================
    // MAIN SCRIPT - DOM READY
    // ==========================================

    document.addEventListener('DOMContentLoaded', function() {
        // ==========================================
        // SELECT ALL CHECKBOX FUNCTIONALITY
        // ==========================================

        const selectAllCheckbox = document.getElementById('selectAll');
        const invoiceCheckboxes = document.querySelectorAll('input[name="selected_invoices[]"]');
        const deleteButton = document.getElementById('delete-button');

        // Function to update delete button state
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

        // Uncheck "Select All" if any individual checkbox is unchecked
        invoiceCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (!this.checked) {
                    selectAllCheckbox.checked = false;
                } else {
                    // Check if all checkboxes are checked
                    const allChecked = Array.from(invoiceCheckboxes).every(cb => cb.checked);
                    selectAllCheckbox.checked = allChecked;
                }
                updateDeleteButtonState();
            });
        });

        // Initialize button state on page load
        updateDeleteButtonState();

        // ==========================================
        // ADD MODAL - ADD ITEM FUNCTIONALITY
        // ==========================================

        if (document.getElementById('add-item')) {
            document.getElementById('add-item').addEventListener('click', function(e) {
                e.preventDefault();
                const itemsContainer = document.getElementById('items-list');
                const newItem = document.createElement('div');
                newItem.className = 'item-row mb-3 p-3 border rounded bg-gray-50';
                newItem.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                        <input type="text" class="item-keterangan border rounded p-2 w-full" placeholder="Keterangan *" required
                            oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')"
                            oninput="this.setCustomValidity('')">
                        <input type="number" step="0.01" min="0" class="item-volume border rounded p-2 w-full" placeholder="Volume *" required oninput="calculateRowTotal(this)"
                            oninvalid="this.setCustomValidity('Volume tidak boleh kosong')"
                            oninput="calculateRowTotal(this); this.setCustomValidity('')">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                        <input type="text" class="item-satuan border rounded p-2 w-full" placeholder="Satuan (m3, unit) *" required
                            oninvalid="this.setCustomValidity('Satuan tidak boleh kosong')"
                            oninput="this.setCustomValidity('')">
                        <input type="number" step="0.01" min="0" class="item-harga border rounded p-2 w-full" placeholder="Harga *" required oninput="calculateRowTotal(this)"
                            oninvalid="this.setCustomValidity('Harga tidak boleh kosong')"
                            oninput="calculateRowTotal(this); this.setCustomValidity('')">
                        <div class="flex items-center">
                            <span class="item-total text-sm font-semibold text-primary">Rp 0</span>
                        </div>
                        <button type="button" class="remove-item bg-btn-delete text-white px-2 py-2 rounded hover:bg-btn-delete-hover">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                `;
                itemsContainer.appendChild(newItem);
                attachRemoveListener();
                updateInvoiceTotal();
            });
        }

        function attachRemoveListener() {
            document.querySelectorAll('.remove-item').forEach(btn => {
                btn.removeEventListener('click', removeItemClickHandler);
                btn.addEventListener('click', removeItemClickHandler);
            });
        }

        function removeItemClickHandler(e) {
            e.preventDefault();
            this.closest('.item-row').remove();
            updateInvoiceTotal();
        }

        attachRemoveListener();

        // ==========================================
        // EDIT MODAL - ADD ITEM FUNCTIONALITY
        // ==========================================

        document.querySelectorAll('.add-item-edit').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const invoiceId = this.getAttribute('data-invoice-id');
                const itemsContainer = document.getElementById('items-list-edit-' + invoiceId);
                const currentItems = itemsContainer.querySelectorAll('.item-row-edit');
                const newIndex = currentItems.length;

                const newItem = document.createElement('div');
                newItem.className = 'item-row-edit mb-3 p-3 border rounded bg-gray-50';
                newItem.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                        <input type="text" name="items[${newIndex}][keterangan]" 
                            class="item-keterangan-edit border rounded p-2 w-full" placeholder="Keterangan *" required
                            oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')"
                            oninput="this.setCustomValidity('')">
                        <input type="number" step="0.01" min="0" name="items[${newIndex}][volume]"
                            class="item-volume-edit border rounded p-2 w-full" placeholder="Volume *" required oninput="calculateEditRowTotal(this)"
                            oninvalid="this.setCustomValidity('Volume tidak boleh kosong')"
                            oninput="calculateEditRowTotal(this); this.setCustomValidity('')">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                        <input type="text" name="items[${newIndex}][satuan]"
                            class="item-satuan-edit border rounded p-2 w-full" placeholder="Satuan *" required
                            oninvalid="this.setCustomValidity('Satuan tidak boleh kosong')"
                            oninput="this.setCustomValidity('')">
                        <input type="number" step="0.01" min="0" name="items[${newIndex}][harga]"
                            class="item-harga-edit border rounded p-2 w-full" placeholder="Harga *" required oninput="calculateEditRowTotal(this)"
                            oninvalid="this.setCustomValidity('Harga tidak boleh kosong')"
                            oninput="calculateEditRowTotal(this); this.setCustomValidity('')">
                        <div class="flex items-center">
                            <span class="item-total-edit text-sm font-semibold text-primary">Rp 0</span>
                        </div>
                        <button type="button" class="remove-item-edit bg-btn-delete text-white px-2 py-2 rounded hover:bg-btn-delete-hover">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                `;
                itemsContainer.appendChild(newItem);
                attachRemoveListenerEdit();
            });
        });

        function attachRemoveListenerEdit() {
            document.querySelectorAll('.remove-item-edit').forEach(btn => {
                btn.removeEventListener('click', removeItemEditClickHandler);
                btn.addEventListener('click', removeItemEditClickHandler);
            });
        }

        function removeItemEditClickHandler(e) {
            e.preventDefault();
            const itemsContainer = this.closest('[id^="items-list-edit-"]');

            const remainingItems = itemsContainer.querySelectorAll('.item-row-edit');

            if (remainingItems.length <= 1) {
                alert('Minimal harus ada 1 item dalam invoice');
                return;
            }

            this.closest('.item-row-edit').remove();

            // Re-index items
            itemsContainer.querySelectorAll('.item-row-edit').forEach((row, index) => {
                row.querySelectorAll('input[name^="items"]').forEach(input => {
                    const fieldName = input.name.match(/\[(\w+)\]$/)[1];
                    input.name = `items[${index}][${fieldName}]`;
                });
            });

            // Update total after removing item
            const firstInput = itemsContainer.querySelector('.item-volume-edit');
            if (firstInput) {
                updateEditInvoiceTotal(firstInput);
            }
        }

        attachRemoveListenerEdit();

        // ==========================================
        // FORM SUBMIT HANDLING - ADD MODAL
        // ==========================================

        attachAddFormListener();

        function attachAddFormListener() {
            const addModalElement = document.getElementById('addModal');
            if (!addModalElement) {
                console.error('addModal not found');
                return;
            }

            const addForm = addModalElement.querySelector('form');
            if (!addForm) {
                console.error('Form in addModal not found');
                return;
            }

            console.log('Attaching submit listener to add form');

            addForm.addEventListener('submit', function(e) {
                console.log('=== FORM SUBMIT TRIGGERED ===');

                const submitBtn = this.querySelector('button[type="submit"]');

                // Prevent double submit
                if (isSubmitting) {
                    e.preventDefault();
                    return false;
                }

                const items = [];
                const itemRows = this.querySelectorAll('.item-row');

                console.log('Item rows found:', itemRows.length);

                itemRows.forEach((row, index) => {
                    const keterangan = row.querySelector('.item-keterangan')?.value || '';
                    const volumeInput = row.querySelector('.item-volume');
                    const satuanInput = row.querySelector('.item-satuan');
                    const hargaInput = row.querySelector('.item-harga');

                    const volume = volumeInput ? parseFloat(volumeInput.value) : 0;
                    const satuan = satuanInput ? satuanInput.value : '';
                    const harga = hargaInput ? parseFloat(hargaInput.value) : 0;

                    console.log(`Row ${index}:`, {
                        keterangan,
                        volume,
                        satuan,
                        harga
                    });

                    if (keterangan && !isNaN(volume) && volume > 0 && satuan && !isNaN(harga) &&
                        harga > 0) {
                        items.push({
                            keterangan,
                            volume,
                            satuan,
                            harga
                        });
                    }
                });

                if (items.length === 0) {
                    e.preventDefault();
                    alert('Minimal harus ada 1 item dalam invoice dengan data lengkap');
                    return false;
                }

                const itemsJsonField = this.querySelector('#items-json');
                if (!itemsJsonField) {
                    console.error('items-json field not found!');
                    e.preventDefault();
                    alert('Error: Field items tidak ditemukan');
                    return false;
                }

                const jsonString = JSON.stringify(items);
                itemsJsonField.value = jsonString;
                console.log('Items JSON value set:', itemsJsonField.value);
                console.log('Field name:', itemsJsonField.name);

                // Set loading state
                handleFormSubmit(submitBtn);

                // Let form submit naturally
                return true;
            });
        }

        // ==========================================
        // FORM SUBMIT HANDLING - EDIT MODALS
        // ==========================================

        document.querySelectorAll('form[action*="alumunium-invoice"]').forEach(form => {
            if (form.querySelector('[name="_method"][value="PUT"]')) {
                form.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type="submit"]');

                    // Prevent double submit
                    if (isSubmitting) {
                        e.preventDefault();
                        return false;
                    }

                    const editItems = this.querySelectorAll('.item-row-edit');
                    if (editItems.length === 0) {
                        e.preventDefault();
                        alert('Minimal harus ada 1 item dalam invoice');
                        return false;
                    }

                    // Set loading state
                    handleFormSubmit(submitBtn);
                });
            }
        });

        // ==========================================
        // DP VALIDATION - ADD MODAL
        // ==========================================

        const dpTypeSelect = document.getElementById('dp-type');
        const dpValueInput = document.getElementById('dp-value');

        if (dpTypeSelect && dpValueInput) {
            dpTypeSelect.addEventListener('change', function() {
                validateDPPercentage();
            });
        }

        // ==========================================
        // INITIALIZE TOTALS ON PAGE LOAD
        // ==========================================

        updateInvoiceTotal();
    });
</script>
