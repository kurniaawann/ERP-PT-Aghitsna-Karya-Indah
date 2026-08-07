{{-- =====================================================================
     Partial: Laporan Penjualan (dalam Laporan Akhir)
     Logika/data sama dengan halaman Laporan Penjualan lama (SalesReportController),
     hanya form disubmit ke route report.final dengan tab=sales agar tetap
     berada di halaman Laporan Akhir. Export tetap ke endpoint terpisah
     (report.sales.export.pdf / report.sales.export.excel).
     ===================================================================== --}}
<div class="space-y-6">

    {{-- ==================== Filter Section ==================== --}}
    <div class="bg-surface-base p-6 rounded-xl shadow">
        <div class="flex flex-col min-[1520px]:flex-row items-stretch min-[1520px]:items-center gap-3">

            {{-- Form Filter (kiri) --}}
            <form method="GET" action="{{ route('report.final') }}"
                class="flex flex-col min-[1520px]:flex-row items-stretch min-[1520px]:items-center gap-3 flex-1">

                {{-- Hidden: pertahankan tab aktif --}}
                <input type="hidden" name="tab" value="sales">

                {{-- Filter Bulan --}}
                <x-filters.month-filter :value="request('month')" responsive="custom" />

                {{-- Filter Tahun --}}
                <x-filters.year-filter :value="request('year')" responsive="custom" />

                {{-- Filter Status --}}
                <x-filters.select-filter name="status" :value="request('status')"
                    :options="collect([
                        (object) ['id' => 'Lunas', 'name' => 'Lunas'],
                        (object) ['id' => 'Belum Lunas', 'name' => 'Belum Lunas'],
                    ])"
                    placeholder="Semua Status" :autoSubmit="true" responsive="custom" />

                {{-- Search --}}
                <x-filters.search-input :value="request('search')" placeholder="Cari proyek..." responsive="custom" />
            </form>

            {{-- Print Dropdown (kanan): export terpisah hanya untuk laporan ini --}}
            <div class="w-full min-[1520px]:w-auto">
                <x-buttons.print-dropdown
                    :pdfRoute="route('report.sales.export.pdf')"
                    :excelRoute="route('report.sales.export.excel')"
                    :queryParams="[
                        'month' => request('month'),
                        'year' => request('year'),
                        'status' => request('status'),
                        'search' => request('search'),
                    ]"
                    size="sm" responsive="custom" />
            </div>
        </div>
    </div>

    {{-- ==================== Summary Cards ==================== --}}
    <div class="grid grid-cols-1 min-[1272px]:grid-cols-2 min-[1520px]:grid-cols-4 gap-6">
        <x-report.sales-reports.summary-card
            title="Total Penjualan"
            value="Rp {{ number_format($summary['total_selling'], 0, ',', '.') }}"
            subtitle="{{ $summary['total_transactions'] }} transaksi"
            icon="fa-chart-line"
            color="primary"
        />

        <x-report.sales-reports.summary-card
            title="Total Modal"
            value="Rp {{ number_format($summary['total_capital'], 0, ',', '.') }}"
            subtitle="HPP"
            icon="fa-coins"
            color="warning"
        />

        <x-report.sales-reports.summary-card
            title="Total Profit"
            value="Rp {{ number_format($summary['total_profit'], 0, ',', '.') }}"
            subtitle="Margin: {{ $summary['profit_margin'] }}%"
            icon="fa-hand-holding-usd"
            color="success"
        />

        <x-report.sales-reports.summary-card
            title="Rata-rata/Transaksi"
            value="Rp {{ number_format($summary['avg_transaction'], 0, ',', '.') }}"
            subtitle="Per transaksi"
            icon="fa-calculator"
            color="default"
        />
    </div>

    {{-- ==================== Charts Section ==================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Monthly Trend Chart --}}
        <div class="bg-surface-base p-6 rounded-xl shadow">
            <h3 class="text-lg font-semibold text-text-primary mb-4">Trend Penjualan Bulanan</h3>
            <div style="position: relative; height: 300px;">
                <canvas id="monthlySalesChart"></canvas>
            </div>
        </div>

        {{-- Status Distribution Chart --}}
        <div class="bg-surface-base p-6 rounded-xl shadow">
            <h3 class="text-lg font-semibold text-text-primary mb-4">Status Pembayaran</h3>
            <div style="position: relative; height: 300px;">
                <canvas id="statusDistributionChart"></canvas>
            </div>
        </div>
    </div>

    {{-- ==================== Status Pembayaran Cards ==================== --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-surface-base p-6 rounded-xl shadow">
            <h3 class="text-lg font-semibold text-text-primary mb-4 flex items-center">
                <span class="w-3 h-3 bg-success rounded-full mr-2"></span>
                Transaksi Lunas
            </h3>
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-text-secondary">Jumlah Transaksi:</span>
                    <span class="font-semibold text-text-primary">{{ $summary['paid_count'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-text-secondary">Total Nilai:</span>
                    <span class="font-semibold text-success">Rp
                        {{ number_format($summary['paid_amount'], 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="bg-surface-base p-6 rounded-xl shadow">
            <h3 class="text-lg font-semibold text-text-primary mb-4 flex items-center">
                <span class="w-3 h-3 bg-error rounded-full mr-2"></span>
                Transaksi Belum Lunas
            </h3>
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-text-secondary">Jumlah Transaksi:</span>
                    <span class="font-semibold text-text-primary">{{ $summary['unpaid_count'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-text-secondary">Total Piutang:</span>
                    <span class="font-semibold text-error">Rp
                        {{ number_format($summary['unpaid_amount'], 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== Top Projects Table ==================== --}}
    <div class="bg-surface-base rounded-xl shadow overflow-hidden">
        <div class="p-6 border-b border-border-light">
            <h3 class="text-lg font-semibold text-text-primary">Top 5 Proyek Terbaik (Berdasarkan Profit)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-surface-secondary">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                            Ranking</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                            Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                            Nama Proyek</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                            Penjualan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                            Modal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                            Profit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                            Status</th>
                    </tr>
                </thead>
                <tbody class="bg-surface-base divide-y divide-border-light">
                    @forelse($topProjects as $index => $project)
                        <tr class="hover:bg-surface-secondary transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    @if ($index == 0)
                                        <span class="text-2xl">&#x1F947;</span>
                                    @elseif($index == 1)
                                        <span class="text-2xl">&#x1F948;</span>
                                    @elseif($index == 2)
                                        <span class="text-2xl">&#x1F949;</span>
                                    @else
                                        <span class="text-text-secondary font-semibold">{{ $index + 1 }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-primary">
                                {{ \Carbon\Carbon::parse($project->date)->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-text-primary">{{ $project->name_proyek }}</div>
                                <div class="text-xs text-text-secondary">{{ $project->no_faktur }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-primary">
                                Rp {{ number_format($project->total_selling, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-primary">
                                Rp {{ number_format($project->total_capital, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-success">
                                Rp {{ number_format($project->total_profit, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-report.sales-reports.status-badge :status="$project->status" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-text-secondary">
                                Tidak ada data proyek
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ==================== Detail Transactions Table ==================== --}}
    <div class="bg-surface-base rounded-xl shadow overflow-hidden">
        <div class="p-6 border-b border-border-light">
            <h3 class="text-lg font-semibold text-text-primary">Detail Transaksi Penjualan</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-surface-secondary">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                            ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                            Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                            Nama Proyek</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                            Modal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                            Penjualan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                            Profit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                            Status</th>
                    </tr>
                </thead>
                <tbody class="bg-surface-base divide-y divide-border-light">
                    @forelse($salesRecaps as $recap)
                        <tr class="hover:bg-surface-secondary transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-text-primary">
                                {{ $recap->no_faktur }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-primary">
                                {{ \Carbon\Carbon::parse($recap->date)->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-text-primary">{{ $recap->name_proyek }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-primary">
                                Rp {{ number_format($recap->total_capital, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-primary">
                                Rp {{ number_format($recap->total_selling, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-success">
                                Rp {{ number_format($recap->total_profit, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-report.sales-reports.status-badge :status="$recap->status" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-text-secondary">
                                Tidak ada data transaksi
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ==================== Pagination ==================== --}}
    <div class="bg-surface-base p-4 rounded-xl shadow">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-sm text-text-primary font-semibold">
                Halaman {{ $salesRecaps->currentPage() }} dari {{ $salesRecaps->lastPage() }}
            </div>
            <div>
                {{ $salesRecaps->links() }}
            </div>
        </div>
    </div>
</div>

{{-- Chart Data & JavaScript --}}
@push('scripts')
    <script>
        window.monthlyTrendData = @json($monthlyTrend);
        window.statusDistributionData = @json($statusDistribution);
    </script>
    @vite(['resources/js/pages/report/sales-reports/index.js'])
@endpush
