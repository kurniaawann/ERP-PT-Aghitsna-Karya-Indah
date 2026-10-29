{{-- =====================================================================
     Partial: Laporan Stok (dalam Laporan Akhir, gaya Filament)
     Logika/data sama dengan halaman Laporan Stok lama (StockReportController),
     hanya form disubmit ke route report.final dengan tab=stock agar tetap
     berada di halaman Laporan Akhir. Export tetap ke endpoint terpisah
     (stock-report.export.pdf / stock-report.export.excel) lewat tombol Export
     di header halaman.

     Hook JS (inventory/stock-report.js): #start_date, #end_date (auto-submit
     saat berubah), #item_id, #itemDropdownBtn, #itemDropdownLabel,
     #itemDropdownMenu, #itemSearchInput, #itemDropdownList, #clearItemBtn.
     ===================================================================== --}}
@php
    $qty = fn ($value) => number_format($value ?? 0, 0, ',', '.');
    $rupiah = fn ($value) => 'Rp ' . number_format($value ?? 0, 0, ',', '.');
@endphp

<div class="flex flex-col gap-y-6">

    {{-- SECTION: Filter — periode (tanggal mulai/akhir) & pilihan barang --}}
    <x-fi.card class="p-4 sm:p-6">
        <form method="GET" action="{{ route('report.final') }}" class="grid grid-cols-1 gap-4 md:grid-cols-3">

            {{-- Hidden: pertahankan tab aktif --}}
            <input type="hidden" name="tab" value="stock">

            {{-- Filter Tanggal Mulai --}}
            <x-fi.field label="Tanggal Mulai" for="start_date">
                <x-fi.input type="date" id="start_date" name="start_date" :value="$startDate" required />
            </x-fi.field>

            {{-- Filter Tanggal Akhir --}}
            <x-fi.field label="Tanggal Akhir" for="end_date">
                <x-fi.input type="date" id="end_date" name="end_date" :value="$endDate" required />
            </x-fi.field>

            {{-- Filter Pilihan Barang (dropdown pencarian + infinite scroll, stock-report.js) --}}
            <x-fi.field label="Pilih Barang (Opsional)" for="itemDropdownBtn">
                {{-- Hidden input untuk form submission: value diisi JS saat user memilih barang --}}
                <input type="hidden" name="item_id" id="item_id" value="{{ $selectedItemId ?? '' }}">

                <div class="relative">
                    <button type="button" id="itemDropdownBtn" aria-haspopup="listbox"
                        class="flex w-full items-center gap-x-2 rounded-lg bg-white py-1.5 pe-3 ps-3 text-start text-base shadow-sm outline-none ring-1 ring-gray-950/10 transition duration-75 focus-visible:ring-2 focus-visible:ring-primary sm:text-sm sm:leading-6">
                        <x-fi.icon name="cube" class="h-5 w-5 shrink-0 text-gray-400" />
                        <span id="itemDropdownLabel" class="min-w-0 flex-1 truncate text-gray-950">
                            {{ $selectedItemId ? $items[0]['id_item'] ?? $selectedItemId : '- Semua Barang -' }}
                        </span>
                        <x-fi.icon name="chevron-down" class="h-4 w-4 shrink-0 text-gray-400" />
                    </button>

                    <div id="itemDropdownMenu"
                        class="absolute z-20 mt-2 hidden w-full overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-gray-950/5">
                        {{-- Input pencarian barang --}}
                        <div class="border-b border-gray-200 p-2">
                            <x-fi.input id="itemSearchInput" placeholder="Cari ID atau nama barang..."
                                prefix-icon="magnifying-glass" autocomplete="off" aria-label="Cari barang" />
                        </div>

                        {{-- Daftar barang (infinite scroll) --}}
                        <div id="itemDropdownList" class="max-h-60 overflow-y-auto p-1">
                            <div class="p-2 text-sm text-gray-500" id="dropdownLoadingPlaceholder">
                                Silakan klik scroll untuk memuat data...
                            </div>
                        </div>

                        <div class="border-t border-gray-200 p-1">
                            <button type="button" id="clearItemBtn"
                                class="flex w-full items-center gap-2 rounded-md p-2 text-start text-sm font-medium text-red-600 outline-none transition-colors duration-75 hover:bg-gray-50 focus-visible:bg-gray-50">
                                <x-fi.icon name="x-mark" class="h-5 w-5 text-red-500" />
                                Reset (- Semua Barang -)
                            </button>
                        </div>
                    </div>
                </div>
            </x-fi.field>
        </form>
    </x-fi.card>

    {{-- SECTION: Kartu Ringkasan --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-fi.stat label="Total Jumlah Barang" icon="cube" color="primary">
            {{ $qty($summary['total_items']) }} <span class="text-base font-medium text-gray-500">item</span>
        </x-fi.stat>
        <x-fi.stat label="Stok Akhir Total" icon="check-circle" color="success">
            {{ $qty($summary['total_ending_stock']) }} <span class="text-base font-medium text-gray-500">unit</span>
        </x-fi.stat>
        <x-fi.stat label="Nilai Stok Total" icon="banknotes" color="warning">
            {{ $rupiah($summary['total_stock_value']) }}
        </x-fi.stat>
    </div>

    {{-- SECTION: Ringkasan Pergerakan Stok periode --}}
    <x-fi.section heading="Ringkasan Pergerakan Stok"
        :description="'Periode ' . \Carbon\Carbon::parse($startDate)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($endDate)->format('d/m/Y')">
        @php
            $movements = [
                ['Stok Awal', $summary['total_beginning_stock'], 'text-primary'],
                ['Stok Masuk', $summary['total_stock_in'], 'text-emerald-600'],
                ['Stok Keluar', $summary['total_stock_out'], 'text-red-600'],
                ['Retur Barang', $summary['total_returns'], 'text-amber-600'],
                ['Stok Akhir', $summary['total_beginning_stock'] + $summary['total_stock_in'] - $summary['total_stock_out'] - $summary['total_returns'], 'text-primary'],
            ];
        @endphp
        <dl class="grid grid-cols-2 gap-4 text-center md:grid-cols-5">
            @foreach ($movements as [$movementLabel, $movementValue, $movementColor])
                <div class="rounded-xl p-4 ring-1 ring-gray-950/10">
                    <dt class="text-sm text-gray-500">{{ $movementLabel }}</dt>
                    <dd class="mt-1 text-xl font-semibold tabular-nums {{ $movementColor }}">{{ $qty($movementValue) }}</dd>
                </div>
            @endforeach
        </dl>
    </x-fi.section>

    {{-- SECTION: Tabel Detail Laporan Stok --}}
    <x-fi.section heading="Detail Laporan Stok" flush>
        <div class="relative max-h-[520px] overflow-auto">
            <table class="w-full table-auto divide-y divide-gray-200 text-start">
                <thead class="sticky top-0 z-10 bg-gray-50">
                    <tr>
                        <x-fi.table.th align="center" class="w-1">No</x-fi.table.th>
                        <x-fi.table.th>ID Barang</x-fi.table.th>
                        <x-fi.table.th>Nama Barang</x-fi.table.th>
                        <x-fi.table.th align="end">Stok Awal</x-fi.table.th>
                        <x-fi.table.th align="end">Masuk</x-fi.table.th>
                        <x-fi.table.th align="end">Keluar</x-fi.table.th>
                        <x-fi.table.th align="end">Retur</x-fi.table.th>
                        <x-fi.table.th align="end">Stok Akhir</x-fi.table.th>
                        <x-fi.table.th align="end">Harga Satuan</x-fi.table.th>
                        <x-fi.table.th align="end">Nilai Stok</x-fi.table.th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                    @forelse ($reportData as $index => $item)
                        <tr class="bg-white transition duration-75 hover:bg-gray-50">
                            <x-fi.table.td align="center" class="tabular-nums text-gray-500">
                                {{ ($reportData->currentPage() - 1) * $perPage + $index + 1 }}
                            </x-fi.table.td>
                            <x-fi.table.td>
                                <span class="rounded-md bg-gray-50 px-2 py-1 font-mono text-xs text-gray-950 ring-1 ring-inset ring-gray-950/10">{{ $item['id_item'] }}</span>
                            </x-fi.table.td>
                            <x-fi.table.td class="min-w-[12rem] whitespace-normal font-medium">{{ $item['name_item'] }}</x-fi.table.td>
                            <x-fi.table.td align="end"><x-fi.badge color="primary">{{ $qty($item['beginning_stock']) }}</x-fi.badge></x-fi.table.td>
                            <x-fi.table.td align="end"><x-fi.badge color="success">{{ $qty($item['stock_in']) }}</x-fi.badge></x-fi.table.td>
                            <x-fi.table.td align="end"><x-fi.badge color="danger">{{ $qty($item['stock_out']) }}</x-fi.badge></x-fi.table.td>
                            <x-fi.table.td align="end"><x-fi.badge color="warning">{{ $qty($item['returns']) }}</x-fi.badge></x-fi.table.td>
                            <x-fi.table.td align="end"><x-fi.badge color="gray">{{ $qty($item['ending_stock']) }}</x-fi.badge></x-fi.table.td>
                            <x-fi.table.td align="end" class="tabular-nums text-gray-700">{{ $rupiah($item['capital_price']) }}</x-fi.table.td>
                            <x-fi.table.td align="end" class="font-semibold tabular-nums">{{ $rupiah($item['stock_value']) }}</x-fi.table.td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <x-fi.empty-state icon="inbox" heading="Tidak ada data stok untuk periode ini"
                                    description="Ubah tanggal atau pilihan barang untuk melihat data lain." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginasi (manual LengthAwarePaginator) --}}
        <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
            <x-fi.pagination :paginator="$reportData" />
        </div>
    </x-fi.section>
</div>

{{-- Konfigurasi route untuk modul stock-report.js (index = halaman Laporan Akhir) --}}
@push('scripts')
    <script>
        window.stockReportConfig = {
            indexRoute: '{{ route("report.final", ["tab" => "stock"]) }}',
            itemsDropdownRoute: '{{ route("stock-report.items-dropdown") }}',
        };
    </script>
    @vite('resources/js/pages/inventory/stock-report.js')
@endpush
