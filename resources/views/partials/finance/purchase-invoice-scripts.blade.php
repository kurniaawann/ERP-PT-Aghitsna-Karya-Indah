<script>
    // ==========================================
    // SHOW SPINNER LOADING INDICATOR
    // ==========================================

    function showSpinner(button, text = 'Memproses...') {
        if (button) {
            button.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> ${text}`;
            button.disabled = true;
            button.classList.add('opacity-70', 'cursor-not-allowed');
        }
    }

    function parseDecimalInput(value) {
        const rawValue = String(value ?? '').trim();

        if (!rawValue) {
            return 0;
        }

        return parseFloat(rawValue.replace(',', '.')) || 0;
    }

    function parseCurrencyInput(value) {
        const rawValue = String(value ?? '').trim();

        if (!rawValue) {
            return 0;
        }

        return parseInt(rawValue.replace(/[^0-9-]/g, ''), 10) || 0;
    }

    function formatRupiah(value) {
        return 'Rp ' + (Number(value) || 0).toLocaleString('id-ID');
    }

    // ==========================================
    // AUTO-CALCULATE PPN TAX FROM PERCENTAGE
    // ==========================================

    function calculatePpnTax(sellingPriceId, ppnPercentageId, ppnTaxId) {
        const sellingPriceInput = document.getElementById(sellingPriceId);
        const ppnPercentageInput = document.getElementById(ppnPercentageId);
        const ppnTaxInput = document.getElementById(ppnTaxId);

        if (!sellingPriceInput || !ppnPercentageInput || !ppnTaxInput) return;

        const sellingPrice = parseCurrencyInput(sellingPriceInput.value);
        const ppnPercentage = parseDecimalInput(ppnPercentageInput.value);
        const ppnTax = Math.round((sellingPrice * ppnPercentage) / 100);

        ppnTaxInput.value = formatRupiah(ppnTax);
    }

    function initPpnCalculation() {
        // Add modal
        const addSellingPrice = document.getElementById('addSellingPrice');
        const addPpnPercentage = document.getElementById('addPpnPercentage');

        if (addSellingPrice && addPpnPercentage) {
            addSellingPrice.addEventListener('input', () => {
                addSellingPrice.value = formatRupiah(parseCurrencyInput(addSellingPrice.value));
                calculatePpnTax('addSellingPrice', 'addPpnPercentage', 'addPpnTax');
            });
            addPpnPercentage.addEventListener('input', () => {
                calculatePpnTax('addSellingPrice', 'addPpnPercentage', 'addPpnTax');
            });
            // Calculate initial value
            calculatePpnTax('addSellingPrice', 'addPpnPercentage', 'addPpnTax');
        }

        // Edit modals
        document.querySelectorAll('[id^="editSellingPrice-"]').forEach(sellingPriceInput => {
            const invoiceId = sellingPriceInput.id.replace('editSellingPrice-', '');
            const ppnPercentageInput = document.getElementById(`editPpnPercentage-${invoiceId}`);

            if (ppnPercentageInput) {
                sellingPriceInput.addEventListener('input', () => {
                    sellingPriceInput.value = formatRupiah(parseCurrencyInput(sellingPriceInput.value));
                    calculatePpnTax(`editSellingPrice-${invoiceId}`, `editPpnPercentage-${invoiceId}`,
                        `editPpnTax-${invoiceId}`);
                });
                ppnPercentageInput.addEventListener('input', () => {
                    calculatePpnTax(`editSellingPrice-${invoiceId}`, `editPpnPercentage-${invoiceId}`,
                        `editPpnTax-${invoiceId}`);
                });
                // Calculate initial value
                calculatePpnTax(`editSellingPrice-${invoiceId}`, `editPpnPercentage-${invoiceId}`,
                    `editPpnTax-${invoiceId}`);
            }
        });
    }

    // ==========================================
    // PRINT DROPDOWN FUNCTIONALITY
    // ==========================================

    function initPrintDropdown() {
        const printDropdownButton = document.getElementById('printDropdownButton');
        const printDropdownMenu = document.getElementById('printDropdownMenu');

        if (printDropdownButton && printDropdownMenu) {
            printDropdownButton.addEventListener('click', function(event) {
                event.stopPropagation();
                printDropdownMenu.classList.toggle('hidden');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (!printDropdownButton.contains(event.target) && !printDropdownMenu.contains(event.target)) {
                    printDropdownMenu.classList.add('hidden');
                }
            });

            // Prevent dropdown from closing when clicking inside
            printDropdownMenu.addEventListener('click', function(event) {
                event.stopPropagation();
            });
        }
    }

    @include('partials.shared.select-all-script')

    // ==========================================
    // BULK DELETE FUNCTION
    // ==========================================

    function submitDeleteForm() {
        const deleteBtn = document.getElementById('confirm-btn-deleteModal');
        showSpinner(deleteBtn, 'Menghapus...');

        const form = document.getElementById('deleteForm');
        if (form) {
            form.submit();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // ==========================================
        // INITIALIZE PPN CALCULATION
        // ==========================================
        initPpnCalculation();

        // ==========================================
        // INITIALIZE PRINT DROPDOWN
        // ==========================================
        initPrintDropdown();

        // ==========================================
        // ADD FORM HANDLING
        // ==========================================
        const addModal = document.getElementById('addModal');
        const addFormElement = addModal ? addModal.querySelector('form') : null;
        const addButton = addFormElement ? addFormElement.querySelector('button[type="submit"]') : null;

        if (addFormElement && addButton) {
            addFormElement.addEventListener('submit', function() {
                showSpinner(addButton, 'Menyimpan...');
            });
        }

        // ==========================================
        // EDIT FORM HANDLING
        // ==========================================
        document.querySelectorAll('[id^="editModal-"]').forEach(editModal => {
            const editForm = editModal.querySelector('form');
            const editButton = editModal.querySelector('form button[type="submit"]');
            if (editForm && editButton) {
                editForm.addEventListener('submit', function() {
                    showSpinner(editButton, 'Menyimpan...');
                });
            }
        });

        // ==========================================
        // AUTO-OPEN ERROR MODALS & SCROLL
        // ==========================================
        const addErrorAlert = document.getElementById('addErrorAlert');
        if (addErrorAlert) {
            setTimeout(() => {
                addErrorAlert.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }, 100);
        }

        const editErrorAlerts = document.querySelectorAll('[id$="ErrorAlert"]');
        editErrorAlerts.forEach(alert => {
            if (alert.id !== 'addErrorAlert') {
                setTimeout(() => {
                    alert.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }, 100);
            }
        });

        // ==========================================
        // AUTO-DISMISS ALERT MESSAGES
        // ==========================================
        const errorAlert = document.getElementById('errorAlert');
        const successAlert = document.getElementById('successAlert');

        function autoDismissAlert(alert) {
            if (alert) {
                setTimeout(() => {
                    alert.classList.add('hidden');
                }, 5000); // Hide after 5 seconds
            }
        }

        autoDismissAlert(errorAlert);
        autoDismissAlert(successAlert);

        initSelectAll('selected_invoices[]');

        // ==========================================
        // FILTER BY MONTH, YEAR
        // ==========================================
        const monthFilter = document.querySelector('select[name="month"]') || document.getElementById(
            'month-select');
        const yearFilter = document.querySelector('select[name="year"]') || document.getElementById(
            'year-select');

        [monthFilter, yearFilter].forEach(filter => {
            if (filter) {
                filter.addEventListener('change', function() {
                    // Get the form parent - traverse up DOM tree
                    let form = this.closest('form');
                    if (form) {
                        form.submit();
                    }
                });
            }
        });
    });
</script>
