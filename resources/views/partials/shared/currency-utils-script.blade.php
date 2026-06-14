function parseCurrencyInput(value) {
    const rawValue = String(value ?? '').replace(/[^0-9]/g, '');
    return rawValue ? parseInt(rawValue, 10) || 0 : 0;
}

function formatCurrencyInput(input) {
    if (!input) return;

    const numeric = String(input.value ?? '').replace(/[^\d]/g, '');
    input.value = numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '';
}

function formatRupiah(value) {
    return 'Rp ' + (Number(value) || 0).toLocaleString('id-ID');
}

