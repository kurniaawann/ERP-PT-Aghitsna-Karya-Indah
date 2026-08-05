{{-- =====================================================================
     Halaman: Laporan Stok Barang
     Tujuan: Menampilkan laporan pergerakan & nilai stok per periode
             (stok awal, masuk, keluar, retur, stok akhir, harga, nilai)
             dengan filter periode (start_date–end_date) & filter barang
             (dropdown infinite scroll). Menyediakan export PDF/Excel.
     Data dari StockReportController@index:
     - $items           : koleksi berisi item terpilih saja (placeholder dropdown).
     - $reportData      : LengthAwarePaginator manual hasil StockReportService::generateReport().
     - $summary         : ringkasan agregat (total_items, total_beginning_stock,
                          total_stock_in, total_stock_out, total_returns,
                          total_ending_stock, total_stock_value).
     - $startDate/$endDate : rentang periode filter.
     - $selectedItemId  : id barang terpilih (opsional).
     - $perPage         : jumlah baris per halaman (default 10, maks 100).
     Endpoint AJAX: route('stock-report.items-dropdown') untuk dropdown infinite scroll.
     Komponen yang di-include:
     - x-buttons.print-dropdown (export PDF/Excel dengan seluruh query filter)
     JS: inline window.stockReportConfig + @vite('resources/js/pages/inventory/stock-report.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah Laporan Stok Barang')

@section('content')
    <div class="px-4 py-4">

        {{-- SECTION: Header Halaman --}}
        <div class="mb-6">
            <div>
                <h2 class="text-2xl font-bold text-text-primary mb-1">📊 Laporan Stok Barang</h2>
                <p class="text-text-secondary mb-0">Pantau pergerakan dan nilai persediaan barang Anda</p>
            </div>
        </div>

        {{-- SECTION: Filter — Form filter periode (tanggal mulai/akhir) & pilihan barang --}}
        {{-- Auto-submit: modul stock-report.js mengirim form saat tanggal atau barang berubah
             (initDateFilter untuk tanggal, setSelected → autoFilterNow untuk pilihan barang). --}}
        <div class="bg-surface-base rounded-lg shadow-sm p-6 mb-6">
            <form method="GET" action="{{ route('stock-report.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">

                {{-- Filter Tanggal Mulai: wajib (required); value $startDate (default awal bulan berjalan) --}}
                <div>
                    <label for="start_date" class="block text-sm font-semibold text-text-primary mb-2">Tanggal Mulai</label>
                    <input type="date"
                        class="w-full px-3 py-2 border border-border-strong rounded-lg bg-surface-base text-text-input focus:outline-none focus:ring-2 focus:ring-primary"
                        id="start_date" name="start_date" value="{{ $startDate }}" required>
                </div>

                {{-- Filter Tanggal Akhir: wajib (required); value $endDate (default hari ini).
                     Validasi client-side memastikan end_date >= start_date. --}}
                <div>
                    <label for="end_date" class="block text-sm font-semibold text-text-primary mb-2">Tanggal Akhir</label>
                    <input type="date"
                        class="w-full px-3 py-2 border border-border-strong rounded-lg bg-surface-base text-text-input focus:outline-none focus:ring-2 focus:ring-primary"
                        id="end_date" name="end_date" value="{{ $endDate }}" required>
                </div>

                {{-- SECTION: Filter Pilihan Barang (Dropdown Infinite Scroll) --}}
                {{-- Dropdown kustom, bukan <select> biasa:
                     - hidden input 'item_id' menyimpan pilihan untuk form submission.
                     - daftar barang dimuat via AJAX ke route('stock-report.items-dropdown')
                       dengan pencarian & infinite scroll (10 item per load).
                     - label menampilkan item terpilih atau '- Semua Barang -' bila kosong.
                     - memilih/mereset barang memicu auto-filter (redirect ke index). --}}
                <div>
                    <label class="block text-sm font-semibold text-text-primary mb-2">Pilih Barang (Opsional)</label>

                    {{-- Hidden input untuk form submission: value diisi JS saat user memilih barang --}}
                    <input type="hidden" name="item_id" id="item_id" value="{{ $selectedItemId ?? '' }}">

                    {{-- Custom dropdown (infinite scroll): container relatif berisi tombol toggle,
                         menu dropdown (#itemDropdownMenu), input pencarian, daftar barang,
                         dan tombol reset — semua logika di modul stock-report.js. --}}
                    <div class="relative">
                        <button type="button" id="itemDropdownBtn"
                            class="w-full px-3 py-2 border border-border-strong rounded-lg bg-surface-base flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-primary">
                            <span id="itemDropdownLabel" class="text-sm text-text-primary">
                                {{ $selectedItemId ? $items[0]['id_item'] ?? $selectedItemId : '- Semua Barang -' }}
                            </span>
                            <span class="text-text-secondary">▼</span>
                        </button>

                        <div id="itemDropdownMenu"
                            class="absolute z-20 mt-1 w-full bg-surface-base border border-border-light rounded-lg shadow-sm hidden">
                            {{-- Input pencarian barang --}}
                            <div class="p-2 border-b border-border-light">
                                <input type="text" id="itemSearchInput"
                                    placeholder="Cari ID atau nama barang..."
                                    class="w-full px-3 py-2 border border-border-light rounded-lg bg-surface-base text-sm text-text-primary placeholder:text-text-secondary focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>

                            {{-- Daftar barang (infinite scroll) --}}
                            <div id="itemDropdownList" class="max-h-60 overflow-y-auto">
                                <div class="p-2 text-sm text-text-secondary" id="dropdownLoadingPlaceholder">
                                    Silakan klik scroll untuk memuat data...
                                </div>
                            </div>

                            <div class="p-2 border-t border-border-light">
                                <button type="button" id="clearItemBtn" class="text-sm text-error hover:text-error">
                                    Reset (- Semua Barang -)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Print Dropdown: queryParams = request()->except([]) → seluruh filter aktif
                     (start_date, end_date, item_id, page, per_page) diteruskan ke export. --}}
                <div class="flex items-end">
                    <x-buttons.print-dropdown
                        :pdfRoute="route('stock-report.export.pdf')"
                        :excelRoute="route('stock-report.export.excel')"
                        :queryParams="request()->except([])"
                        size="sm"
                    />
                </div>

            </form>
        </div>

        {{-- SECTION: Summary Cards — Ringkasan cepat stok dari $summary (seluruh data, bukan per halaman) --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

            {{-- Card 1: Total Jumlah Barang ($summary['total_items']) --}}
            <div class="bg-surface-base rounded-lg shadow-sm p-6 border-l-4 border-primary">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-text-secondary text-sm mb-1">Total Jumlah Barang</p>
                        <h5 class="text-2xl font-bold text-text-primary">{{ number_format($summary['total_items']) }} item
                        </h5>
                    </div>
                    <i class="fas fa-box-open text-primary text-3xl opacity-20"></i>
                </div>
            </div>

            {{-- Card 2: Stok Akhir Total ($summary['total_ending_stock']) --}}
            <div class="bg-surface-base rounded-lg shadow-sm p-6 border-l-4 border-success">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-text-secondary text-sm mb-1">Stok Akhir Total</p>
                        <h5 class="text-2xl font-bold text-text-primary">{{ number_format($summary['total_ending_stock']) }}
                            unit</h5>
                    </div>
                    <i class="fas fa-check-circle text-success text-3xl opacity-20"></i>
                </div>
            </div>

            {{-- Card 3: Nilai Stok Total dalam rupiah ($summary['total_stock_value']) --}}
            <div class="bg-surface-base rounded-lg shadow-sm p-6 border-l-4 border-warning">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-text-secondary text-sm mb-1">Nilai Stok Total</p>
                        <h5 class="text-xl font-bold text-text-primary truncate">Rp
                            {{ number_format($summary['total_stock_value']) }}</h5>
                    </div>
                    <i class="fas fa-coins text-warning text-3xl opacity-20"></i>
                </div>
            </div>
        </div>

        {{-- SECTION: Detail Summary — Ringkasan pergerakan stok per periode --}}
        {{-- Menampilkan Stok Awal, Masuk, Keluar, Retur, dan Stok Akhir agregat.
             Rumus Stok Akhir: awal + masuk - keluar - retur (lihat StockReportService). --}}
        <div class="bg-surface-base rounded-lg shadow-sm mb-6 overflow-hidden">
            <div class="bg-surface-secondary px-6 py-4 border-b border-border-light">
                <h6 class="text-sm font-semibold text-text-primary mb-0">📈 Ringkasan Pergerakan Stok Periode
                    {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} -
                    {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</h6>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-center">
                    <div>
                        <h6 class="text-text-secondary text-sm mb-2">Stok Awal</h6>
                        <h5 class="text-xl font-bold text-primary">{{ number_format($summary['total_beginning_stock']) }}
                        </h5>
                    </div>
                    <div>
                        <h6 class="text-text-secondary text-sm mb-2">Stok Masuk</h6>
                        <h5 class="text-xl font-bold text-success">{{ number_format($summary['total_stock_in']) }}</h5>
                    </div>
                    <div>
                        <h6 class="text-text-secondary text-sm mb-2">Stok Keluar</h6>
                        <h5 class="text-xl font-bold text-error">{{ number_format($summary['total_stock_out']) }}</h5>
                    </div>
                    <div>
                        <h6 class="text-text-secondary text-sm mb-2">Retur Barang</h6>
                        <h5 class="text-xl font-bold text-warning">{{ number_format($summary['total_returns']) }}</h5>
                    </div>
                    <div>
                        <h6 class="text-text-secondary text-sm mb-2">Stok Akhir</h6>
                        <h5 class="text-xl font-bold text-primary">
                            {{-- Rumus: Stok Akhir = Stok Awal + Masuk - Keluar - Retur --}}
                            {{ number_format($summary['total_beginning_stock'] + $summary['total_stock_in'] - $summary['total_stock_out'] - $summary['total_returns']) }}
                        </h5>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION: Tabel Laporan Stok — Detail data per barang --}}
        {{-- Kolom: No (nomor global memakai currentPage & perPage), ID/Nama Barang,
             Stok Awal, Masuk, Keluar, Retur, Stok Akhir, Harga Satuan, Nilai Stok.
             Data dari $reportData (LengthAwarePaginator hasil StockReportService::generateReport()). --}}
        <div class="bg-surface-base rounded-lg shadow-sm overflow-hidden">
            <div class="bg-surface-secondary px-6 py-4 border-b border-border-light">
                <h6 class="text-sm font-semibold text-text-primary mb-0">📋 Detail Laporan Stok</h6>
            </div>

            {{-- Scroll area untuk tabel --}}
            <div class="overflow-x-auto">
                <div class="max-h-[520px] overflow-y-auto">
                    <table class="w-full">
                        <thead class="bg-surface-secondary border-b border-border-light sticky top-0 z-10">
                            <tr>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-text-secondary w-12">No</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-text-secondary">ID Barang</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-text-secondary">Nama Barang</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-text-secondary">Stok Awal</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-text-secondary">Masuk</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-text-secondary">Keluar</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-text-secondary">Retur</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-text-secondary">Stok Akhir</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-text-secondary">Harga Satuan</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-text-secondary">Nilai Stok</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            @forelse($reportData as $index => $item)
                                <tr class="hover:bg-surface-secondary transition-colors">
                                    {{-- Nomor urut global: ((halaman-1) * perPage) + index + 1,
                                         agar penomoran berlanjut antar halaman. --}}
                                    <td class="px-6 py-4 text-center text-sm text-text-secondary">
                                        {{ ($reportData->currentPage() - 1) * $perPage + $index + 1 }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span
                                            class="inline-block px-2 py-1 bg-surface-secondary text-text-primary rounded text-xs font-semibold">{{ $item['id_item'] }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-text-primary">{{ $item['name_item'] }}</td>
                                    <td class="px-6 py-4 text-sm text-right">
                                        <span
                                            class="inline-block px-2 py-1 bg-primary-light text-primary rounded text-xs font-semibold">{{ number_format($item['beginning_stock']) }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right">
                                        <span
                                            class="inline-block px-2 py-1 bg-success-light text-success rounded text-xs font-semibold">{{ number_format($item['stock_in']) }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right">
                                        <span
                                            class="inline-block px-2 py-1 bg-error-light text-error rounded text-xs font-semibold">{{ number_format($item['stock_out']) }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right">
                                        <span
                                            class="inline-block px-2 py-1 bg-warning-light text-warning rounded text-xs font-semibold">{{ number_format($item['returns']) }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right">
                                        <span
                                            class="inline-block px-2 py-1 bg-surface-secondary text-text-primary rounded text-xs font-semibold">{{ number_format($item['ending_stock']) }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right text-text-secondary">Rp
                                        {{ number_format($item['capital_price']) }}</td>
                                    <td class="px-6 py-4 text-sm text-right font-bold text-text-primary">Rp
                                        {{ number_format($item['stock_value']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-6 py-12 text-center">
                                        <p class="text-text-secondary mb-0">
                                            <i class="fas fa-inbox mr-2"></i>Tidak ada data stok untuk periode ini
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- SECTION: Pagination (manual LengthAwarePaginator) --}}
            {{-- Paginasi manual dari StockReportController (default 10 per halaman, maks 100);
                 appends() mempertahankan seluruh query filter saat pindah halaman. --}}
            <div class="px-6 py-4 border-t border-border-light">
                {{ $reportData->appends(request()->query())->links() }}
            </div>
        </div>

    </div>

    {{-- SECTION: Scripts — Konfigurasi route untuk JavaScript module --}}
    {{-- window.stockReportConfig menyuplai route index & items-dropdown ke modul
         stock-report.js (dipakai untuk auto-filter & fetch data dropdown). --}}
    @push('scripts')
        <script>
            window.stockReportConfig = {
                indexRoute: '{{ route("stock-report.index") }}',
                itemsDropdownRoute: '{{ route("stock-report.items-dropdown") }}',
            };
        </script>
        @vite('resources/js/pages/inventory/stock-report.js')
    @endpush
@endsection
