{{-- Status Modal for Sales Report --}}
{{-- Usage: @include('components.sales-report.status-modal', ['sale' => $sale]) --}}

<x-modal id="statusModal-{{ $sale->id_sales_report }}" title="Update Status Pembayaran"
    action="{{ route('sales-report.updateStatus', $sale->id_sales_report) }}" method="POST" buttonText="Update Status">
    @method('PATCH')

    <div class="mb-4">
        <p class="text-gray-700 mb-3">Update status pembayaran laporan penjualan:</p>
        <div class="bg-gray-50 p-3 rounded-lg mb-4">
            <p class="font-semibold text-gray-800">{{ $sale->name_proyek }}</p>
            <p class="text-sm text-gray-600">Tanggal: {{ $sale->date->format('d-m-Y') }}</p>
            <p class="text-sm text-gray-600">Total Profit: Rp
                {{ number_format($sale->total_profit, 0, ',', '.') }}</p>
        </div>

        <label class="block text-gray-700 font-semibold mb-2">Status Pembayaran <span
                class="text-error">*</span></label>
        <select name="status"
            class="w-full border border-gray-300 rounded-lg p-3 focus:border-primary focus:ring-2 focus:ring-primary-light"
            required>
            <option value="Belum Lunas" {{ $sale->status === 'Belum Lunas' ? 'selected' : '' }}>Belum Lunas
            </option>
            <option value="Lunas" {{ $sale->status === 'Lunas' ? 'selected' : '' }}>Lunas
            </option>
        </select>

        <div class="bg-warning-light border-l-4 border-warning p-4 mt-4 rounded">
            <div class="flex items-start gap-3">
                <i class="fa-solid fa-exclamation-triangle text-warning text-xl mt-0.5"></i>
                <div>
                    <p class="font-semibold text-warning mb-1">Peringatan Penting!</p>
                    <p class="text-sm text-gray-700">
                        Setelah status diubah menjadi <strong>"Lunas"</strong>, data laporan penjualan ini
                        <strong>tidak dapat diubah atau diedit lagi</strong>.
                        Pastikan semua informasi sudah benar sebelum mengonfirmasi.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-modal>
