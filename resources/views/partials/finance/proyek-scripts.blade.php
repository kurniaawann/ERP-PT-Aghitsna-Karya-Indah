<script>
    // ==========================================
    // LIVE CALCULATION FUNCTIONS
    // ==========================================

    function parseDecimalInput(inputElement) {
        const str = String(inputElement?.value ?? '').trim();
        if (!str) return 0;

        // Clean from Rp, % or other non-numeric stuff except . and ,
        const cleaned = str.replace(/Rp\s*/gi, '').replace(/%\s*/g, '').replace(/[^\d.,-]/g, '');

        if (cleaned.includes(',')) {
            // has comma => assume dot is thousand separator, comma is decimal
            return parseFloat(cleaned.replace(/\./g, '').replace(',', '.')) || 0;
        }

        // no comma => dots could be thousand separators or decimal.
        // If there are multiple dots, or a single dot followed by exactly 3 digits, it's likely a thousand separator.
        const parts = cleaned.split('.');
        if (parts.length > 2 || (parts.length === 2 && parts[1].length === 3)) {
            return parseFloat(cleaned.replace(/\./g, '')) || 0;
        }

        return parseFloat(cleaned) || 0;
    }

    function parseCurrencyInput(value) {
        const str = String(value ?? '').trim();
        if (!str) return 0;

        // example: "Rp 1.234.567,89" or "1.234.567"
        const cleaned = str.replace(/Rp\s*/gi, '');

        if (cleaned.includes(',')) {
            // comma as decimal separator
            const normalized = cleaned.replace(/\./g, '').replace(',', '.');
            const num = parseFloat(normalized);
            return Number.isFinite(num) ? num : 0;
        }

        // no comma => dots are thousand separators
        const normalized = cleaned.replace(/\./g, '');
        const num = parseFloat(normalized);
        return Number.isFinite(num) ? num : 0;
    }

    function formatCurrencyInput(input) {
        const str = String(input.value ?? '').trim();
        if (!str) return;

        const num = parseCurrencyInput(str);
        // Only round if no decimals were present? Usually for currency it's rounded.
        input.value = num ? Math.round(num).toLocaleString('id-ID') : '';
    }

    function bindEditCurrencyInput(input) {
        if (!input || input.dataset.currencyBound === '1') return;

        input.dataset.currencyBound = '1';

        input.addEventListener('focus', function() {
            // Show plain number for easier editing
            const val = parseCurrencyInput(this.value);
            this.value = val ? String(val) : '';
            requestAnimationFrame(() => this.select());
        });

        input.addEventListener('blur', function() {
            formatCurrencyInput(this);
        });
    }

    function normalizeInvoicePriceFields(form) {
        form.querySelectorAll('input[name*="[harga]"]').forEach(input => {
            const numeric = parseCurrencyInput(input.value);
            input.value = numeric ? String(numeric) : '0';
        });
    }

    // Calculate individual row total for ADD modal
    function calculateRowTotal(input) {
        const row = input.closest('.item-row');
        const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
        const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
        const total = volume * harga;

        const totalSpan = row.querySelector('.item-total');
        if (totalSpan) {
            totalSpan.textContent = 'Rp ' + total.toLocaleString('id-ID');
        }

        updateInvoiceTotal();
    }

    // Calculate edit modal row total
    function calculateEditRowTotal(input, invoiceNumber) {
        const row = input.closest('.item-row-edit');
        const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
        const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
        const total = volume * harga;

        const totalSpan = row.querySelector('.item-total');
        if (totalSpan) {
            totalSpan.textContent = 'Rp ' + total.toLocaleString('id-ID');
        }

        updateEditInvoiceTotal(invoiceNumber);
    }

    // Grand total for ADD modal
    function updateInvoiceTotal() {
        let grandTotal = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
            const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
            grandTotal += (volume * harga);
        });

        const totalPreview = document.getElementById('invoice-total-preview');
        if (totalPreview) {
            totalPreview.textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');
        }

        const wordsElement = document.getElementById('invoice-total-words');
        if (wordsElement && grandTotal > 0) {
            wordsElement.textContent = 'Terbilang: ' + numberToWords(Math.round(grandTotal)) + ' rupiah';
        } else if (wordsElement) {
            wordsElement.textContent = '';
        }

        calculateDiscount();
    }

    // Grand total for EDIT modal
    function updateEditInvoiceTotal(invoiceNumber) {
        const modal = document.getElementById('editModal-' + invoiceNumber);
        if (!modal) return;

        let grandTotal = 0;
        modal.querySelectorAll('.item-row-edit').forEach(row => {
            const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
            const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
            grandTotal += (volume * harga);
        });

        const totalPreview = document.getElementById('invoice-total-preview-edit-' + invoiceNumber);
        if (totalPreview) {
            totalPreview.textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');
        }

        calculateDiscountEdit(invoiceNumber);
    }

    // Calculate Discount for ADD modal
    function calculateDiscount() {
        const discountType = document.getElementById('discount-type')?.value;
        const discountValueInput = document.getElementById('discount-value');
        let discountValue = parseDecimalInput(discountValueInput);
        const discountError = document.getElementById('discount-error');

        if (discountValueInput) {
            if (!discountType) {
                discountValueInput.disabled = true;
                discountValueInput.value = '';
                discountValue = 0;
            } else {
                discountValueInput.disabled = false;
            }
        }

        if (discountType === 'percentage' && discountValue > 100) {
            discountValue = 100;
            if (discountValueInput) discountValueInput.value = 100;
            if (discountError) discountError.classList.remove('hidden');
        } else if (discountError) {
            discountError.classList.add('hidden');
        }

        let baseTotal = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
            const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
            baseTotal += (volume * harga);
        });

        let discountAmount = 0;
        if (discountType && discountValue > 0) {
            if (discountType === 'percentage') {
                discountAmount = Math.round((baseTotal * discountValue) / 100);
            } else {
                discountAmount = Math.round(discountValue);
            }
        }

        const totalAfterDiscount = Math.round(baseTotal - discountAmount);

        const discountAmountEl = document.getElementById('discount-amount');
        const totalAfterDiscountEl = document.getElementById('total-after-discount');

        if (discountAmountEl) discountAmountEl.textContent = 'Rp ' + discountAmount.toLocaleString('id-ID');
        if (totalAfterDiscountEl) totalAfterDiscountEl.textContent = 'Rp ' + (totalAfterDiscount > 0 ? totalAfterDiscount : 0).toLocaleString('id-ID');

        calculateDP();
    }

    // Calculate Discount for EDIT modal
    function calculateDiscountEdit(invoiceNumber) {
        const typeEl = document.getElementById('discount-type-edit-' + invoiceNumber);
        const valueEl = document.getElementById('discount-value-edit-' + invoiceNumber);
        const discountType = typeEl?.value;
        let discountValue = parseDecimalInput(valueEl);

        if (valueEl) {
            if (!discountType) {
                valueEl.disabled = true;
                if (valueEl.dataset.initialLoaded === '1') {
                    valueEl.value = 0;
                    discountValue = 0;
                }
            } else {
                valueEl.disabled = false;
            }
            valueEl.dataset.initialLoaded = '1';
        }

        if (discountType === 'percentage' && discountValue > 100) {
            discountValue = 100;
            if (valueEl) valueEl.value = 100;
        }

        const modal = document.getElementById('editModal-' + invoiceNumber);
        let baseTotal = 0;
        if (modal) {
            modal.querySelectorAll('.item-row-edit').forEach(row => {
                const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
                const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
                baseTotal += (volume * harga);
            });
        }

        let discountAmount = 0;
        if (discountType && discountValue > 0) {
            if (discountType === 'percentage') {
                discountAmount = Math.round((baseTotal * discountValue) / 100);
            } else {
                discountAmount = Math.round(discountValue);
            }
        }

        const totalAfterDiscount = Math.round(baseTotal - discountAmount);

        const discountAmountEl = document.getElementById('discount-amount-edit-' + invoiceNumber);
        const totalAfterDiscountEl = document.getElementById('total-after-discount-edit-' + invoiceNumber);

        if (discountAmountEl) discountAmountEl.textContent = 'Rp ' + discountAmount.toLocaleString('id-ID');
        if (totalAfterDiscountEl) totalAfterDiscountEl.textContent = 'Rp ' + (totalAfterDiscount > 0 ? totalAfterDiscount : 0).toLocaleString('id-ID');

        calculateDPEdit(invoiceNumber);
    }

    // Calculate DP for ADD modal
    function calculateDP() {
        const dpType = document.getElementById('dp-type')?.value;
        const dpValueInput = document.getElementById('dp-value');
        let dpValue = parseDecimalInput(dpValueInput);

        if (dpValueInput) {
            if (!dpType) {
                dpValueInput.disabled = true;
                dpValueInput.value = '';
                dpValue = 0;
            } else {
                dpValueInput.disabled = false;
            }
        }

        if (dpType === 'percentage' && dpValue > 100) {
            dpValue = 100;
            if (dpValueInput) dpValueInput.value = 100;
        }

        let baseTotal = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
            const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
            baseTotal += (volume * harga);
        });

        const discountType = document.getElementById('discount-type')?.value;
        const discountValue = parseDecimalInput(document.getElementById('discount-value'));

        let discountAmount = 0;
        if (discountType && discountValue > 0) {
            if (discountType === 'percentage') {
                discountAmount = Math.round((baseTotal * discountValue) / 100);
            } else {
                discountAmount = Math.round(discountValue);
            }
        }

        const totalAfterDiscount = Math.round(baseTotal - discountAmount);
        const calculationBase = totalAfterDiscount > 0 ? totalAfterDiscount : baseTotal;

        let dpAmount = 0;
        if (dpType && dpValue > 0) {
            if (dpType === 'percentage') {
                dpAmount = Math.round((calculationBase * dpValue) / 100);
            } else {
                dpAmount = Math.round(dpValue);
            }
        }

        const dpAmountEl = document.getElementById('dp-amount');
        if (dpAmountEl) dpAmountEl.textContent = 'Rp ' + dpAmount.toLocaleString('id-ID');
    }

    // Calculate DP for EDIT modal
    function calculateDPEdit(invoiceNumber) {
        const typeEl = document.getElementById('dp-type-edit-' + invoiceNumber);
        const valueEl = document.getElementById('dp-value-edit-' + invoiceNumber);
        const dpType = typeEl?.value;
        let dpValue = parseDecimalInput(valueEl);

        if (valueEl) {
            if (!dpType) {
                valueEl.disabled = true;
                if (valueEl.dataset.initialLoaded === '1') {
                    valueEl.value = 0;
                    dpValue = 0;
                }
            } else {
                valueEl.disabled = false;
            }
            valueEl.dataset.initialLoaded = '1';
        }

        if (dpType === 'percentage' && dpValue > 100) {
            dpValue = 100;
            if (valueEl) valueEl.value = 100;
        }

        const modal = document.getElementById('editModal-' + invoiceNumber);
        let baseTotal = 0;
        if (modal) {
            modal.querySelectorAll('.item-row-edit').forEach(row => {
                const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
                const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
                baseTotal += (volume * harga);
            });
        }

        const discountType = document.getElementById('discount-type-edit-' + invoiceNumber)?.value;
        const discountValue = parseDecimalInput(document.getElementById('discount-value-edit-' + invoiceNumber));

        let discountAmount = 0;
        if (discountType && discountValue > 0) {
            if (discountType === 'percentage') {
                discountAmount = Math.round((baseTotal * discountValue) / 100);
            } else {
                discountAmount = Math.round(discountValue);
            }
        }

        const totalAfterDiscount = Math.round(baseTotal - discountAmount);
        const calculationBase = totalAfterDiscount > 0 ? totalAfterDiscount : baseTotal;

        let dpAmount = 0;
        if (dpType && dpValue > 0) {
            if (dpType === 'percentage') {
                dpAmount = Math.round((calculationBase * dpValue) / 100);
            } else {
                dpAmount = Math.round(dpValue);
            }
        }

        const dpAmountEl = document.getElementById('dp-amount-edit-' + invoiceNumber);
        if (dpAmountEl) dpAmountEl.textContent = 'Rp ' + dpAmount.toLocaleString('id-ID');
    }

    function addProyekItemEdit(invoiceNumber) {
        const itemsContainer = document.getElementById('items-list-edit-' + invoiceNumber);
        if (!itemsContainer) return;
        
        const currentItems = itemsContainer.querySelectorAll('.item-row-edit');
        const newIndex = currentItems.length;

        const newItem = document.createElement('div');
        newItem.className = 'item-row-edit mb-3 p-3 border border-border-strong rounded-lg bg-surface-secondary';
        newItem.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                <input type="text" name="items[${newIndex}][keterangan]" 
                    class="item-keterangan border border-border-strong rounded-lg p-2 w-full text-text-input" placeholder="Keterangan *" required
                    oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')"
                    oninput="this.setCustomValidity('')">
                <input type="number" step="0.01" min="0" name="items[${newIndex}][volume]"
                    class="item-volume border border-border-strong rounded-lg p-2 w-full text-text-input" placeholder="Volume *" required 
                    oninput="calculateEditRowTotal(this, '${invoiceNumber}'); this.setCustomValidity('')"
                    oninvalid="this.setCustomValidity('Volume tidak boleh kosong')">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                <input type="text" name="items[${newIndex}][satuan]"
                    class="item-satuan border border-border-strong rounded-lg p-2 w-full text-text-input" placeholder="Satuan *" required
                    oninvalid="this.setCustomValidity('Satuan tidak boleh kosong')"
                    oninput="this.setCustomValidity('')">
                <input type="text" inputmode="numeric" name="items[${newIndex}][harga]"
                    class="item-harga border border-border-strong rounded-lg p-2 w-full text-text-input" placeholder="0" required 
                    oninput="calculateEditRowTotal(this, '${invoiceNumber}'); this.setCustomValidity('')"
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
        
        const newHargaInput = newItem.querySelector('.item-harga');
        if (newHargaInput) {
            bindEditCurrencyInput(newHargaInput);
        }

        attachRemoveListenerEdit();
        updateEditInvoiceTotal(invoiceNumber);
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
        newInstallment.className =
            'payment-installment-row mb-2 p-3 border border-border-strong rounded-lg bg-surface-base';
        newInstallment.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                <input type="text" class="payment-label border border-border-strong rounded-lg p-2 bg-surface-base text-text-input" 
                    placeholder="Label (Pembayaran Ke 1)" value="${autoLabel}">
                <input type="number" step="0.01" min="0" class="payment-amount border border-border-strong rounded-lg p-2 bg-surface-base text-text-input" 
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
        newInstallment.className =
            'payment-installment-row mb-2 p-3 border border-border-strong rounded-lg bg-surface-base';
        newInstallment.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                <input type="text" name="payment_installments[${nextIndex}][label]"
                    value="${autoLabel}" class="payment-label border border-border-strong rounded-lg p-2 bg-surface-base text-text-input" 
                    placeholder="Label (Pembayaran Ke 1)">
                <input type="number" step="0.01" min="0" 
                    name="payment_installments[${nextIndex}][amount]"
                    class="payment-amount border border-border-strong rounded-lg p-2 bg-surface-base text-text-input" 
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
            // IMPORTANT: harga bisa sudah terformat "1.000" (titik ribuan)
            // parse harus pakai parseCurrencyInput supaya tersimpan benar.
            const harga = hargaInput ? parseCurrencyInput(hargaInput.value) : 0;

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

    // Shared helper is loaded from resources/js/shared/form-submit.js

    // ==========================================
    // BULK DELETE FUNCTION
    // ==========================================

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
        const addModal = document.getElementById('addModal');
        const checkboxes = addModal?.querySelectorAll('.payment-account-checkbox') ?? [];
        const errorDiv = document.getElementById('payment-account-error');
        const submitBtn = document.getElementById('submit-btn-addModal');

        const anyChecked = Array.from(checkboxes).some(cb => cb.checked);

        if (!anyChecked) {
            errorDiv?.classList.remove('hidden');
        } else {
            errorDiv?.classList.add('hidden');
        }

        if (submitBtn) {
            submitBtn.disabled = !anyChecked;
            submitBtn.classList.toggle('opacity-50', !anyChecked);
            submitBtn.classList.toggle('cursor-not-allowed', !anyChecked);
        }

        return anyChecked;
    }

    function validatePaymentSelectionEdit(invoiceNumber) {
        const modal = document.getElementById('editModal-' + invoiceNumber);
        const checkboxes = modal?.querySelectorAll('.payment-account-checkbox') ?? [];
        const submitBtn = document.getElementById('submit-btn-editModal-' + invoiceNumber);

        const anyChecked = Array.from(checkboxes).some(cb => cb.checked);

        if (submitBtn) {
            submitBtn.disabled = !anyChecked;
            submitBtn.classList.toggle('opacity-50', !anyChecked);
            submitBtn.classList.toggle('cursor-not-allowed', !anyChecked);
        }

        return anyChecked;
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
                if (!this.checked) {
                    selectAllCheckbox.checked = false;
                } else {
                    const allChecked = Array.from(invoiceCheckboxes).every(cb => cb.checked);
                    selectAllCheckbox.checked = allChecked;
                }
                updateDeleteButtonState();
            });
        });

        updateDeleteButtonState();

        // ==========================================
        // INITIALIZE FIELDS & TOTALS
        // ==========================================

        // Format and bind currency inputs for ALL existing items (Add & Edit)
        document.querySelectorAll('.item-harga').forEach(input => {
            if (input.value) {
                formatCurrencyInput(input);
            }
            bindEditCurrencyInput(input);
        });

        // Initialize Add Modal Total
        updateInvoiceTotal();

        // Initialize ALL Edit Modals: totals, discounts & DP
        document.querySelectorAll('[id^="editModal-"]').forEach(modal => {
            const invoiceNumber = modal.id.replace('editModal-', '');
            
            // Initial calc for the modal items and summary
            updateEditInvoiceTotal(invoiceNumber);
            
            // Initialize payment account validation
            validatePaymentSelectionEdit(invoiceNumber);
            modal.querySelectorAll('.payment-account-checkbox').forEach(cb => {
                cb.addEventListener('change', () => validatePaymentSelectionEdit(invoiceNumber));
            });
        });

        // ==========================================
        // FORM SUBMIT HANDLING
        // ==========================================

        // ADD MODAL
        const addModalElement = document.getElementById('addModal');
        if (addModalElement) {
            const addForm = addModalElement.querySelector('form');
            if (addForm) {
                addForm.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const hasItems = serializeItems();

                    if (!hasItems) {
                        e.preventDefault();
                        alert('Minimal harus ada 1 item dalam invoice dengan data lengkap');
                        return false;
                    }

                    if (!handleFormSubmit(submitBtn)) {
                        e.preventDefault();
                        return false;
                    }
                });
            }
        }

        // EDIT MODALS
        document.querySelectorAll('form[action*="proyek-invoice"]').forEach(form => {
            if (form.querySelector('[name="_method"][value="PUT"]')) {
                form.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const editItems = this.querySelectorAll('.item-row-edit');
                    
                    if (editItems.length === 0) {
                        e.preventDefault();
                        alert('Minimal harus ada 1 item dalam invoice');
                        return false;
                    }

                    normalizeInvoicePriceFields(this);

                    if (!handleFormSubmit(submitBtn)) {
                        e.preventDefault();
                        return false;
                    }
                });
            }
        });

        // ==========================================
        // FILTERING
        // ==========================================

        const monthSelect = document.getElementById('month-select');
        const yearSelect = document.getElementById('year-select');

        function updateInvoiceFilterUrl() {
            const url = new URL(window.location.href);
            if (monthSelect && monthSelect.value) url.searchParams.set('month', monthSelect.value);
            else url.searchParams.delete('month');
            
            if (yearSelect && yearSelect.value) url.searchParams.set('year', yearSelect.value);
            else url.searchParams.delete('year');

            url.searchParams.delete('page');
            window.location.href = url.toString();
        }

        if (monthSelect) monthSelect.addEventListener('change', updateInvoiceFilterUrl);
        if (yearSelect) yearSelect.addEventListener('change', updateInvoiceFilterUrl);
    });

    // Handle item removal for ADD modal
    function attachRemoveListener() {
        // Redundant since we can use event delegation or inline onclick if preferred,
        // but keeping it simple for now.
    }

    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-item')) {
            e.preventDefault();
            e.target.closest('.item-row').remove();
            updateInvoiceTotal();
        }
    });

    // Handle item removal for EDIT modal
    function attachRemoveListenerEdit() { } // Legacy

    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-item-edit')) {
            e.preventDefault();
            const row = e.target.closest('.item-row-edit');
            const itemsContainer = row.closest('[id^="items-list-edit-"]');
            const invoiceNumber = itemsContainer.id.replace('items-list-edit-', '');
            
            const remainingItems = itemsContainer.querySelectorAll('.item-row-edit');
            if (remainingItems.length <= 1) {
                alert('Minimal harus ada 1 item dalam invoice');
                return;
            }

            row.remove();

            // Re-index items
            itemsContainer.querySelectorAll('.item-row-edit').forEach((r, index) => {
                r.querySelectorAll('input[name^="items"]').forEach(input => {
                    const fieldName = input.name.match(/\[(\w+)\]$/)[1];
                    input.name = `items[${index}][${fieldName}]`;
                });
            });

            updateEditInvoiceTotal(invoiceNumber);
        }
    });
</script>
