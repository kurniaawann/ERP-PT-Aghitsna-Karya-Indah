<script>
    window.paymentProofInvoiceData = @json($availableInvoices);
    const PAYMENT_PROOF_INVOICE_CHUNK_SIZE = 10;

    function getPaymentProofConfig(prefix) {
        return {
            module: document.getElementById(`payment-proof-module-${prefix}`),
            invoiceType: document.getElementById(`payment-proof-invoice-type-${prefix}`),
            invoiceNumber: document.getElementById(`payment-proof-invoice-number-${prefix}`),
            stageText: document.getElementById(`payment-proof-stage-${prefix}`),
            stageInput: document.getElementById(`payment-proof-stage-input-${prefix}`),
            stageWrap: document.getElementById(`payment-proof-stage-wrap-${prefix}`),
            amountWrap: document.getElementById(`payment-proof-amount-wrap-${prefix}`),
            amountInput: document.getElementById(`payment-proof-amount-${prefix}`),
            amountHelp: document.getElementById(`payment-proof-amount-help-${prefix}`),
        };
    }

    function getPaymentProofInvoiceData(prefix) {
        const config = getPaymentProofConfig(prefix);
        if (!config.module || !config.invoiceType || !config.invoiceNumber) return [];

        const moduleValue = config.module.value;
        const invoiceTypeValue = config.invoiceType.value;

        return window.paymentProofInvoiceData?.[moduleValue]?.[invoiceTypeValue] ?? [];
    }

    function formatCurrency(value) {
        const numericValue = Number(value || 0);
        return 'Rp ' + numericValue.toLocaleString('id-ID');
    }

    function appendPaymentProofInvoiceOptions(prefix, count = PAYMENT_PROOF_INVOICE_CHUNK_SIZE) {
        const config = getPaymentProofConfig(prefix);
        const invoiceData = getPaymentProofInvoiceData(prefix);

        if (!config.invoiceNumber || invoiceData.length === 0) {
            return;
        }

        const loadedCount = Number(config.invoiceNumber.dataset.loadedCount || 0);
        const nextItems = invoiceData.slice(loadedCount, loadedCount + count);

        nextItems.forEach(item => {
            const option = document.createElement('option');
            option.value = item.value;

            const optionSuffix = item.is_fully_paid ? ' (Lunas)' : '';
            option.textContent = `${item.label}${optionSuffix}`;
            option.dataset.nextStage = item.next_stage || '';
            option.dataset.remainingAmount = item.remaining_amount || 0;
            option.dataset.netAmount = item.net_amount || 0;
            option.dataset.paidAmount = item.paid_amount || 0;
            option.dataset.isFullyPaid = item.is_fully_paid ? '1' : '0';

            if (item.is_fully_paid) {
                option.disabled = true;
            }

            config.invoiceNumber.appendChild(option);
        });

        config.invoiceNumber.dataset.loadedCount = String(loadedCount + nextItems.length);
    }

    function updatePaymentProofAmountSection(prefix) {
        const config = getPaymentProofConfig(prefix);

        if (!config.invoiceNumber || !config.amountInput || !config.amountHelp || !config.amountWrap) {
            return;
        }

        const selectedOption = config.invoiceNumber.options[config.invoiceNumber.selectedIndex];
        const remainingAmount = Number(selectedOption?.dataset?.remainingAmount || 0);
        const netAmount = Number(selectedOption?.dataset?.netAmount || 0);

        if (config.invoiceType.value !== 'proyek') {
            config.amountWrap.classList.add('hidden');
            config.amountInput.disabled = true;
            config.amountInput.required = false;
            config.amountInput.removeAttribute('max');
            config.amountInput.value = selectedOption?.value ? netAmount : '';
            config.amountHelp.textContent = selectedOption?.value ?
                `Nominal mengikuti total invoice ${formatCurrency(netAmount)}.` :
                'Pilih invoice terlebih dahulu agar nominal otomatis terisi.';
            return;
        }

        config.amountWrap.classList.remove('hidden');
        config.amountInput.disabled = false;
        config.amountInput.required = true;

        if (!selectedOption || !selectedOption.value) {
            config.amountInput.removeAttribute('max');
            config.amountHelp.textContent = 'Pilih invoice terlebih dahulu agar sisa tagihan tampil di sini.';
            return;
        }

        if (remainingAmount > 0) {
            config.amountInput.max = String(remainingAmount);
            config.amountHelp.textContent = `Sisa tagihan invoice ini ${formatCurrency(remainingAmount)}.`;
        } else {
            config.amountInput.removeAttribute('max');
            config.amountHelp.textContent = 'Invoice ini sudah lunas.';
        }
    }

    function updatePaymentProofStage(prefix) {
        const config = getPaymentProofConfig(prefix);
        if (!config.invoiceNumber || !config.stageText || !config.stageInput) return;

        const selectedOption = config.invoiceNumber.options[config.invoiceNumber.selectedIndex];
        const nextStage = selectedOption?.dataset?.nextStage;

        if (config.stageWrap) {
            config.stageWrap.classList.toggle('hidden', config.invoiceType.value !== 'proyek');
        }

        if (config.invoiceType.value !== 'proyek') {
            config.stageText.textContent = 'Tidak ada tahap pembayaran';
            config.stageInput.value = '';
        } else if (nextStage) {
            config.stageText.textContent = `Pembayaran ke ${nextStage}`;
            config.stageInput.value = nextStage;
        } else {
            config.stageText.textContent = '-';
            config.stageInput.value = '';
        }

        updatePaymentProofAmountSection(prefix);
    }

    function loadPaymentProofInvoices(prefix, selectedInvoiceNumber = null) {
        const config = getPaymentProofConfig(prefix);
        const invoiceData = getPaymentProofInvoiceData(prefix);

        if (!config.module || !config.invoiceType || !config.invoiceNumber) return;

        config.invoiceNumber.innerHTML = '<option value="">Pilih invoice</option>';
        config.invoiceNumber.disabled = invoiceData.length === 0;
        config.invoiceNumber.dataset.loadedCount = '0';

        appendPaymentProofInvoiceOptions(prefix);

        if (selectedInvoiceNumber) {
            while (
                Number(config.invoiceNumber.dataset.loadedCount || 0) < invoiceData.length &&
                !Array.from(config.invoiceNumber.options).some(option => option.value === selectedInvoiceNumber)
            ) {
                appendPaymentProofInvoiceOptions(prefix);
            }

            config.invoiceNumber.value = selectedInvoiceNumber;
        }

        if (config.stageWrap) {
            config.stageWrap.classList.toggle('hidden', config.invoiceType.value !== 'proyek');
        }

        updatePaymentProofStage(prefix);

        if (config.invoiceNumber.__paymentProofScrollBound !== true) {
            config.invoiceNumber.addEventListener('scroll', () => {
                const currentInvoiceData = getPaymentProofInvoiceData(prefix);
                const remainingSpace = config.invoiceNumber.scrollHeight - config.invoiceNumber.scrollTop -
                    config.invoiceNumber.clientHeight;

                if (remainingSpace <= 4) {
                    const loadedCount = Number(config.invoiceNumber.dataset.loadedCount || 0);

                    if (loadedCount < currentInvoiceData.length) {
                        appendPaymentProofInvoiceOptions(prefix);
                    }
                }
            });

            config.invoiceNumber.__paymentProofScrollBound = true;
        }
    }

    function updatePaymentProofInvoices(prefix, selectedInvoiceNumber = null) {
        const config = getPaymentProofConfig(prefix);
        if (!config.module || !config.invoiceType || !config.invoiceNumber) return;

        loadPaymentProofInvoices(prefix, selectedInvoiceNumber);
    }

    function bindPaymentProofForm(prefix, defaults = {}) {
        const config = getPaymentProofConfig(prefix);
        if (!config.module || !config.invoiceType || !config.invoiceNumber) return;

        if (defaults.moduleType) config.module.value = defaults.moduleType;
        if (defaults.invoiceType) config.invoiceType.value = defaults.invoiceType;

        updatePaymentProofInvoices(prefix, defaults.invoiceNumber ?? null);

        if (defaults.amount && config.amountInput) {
            config.amountInput.value = defaults.amount;
        }

        config.module.addEventListener('change', () => updatePaymentProofInvoices(prefix));
        config.invoiceType.addEventListener('change', () => updatePaymentProofInvoices(prefix));
        config.invoiceNumber.addEventListener('change', () => updatePaymentProofStage(prefix));

        if (config.amountInput) {
            config.amountInput.addEventListener('input', () => updatePaymentProofAmountSection(prefix));
        }

        updatePaymentProofStage(prefix);
        updatePaymentProofAmountSection(prefix);
    }

    document.addEventListener('DOMContentLoaded', function() {
        bindPaymentProofForm('create');

        @foreach ($paymentProofs as $paymentProof)
            bindPaymentProofForm('edit-{{ $paymentProof->id }}', {
                moduleType: @json($paymentProof->module_type),
                invoiceType: @json($paymentProof->invoice_type),
                invoiceNumber: @json($paymentProof->invoice_number),
                amount: @json($paymentProof->amount ?? ''),
            });
        @endforeach
    });
</script>
