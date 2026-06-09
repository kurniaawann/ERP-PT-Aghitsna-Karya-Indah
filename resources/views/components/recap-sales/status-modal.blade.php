{{-- Status Modal for Sales Report --}}
{{-- Usage: @include('components.recap-sales.status-modal', ['sale' => $sale]) --}}

<x-modal id="statusModal-{{ $sale->id_sales_recap }}" title="Update Status Pembayaran" readonly="true">

    <div class="mb-4">
        <p class="text-text-primary mb-3">Status akan berubah otomatis saat bukti pembayaran sudah diupload.</p>
        <div class="bg-surface-secondary p-3 rounded-lg mb-4">
            <p class="font-semibold text-text-heading">{{ $sale->name_proyek }}</p>
            <p class="text-sm text-text-label">Tanggal: {{ $sale->date->format('d-m-Y') }}</p>
            <p class="text-sm text-text-label">Total Profit: Rp
                {{ number_format($sale->total_profit, 0, ',', '.') }}</p>
            <p class="text-sm text-text-label">Status Saat Ini: {{ $sale->status }}</p>
        </div>

        <div class="bg-warning-light border-l-4 border-warning p-4 mt-4 rounded">
            <div class="flex items-start gap-3">
                <i class="fa-solid fa-exclamation-triangle text-warning text-xl mt-0.5"></i>
                <div>
                    <p class="font-semibold text-warning mb-1">Informasi</p>
                    <p class="text-sm text-text-primary">
                        Setelah bukti pembayaran {{ auth()->user()?->isSuperAdmin() ? 'invoice' : 'invoice proyek' }} diupload dan dikaitkan ke rekap penjualan ini,
                        status akan berubah otomatis menjadi <strong>"Lunas"</strong> jika total pembayaran sudah
                        terpenuhi.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-modal>
