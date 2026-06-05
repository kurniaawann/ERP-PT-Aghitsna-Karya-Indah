{{-- Modal Bayar Payroll --}}
<x-modal id="payModal-{{ $payroll->id }}" title="Bayar Payroll" action="{{ route('payroll.pay', $payroll->id) }}"
    method="POST" buttonText="Bayar">
    @method('PUT')

    <div class="mb-4 p-4 bg-surface-secondary border border-border rounded-lg">
        <div class="grid grid-cols-2 gap-3 text-sm">
            <div>
                <p class="text-text-label">Karyawan:</p>
                <p class="font-semibold">{{ $payroll->employee->name }}</p>
            </div>
            <div>
                <p class="text-text-label">Periode:</p>
                <p class="font-semibold">
                    {{ \Carbon\Carbon::create($payroll->period_year, $payroll->period_month, 1)->format('F Y') }}</p>
            </div>
            <div>
                <p class="text-text-label">Gaji Bersih:</p>
                <p class="font-semibold text-success">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal Pembayaran <span class="text-error">*</span></label>
        <input type="date" name="payment_date"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            value="{{ date('Y-m-d') }}" required
            oninvalid="this.setCustomValidity('Tanggal pembayaran tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mt-4 p-3 bg-warning-light border border-warning rounded-lg">
        <p class="text-sm text-warning">
            <i class="fa-solid fa-exclamation-triangle"></i>
            <strong>Peringatan:</strong> Setelah dibayar, status payroll akan berubah menjadi "Paid" dan tidak dapat
            dihapus.
        </p>
    </div>
</x-modal>
