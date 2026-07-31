/**
 * Helper Mata Uang
 * Utilitas bersama untuk parsing dan pemformatan nilai mata uang Rupiah Indonesia.
 */

function parseCurrencyInput(value) {
    const rawValue = String(value ?? '').trim();

    if (!rawValue) {
        return 0;
    }

    return parseInt(rawValue.replace(/[^0-9-]/g, ''), 10) || 0;
}

function formatCurrencyInput(input) {
    if (!input) return;

    const numeric = String(input.value ?? '').replace(/[^\d]/g, '');
    input.value = numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '';
}

function formatRupiah(value) {
    return 'Rp ' + (Number(value) || 0).toLocaleString('id-ID');
}

window.parseCurrencyInput = parseCurrencyInput;
window.formatCurrencyInput = formatCurrencyInput;
window.formatRupiah = formatRupiah;
