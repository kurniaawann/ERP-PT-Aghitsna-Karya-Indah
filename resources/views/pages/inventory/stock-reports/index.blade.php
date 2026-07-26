@extends('layouts.app')

@section('title', 'Laporan Stok Barang')

@section('content')
    <div class="px-4 py-4">

        {{-- Header Halaman --}}
        <div class="mb-6">
            <div>
                <h2 class="text-2xl font-bold text-text-primary mb-1">📊 Laporan Stok Barang</h2>
                <p class="text-text-secondary mb-0">Pantau pergerakan dan nilai persediaan barang Anda</p>
            </div>
        </div>

        {{-- Filter Card: Form filter periode dan barang --}}
        <div class="bg-surface-base rounded-lg shadow-sm p-6 mb-6">
            <form method="GET" action="{{ route('stock-report.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">

                {{-- Filter Tanggal Mulai --}}
                <div>
                    <label for="start_date" class="block text-sm font-semibold text-text-primary mb-2">Tanggal Mulai</label>
                    <input type="date"
                        class="w-full px-3 py-2 border border-border-strong rounded-lg bg-surface-base text-text-input focus:outline-none focus:ring-2 focus:ring-primary"
                        id="start_date" name="start_date" value="{{ $startDate }}" required>
                </div>

                {{-- Filter Tanggal Akhir --}}
                <div>
                    <label for="end_date" class="block text-sm font-semibold text-text-primary mb-2">Tanggal Akhir</label>
                    <input type="date"
                        class="w-full px-3 py-2 border border-border-strong rounded-lg bg-surface-base text-text-input focus:outline-none focus:ring-2 focus:ring-primary"
                        id="end_date" name="end_date" value="{{ $endDate }}" required>
                </div>

                {{-- Filter Pilihan Barang (Dropdown Infinite Scroll) --}}
                <div>
                    <label class="block text-sm font-semibold text-text-primary mb-2">Pilih Barang (Opsional)</label>

                    {{-- Hidden input untuk form submission --}}
                    <input type="hidden" name="item_id" id="item_id" value="{{ $selectedItemId ?? '' }}">

                    {{-- Custom dropdown dengan infinite scroll --}}
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

                {{-- Print Dropdown --}}
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

        {{-- Summary Cards: Ringkasan cepat stok --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

            {{-- Card 1: Total Jumlah Barang --}}
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

            {{-- Card 2: Stok Akhir Total --}}
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

            {{-- Card 3: Nilai Stok Total --}}
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

        {{-- Detail Summary: Ringkasan pergerakan stok per periode --}}
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
                            {{ number_format($summary['total_beginning_stock'] + $summary['total_stock_in'] - $summary['total_stock_out'] - $summary['total_returns']) }}
                        </h5>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabel Laporan Stok: Detail data per barang --}}
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

            {{-- Pagination --}}
            <div class="px-6 py-4 border-t border-border-light">
                {{ $reportData->appends(request()->query())->links() }}
            </div>
        </div>

    </div>

    {{-- Konfigurasi route untuk JavaScript module --}}
    @push('scripts')
        <script>
            window.stockReportConfig = {
                indexRoute: '{{ route("stock-report.index") }}',
                itemsDropdownRoute: '{{ route("stock-report.items-dropdown") }}',
            };
        </script>
        @vite('resources/js/pages/inventory/stock-reports/index.js')
        @include('partials.shared.print-dropdown-script')
    @endpush
@endsection
