{{-- =====================================================================
     Halaman: Laporan Rekap Penjualan (Sales Report)
     Tujuan: Dashboard laporan penjualan untuk General Manager dengan filter,
             summary cards, chart (trend bulanan & distribusi status), kartu
             status pembayaran, top 5 proyek terbaik, dan detail transaksi.
     Data dari SalesReportController@index (logic di SalesReportService):
     - $salesRecaps        : paginator detail transaksi penjualan
     - $summary            : total_selling, total_capital, total_profit,
                             profit_margin, avg_transaction, total_transactions,
                             paid_count, paid_amount, unpaid_count, unpaid_amount
     - $monthlyTrend       : trend penjualan bulanan untuk chart
     - $statusDistribution : distribusi status pembayaran (Lunas/Belum Lunas)
     - $topProjects        : top 5 proyek berdasarkan profit
     Filter (GET): month, year, status (Lunas/Belum Lunas), search
     Komponen yang di-include:
     - layouts.app
     - x-report.sales-reports.summary-card (4 kartu ringkasan)
     - x-report.sales-reports.status-badge (badge status per baris)
     - x-buttons.print-dropdown (export PDF/Excel)
     JS yang di-load:
     - resources/js/pages/report/sales-reports/index.js (via @vite + @push)
     - Data chart di-pass via window globals (monthlyTrendData,
       statusDistributionData)
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Laporan Rekap Penjualan')

@section('content')
    <div class="space-y-6">

        {{-- ==================== Filter Section ==================== --}}
        <div class="bg-surface-base p-6 rounded-xl shadow">
            <form method="GET" action="{{ route('report.sales') }}" class="space-y-4">
                <div class="grid grid-cols-1 min-[1272px]:grid-cols-2 min-[1520px]:grid-cols-5 gap-4">
                    {{-- Filter Bulan --}}
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">Bulan</label>
                        <select id="month-select" name="month"
                            class="w-full px-4 py-2 border border-border-strong rounded-lg bg-surface-base text-text-input focus:ring-2 focus:ring-primary focus:border-transparent"
                            onchange="this.form.requestSubmit()">
                            <option value="">Semua Bulan</option>
                            @for ($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>
                                    {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    {{-- Filter Tahun --}}
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">Tahun</label>
                        <select id="year-select" name="year"
                            class="w-full px-4 py-2 border border-border-strong rounded-lg bg-surface-base text-text-input focus:ring-2 focus:ring-primary focus:border-transparent"
                            onchange="this.form.requestSubmit()">
                            @for ($i = date('Y'); $i >= date('Y') - 5; $i--)
                                <option value="{{ $i }}"
                                    {{ request('year', date('Y')) == $i ? 'selected' : '' }}>
                                    {{ $i }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    {{-- Filter Status --}}
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">Status</label>
                        <select id="status-select" name="status"
                            class="w-full px-4 py-2 border border-border-strong rounded-lg bg-surface-base text-text-input focus:ring-2 focus:ring-primary focus:border-transparent"
                            onchange="this.form.requestSubmit()">
                            <option value="">Semua Status</option>
                            <option value="Lunas" {{ request('status') == 'Lunas' ? 'selected' : '' }}>Lunas</option>
                            <option value="Belum Lunas" {{ request('status') == 'Belum Lunas' ? 'selected' : '' }}>Belum
                                Lunas</option>
                        </select>
                    </div>

                    {{-- Search --}}
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">Cari</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari proyek..."
                            class="w-full px-4 py-2 border border-border-strong rounded-lg bg-surface-base text-text-input focus:ring-2 focus:ring-primary focus:border-transparent"
                            oninput="this.form.requestSubmit()">
                    </div>

                    {{-- Print Dropdown --}}
                    <div class="flex items-end">
                        <x-buttons.print-dropdown
                            :pdfRoute="route('report.sales.export.pdf')"
                            :excelRoute="route('report.sales.export.excel')"
                            :queryParams="request()->except([])"
                            size="sm"
                        />
                    </div>
                </div>
            </form>
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
                                ID
                            </th>
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

    {{-- ==================== Chart Data & JavaScript ==================== --}}
    @push('scripts')
        <script>
            window.monthlyTrendData = @json($monthlyTrend);
            window.statusDistributionData = @json($statusDistribution);
        </script>
        @vite(['resources/js/pages/report/sales-reports/index.js'])
    @endpush
@endsection
