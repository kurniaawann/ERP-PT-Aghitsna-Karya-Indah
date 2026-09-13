{{-- =====================================================================
     Partial Tab: Rekap Penjualan (dipakai pada halaman Rekap tab sales)
     Data tersedia dari RecapSalesController::indexData():
     - $salesRecaps : Paginator SalesRecap (10/halaman)
     - $items       : Daftar item inventory untuk dropdown modal & autofill harga
     - $grandTotals : Grand totals (kapital, penjualan, laba)
     ===================================================================== --}}

    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- Header Rekap Penjualan --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Rekap Penjualan</h1>

        {{-- Toolbar Filter & Aksi (form mengarah ke tab aktif agar query param
             tetap bersama parameter tab) --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            <form method="GET" action="{{ route('rekap.index', ['tab' => 'sales']) }}"
                class="w-full min-[1530px]:w-auto min-[1530px]:flex-1 flex flex-col min-[1530px]:flex-row gap-3">
                <x-filters.month-filter :value="request('month')" responsive="custom" />
                <x-filters.year-filter :value="request('year')" responsive="custom" />
                <x-filters.status-filter :value="request('status')" responsive="custom" />
                <x-filters.search-input :value="request('search')" placeholder="Cari proyek..." responsive="custom" />
            </form>

            <div class="flex items-center gap-2 mt-2 min-[1530px]:mt-0 w-full min-[1530px]:w-auto">
                <div class="flex flex-col min-[1530px]:flex-row gap-2 w-full min-[1530px]:w-auto">
                    <x-buttons.print-dropdown :excelRoute="route('recap-sales.export.excel')" :pdfRoute="route('recap-sales.export.pdf')" :queryParams="['search' => request('search'), 'month' => request('month'), 'year' => request('year'), 'status' => request('status')]" responsive="custom" />
                    <x-buttons.delete-button modalId="deleteModal" responsive="custom" />
                    <x-buttons.add-button modalId="addModal" text="Tambah Laporan" responsive="custom" />
                </div>
            </div>
        </div>

        {{-- Tabel Rekap Penjualan --}}
        @include('components.finance.sales-recaps.table', [
            'salesRecaps' => $salesRecaps,
            'grandTotals' => $grandTotals,
        ])
    </div>

    {{-- Pagination --}}
    <x-pagination :paginator="$salesRecaps" />

    {{-- Modal Tambah --}}
    @include('components.finance.sales-recaps.add-modal', ['items' => $items])

    {{-- Modal Edit & Status (hanya untuk yang belum lunas) --}}
    @foreach ($salesRecaps as $sale)
        @if (!$sale->isLunas())
            @include('components.finance.sales-recaps.edit-modal', ['sale' => $sale, 'items' => $items])
            @include('components.finance.sales-recaps.status-modal', ['sale' => $sale])
        @endif
    @endforeach

    {{-- Modal Upload & Hapus Bukti Pembayaran (shared, dipicu dari modal Edit) --}}
    @include('components.finance.payment-proofs.upload-modal')
    @include('components.finance.payment-proofs.delete-modal')

    {{-- Modal Konfirmasi Bulk Delete --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah Anda yakin ingin menghapus data yang dipilih?
    </x-modal>

    @push('scripts')
        <script>
            window._itemsData = {!! json_encode($items->map(fn($item) => [
                'id_item' => $item->id_item,
                'name_item' => $item->name_item,
                'capital_price' => $item->capital_price,
                'selling_price' => $item->selling_price,
                'quantity' => $item->quantity,
            ])->values()) !!};
        </script>
        @vite(['resources/js/pages/finance/sales-recaps/index.js', 'resources/js/pages/finance/payment-proofs/embedded.js'])
    @endpush