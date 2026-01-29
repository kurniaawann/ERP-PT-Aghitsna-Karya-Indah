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
    function calculateRowTotalEdit(input, invoiceNumber) {
        const row = input.closest('.item-row-edit');
        const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
        const harga = parseFloat(row.querySelector('.item-harga')?.value) || 0;
        const total = volume * harga;

        const totalSpan = row.querySelector('.item-total');
        if (totalSpan) {
            totalSpan.textContent = 'Rp ' + total.toLocaleString('id-ID');
        }

        updateEditInvoiceTotal(invoiceNumber);
    }

    // Alias untuk backward compatibility
    function calculateEditRowTotal(input) {
        calculateRowTotalEdit(input, '');
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
        const discountValueInput = document.getElementById('discount-value');
        let discountValue = parseFloat(discountValueInput?.value) || 0;
        const discountError = document.getElementById('discount-error');

        // Validasi: jika percentage, batasi maksimal 100
        if (discountType === 'percentage' && discountValue > 100) {
            discountValue = 100;
            if (discountValueInput) discountValueInput.value = 100;

            // Tampilkan error message di modal
            if (discountError) {
                discountError.classList.remove('hidden');
                setTimeout(() => {
                    discountError.classList.add('hidden');
                }, 3000); // Hide after 3 seconds
            }
        } else {
            // Sembunyikan error message jika valid
            if (discountError) {
                discountError.classList.add('hidden');
            }
        }

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
        const dpValueInput = document.getElementById('dp-value');
        let dpValue = parseFloat(dpValueInput?.value) || 0;
        const dpError = document.getElementById('dp-error');

        // Validasi: jika percentage, batasi maksimal 100
        if (dpType === 'percentage' && dpValue > 100) {
            dpValue = 100;
            if (dpValueInput) dpValueInput.value = 100;

            // Tampilkan error message di modal
            if (dpError) {
                dpError.classList.remove('hidden');
                setTimeout(() => {
                    dpError.classList.add('hidden');
                }, 3000); // Hide after 3 seconds
            }
        } else {
            // Sembunyikan error message jika valid
            if (dpError) {
                dpError.classList.add('hidden');
            }
        }

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

    // Calculate edit modal total
    function updateEditInvoiceTotal(invoiceNumber) {
        if (!invoiceNumber) return;

        const modal = document.getElementById('editModal-' + invoiceNumber);
        if (!modal) return;

        let grandTotal = 0;

        modal.querySelectorAll('.item-row-edit').forEach(row => {
            const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
            const harga = parseFloat(row.querySelector('.item-harga')?.value) || 0;
            grandTotal += (volume * harga);
        });

        const totalPreview = document.getElementById('invoice-total-preview-edit-' + invoiceNumber);
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
    // PAYMENT INSTALLMENT FUNCTIONS
    // ==========================================

    // Add a new payment installment row (for ADD modal)
    function addPaymentInstallment() {
        const installmentsContainer = document.getElementById('payment-installments-list');
        if (!installmentsContainer) return;

        const existingRows = installmentsContainer.querySelectorAll('.payment-installment-row');
        const nextNumber = existingRows.length + 1;
        const autoLabel = `Pembayaran ke ${nextNumber}`;

        const newInstallment = document.createElement('div');
        newInstallment.className = 'payment-installment-row mb-2 p-3 border rounded bg-white';
        newInstallment.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                <input type="text" class="payment-label border rounded p-2" 
                    placeholder="Label (Pembayaran Ke 1)" value="${autoLabel}">
                <input type="number" step="0.01" min="0" class="payment-amount border rounded p-2" 
                    placeholder="Jumlah (Rp)">
                <button type="button" onclick="removePaymentInstallment(this)" 
                    class="bg-btn-delete text-white px-2 py-2 rounded hover:bg-btn-delete-hover">
                    <i class="fa-solid fa-trash"></i> Hapus
                </button>
            </div>
        `;
        installmentsContainer.appendChild(newInstallment);
    }

    // Add a new payment installment row for EDIT modal
    function addPaymentInstallmentEdit(invoiceNumber) {
        const installmentsContainer = document.getElementById('payment-installments-list-edit-' + invoiceNumber);
        if (!installmentsContainer) return;

        const existingRows = installmentsContainer.querySelectorAll('.payment-installment-row');
        const nextIndex = existingRows.length;
        const nextNumber = nextIndex + 1;
        const autoLabel = `Pembayaran ke ${nextNumber}`;

        const newInstallment = document.createElement('div');
        newInstallment.className = 'payment-installment-row mb-2 p-3 border rounded bg-white';
        newInstallment.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                <input type="text" name="payment_installments[${nextIndex}][label]"
                    value="${autoLabel}" class="payment-label border rounded p-2" 
                    placeholder="Label (Pembayaran Ke 1)">
                <input type="number" step="0.01" min="0" 
                    name="payment_installments[${nextIndex}][amount]"
                    class="payment-amount border rounded p-2" 
                    placeholder="Jumlah (Rp)">
                <button type="button" onclick="removePaymentInstallmentEdit(this, '${invoiceNumber}')" 
                    class="bg-btn-delete text-white px-2 py-2 rounded hover:bg-btn-delete-hover">
                    <i class="fa-solid fa-trash"></i> Hapus
                </button>
            </div>
        `;
        installmentsContainer.appendChild(newInstallment);
    }

    // Remove a payment installment row
    function removePaymentInstallment(button) {
        const container = button.closest('#payment-installments-list');
        button.closest('.payment-installment-row').remove();

        // Re-number remaining installments
        if (container) {
            const rows = container.querySelectorAll('.payment-installment-row');
            rows.forEach((row, index) => {
                const labelInput = row.querySelector('.payment-label');
                if (labelInput && labelInput.value.startsWith('Pembayaran ke ')) {
                    labelInput.value = `Pembayaran ke ${index + 1}`;
                }
            });
        }
    }

    // Remove payment installment in edit modal
    function removePaymentInstallmentEdit(button, invoiceNumber) {
        const container = document.getElementById('payment-installments-list-edit-' + invoiceNumber);
        button.closest('.payment-installment-row').remove();

        // Re-index remaining installments
        if (container) {
            const rows = container.querySelectorAll('.payment-installment-row');
            rows.forEach((row, index) => {
                const labelInput = row.querySelector('.payment-label');
                const amountInput = row.querySelector('.payment-amount');

                // Update name attributes
                if (labelInput) {
                    labelInput.name = `payment_installments[${index}][label]`;
                    // Update label if it's auto-generated
                    if (labelInput.value.startsWith('Pembayaran ke ')) {
                        labelInput.value = `Pembayaran ke ${index + 1}`;
                    }
                }
                if (amountInput) {
                    amountInput.name = `payment_installments[${index}][amount]`;
                }
            });
        }
    }

    // Collect all payment installment data into JSON
    function collectPaymentInstallments() {
        const installments = [];
        const installmentRows = document.querySelectorAll('.payment-installment-row');

        installmentRows.forEach((row, index) => {
            const label = row.querySelector('.payment-label')?.value || '';
            const amount = row.querySelector('.payment-amount')?.value || '';

            if (label && amount) {
                installments.push({
                    label: label,
                    amount: parseFloat(amount)
                });
            }
        });

        return installments;
    }

    // Serialize items and payment installments for form submission
    function serializeItems() {
        const items = [];
        const itemRows = document.querySelectorAll('.item-row');

        itemRows.forEach((row, index) => {
            const keterangan = row.querySelector('.item-keterangan')?.value || '';
            const volumeInput = row.querySelector('.item-volume');
            const satuanInput = row.querySelector('.item-satuan');
            const hargaInput = row.querySelector('.item-harga');

            const volume = volumeInput ? parseFloat(volumeInput.value) : 0;
            const satuan = satuanInput ? satuanInput.value : '';
            const harga = hargaInput ? parseFloat(hargaInput.value) : 0;

            if (keterangan && !isNaN(volume) && volume > 0 && satuan && !isNaN(harga) && harga > 0) {
                items.push({
                    keterangan,
                    volume,
                    satuan,
                    harga
                });
            }
        });

        // Set items JSON
        const itemsJsonField = document.querySelector('#items-json');
        if (itemsJsonField) {
            itemsJsonField.value = JSON.stringify(items);
        }

        // Collect and set payment installments JSON
        const installments = collectPaymentInstallments();
        const installmentsJsonField = document.querySelector('#payment-installments-json');
        if (installmentsJsonField) {
            installmentsJsonField.value = JSON.stringify(installments);
        }

        return items.length > 0;
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

    // ==========================================
    // DELETE INDIVIDUAL INVOICE
    // ==========================================

    function deleteInvoiceProyek(invoiceId) {
        if (confirm('Apakah Anda yakin ingin menghapus invoice ini?')) {
            const form = document.getElementById('delete-form-' + invoiceId);
            if (form) {
                form.submit();
            }
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
        // ADD PAYMENT INSTALLMENT BUTTON
        // ==========================================

        const addInstallmentBtn = document.getElementById('add-payment-installment');
        if (addInstallmentBtn) {
            addInstallmentBtn.addEventListener('click', function(e) {
                e.preventDefault();
                addPaymentInstallment();
            });
        }

        // ==========================================
        // EDIT MODAL - ADD PAYMENT INSTALLMENT BUTTONS
        // ==========================================

        document.querySelectorAll('[id^="add-payment-installment-edit-"]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const invoiceNumber = this.id.replace('add-payment-installment-edit-', '');
                addPaymentInstallmentEdit(invoiceNumber);
            });
        });

        // ==========================================
        // EDIT MODAL - ADD ITEM FUNCTIONALITY
        // ==========================================

        document.querySelectorAll('[id^="add-item-edit-"]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const invoiceNumber = this.id.replace('add-item-edit-', '');
                const itemsContainer = document.getElementById('items-list-edit-' +
                    invoiceNumber);
                const currentItems = itemsContainer.querySelectorAll('.item-row-edit');
                const newIndex = currentItems.length;

                const newItem = document.createElement('div');
                newItem.className = 'item-row-edit mb-3 p-3 border rounded bg-gray-50';
                newItem.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                        <input type="text" name="items[${newIndex}][keterangan]" 
                            class="item-keterangan border rounded p-2 w-full" placeholder="Keterangan *" required
                            oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')"
                            oninput="this.setCustomValidity('')">
                        <input type="number" step="0.01" min="0" name="items[${newIndex}][volume]"
                            class="item-volume border rounded p-2 w-full" placeholder="Volume *" required 
                            oninput="calculateRowTotalEdit(this, '${invoiceNumber}')"
                            oninvalid="this.setCustomValidity('Volume tidak boleh kosong')">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                        <input type="text" name="items[${newIndex}][satuan]"
                            class="item-satuan border rounded p-2 w-full" placeholder="Satuan *" required
                            oninvalid="this.setCustomValidity('Satuan tidak boleh kosong')"
                            oninput="this.setCustomValidity('')">
                        <input type="number" step="0.01" min="0" name="items[${newIndex}][harga]"
                            class="item-harga border rounded p-2 w-full" placeholder="Harga *" required 
                            oninput="calculateRowTotalEdit(this, '${invoiceNumber}')"
                            oninvalid="this.setCustomValidity('Harga tidak boleh kosong')">
                        <div class="flex items-center">
                            <span class="item-total text-sm font-semibold text-primary">Rp 0</span>
                        </div>
                        <button type="button" class="remove-item-edit bg-btn-delete text-white px-2 py-2 rounded hover:bg-btn-delete-hover">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                `;
                itemsContainer.appendChild(newItem);
                attachRemoveListenerEdit();

                // Calculate total for the new item
                const volumeInput = newItem.querySelector('.item-volume');
                if (volumeInput) {
                    calculateRowTotalEdit(volumeInput, invoiceNumber);
                }
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
            const invoiceNumber = itemsContainer.id.replace('items-list-edit-', '');
            if (invoiceNumber) {
                updateEditInvoiceTotal(invoiceNumber);
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

                // Serialize items and payment installments
                const hasItems = serializeItems();

                if (!hasItems) {
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

        document.querySelectorAll('form[action*="proyek-invoice"]').forEach(form => {
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
        // INITIALIZE TOTALS ON PAGE LOAD
        // ==========================================

        updateInvoiceTotal();
    });
</script>
