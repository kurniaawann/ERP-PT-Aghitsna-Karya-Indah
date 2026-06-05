@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Laporan Rekap Penjualan')

@section('content')
    <div class="space-y-6">

        {{-- Filter Section --}}
        <div class="bg-surface-base p-6 rounded-xl shadow">
            <form method="GET" action="{{ route('report.sales') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
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
                </div>
            </form>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Total Penjualan --}}
            <div class="bg-surface-base p-6 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-text-secondary">Total Penjualan</p>
                        <h3 class="text-2xl font-bold text-text-primary mt-2">
                            Rp {{ number_format($summary['total_selling'], 0, ',', '.') }}
                        </h3>
                        <p class="text-xs text-text-secondary mt-1">{{ $summary['total_transactions'] }} transaksi</p>
                    </div>
                    <div class="p-4 bg-primary-light rounded-full">
                        <i class="fas fa-chart-line text-primary text-2xl"></i>
                    </div>
                </div>
            </div>

            {{-- Total Modal --}}
            <div class="bg-surface-base p-6 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-text-secondary">Total Modal</p>
                        <h3 class="text-2xl font-bold text-text-primary mt-2">
                            Rp {{ number_format($summary['total_capital'], 0, ',', '.') }}
                        </h3>
                        <p class="text-xs text-text-secondary mt-1">HPP</p>
                    </div>
                    <div class="p-4 bg-warning-light rounded-full">
                        <i class="fas fa-coins text-warning text-2xl"></i>
                    </div>
                </div>
            </div>

            {{-- Total Profit --}}
            <div class="bg-surface-base p-6 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-text-secondary">Total Profit</p>
                        <h3 class="text-2xl font-bold text-success mt-2">
                            Rp {{ number_format($summary['total_profit'], 0, ',', '.') }}
                        </h3>
                        <p class="text-xs text-text-secondary mt-1">Margin: {{ $summary['profit_margin'] }}%</p>
                    </div>
                    <div class="p-4 bg-success-light rounded-full">
                        <i class="fas fa-hand-holding-usd text-success text-2xl"></i>
                    </div>
                </div>
            </div>

            {{-- Rata-rata Transaksi --}}
            <div class="bg-surface-base p-6 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-text-secondary">Rata-rata/Transaksi</p>
                        <h3 class="text-2xl font-bold text-text-primary mt-2">
                            Rp {{ number_format($summary['avg_transaction'], 0, ',', '.') }}
                        </h3>
                        <p class="text-xs text-text-secondary mt-1">Per transaksi</p>
                    </div>
                    <div class="p-4 bg-surface-secondary rounded-full">
                        <i class="fas fa-calculator text-text-primary text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts Section --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Monthly Trend Chart --}}
            <div class="bg-surface-base p-6 rounded-xl shadow">
                <h3 class="text-lg font-semibold text-text-primary mb-4">📈 Trend Penjualan Bulanan</h3>
                <div style="position: relative; height: 300px;">
                    <canvas id="monthlySalesChart"></canvas>
                </div>
            </div>

            {{-- Status Distribution Chart --}}
            <div class="bg-surface-base p-6 rounded-xl shadow">
                <h3 class="text-lg font-semibold text-text-primary mb-4">💳 Status Pembayaran</h3>
                <div style="position: relative; height: 300px;">
                    <canvas id="statusDistributionChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Status Pembayaran Cards --}}
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

        {{-- Top Projects Table --}}
        <div class="bg-surface-base rounded-xl shadow overflow-hidden">
            <div class="p-6 border-b border-border-light">
                <h3 class="text-lg font-semibold text-text-primary">🏆 Top 5 Proyek Terbaik (Berdasarkan Profit)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-surface-secondary">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Ranking</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Tanggal</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Nama Proyek</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Penjualan</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Modal</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Profit</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-base divide-y divide-border-light">
                        @forelse($topProjects as $index => $project)
                            <tr class="hover:bg-surface-secondary transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        @if ($index == 0)
                                            <span class="text-2xl">🥇</span>
                                        @elseif($index == 1)
                                            <span class="text-2xl">🥈</span>
                                        @elseif($index == 2)
                                            <span class="text-2xl">🥉</span>
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
                                    <div class="text-xs text-text-secondary">{{ $project->id_sales_recap }}</div>
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
                                    @if ($project->status == 'Lunas')
                                        <span
                                            class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-success-light text-success">
                                            Lunas
                                        </span>
                                    @else
                                        <span
                                            class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-warning-light text-warning">
                                            Belum Lunas
                                        </span>
                                    @endif
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

        {{-- Detail Transactions Table --}}
        <div class="bg-surface-base rounded-xl shadow overflow-hidden">
            <div class="p-6 border-b border-border-light">
                <h3 class="text-lg font-semibold text-text-primary">Detail Transaksi Penjualan</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-surface-secondary">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                ID
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Tanggal</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Nama Proyek</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Modal</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Penjualan</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Profit</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-base divide-y divide-border-light">
                        @forelse($salesRecaps as $recap)
                            <tr class="hover:bg-surface-secondary transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-text-primary">
                                    {{ $recap->id_sales_recap }}
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
                                    @if ($recap->status == 'Lunas')
                                        <span
                                            class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-success-light text-success">
                                            Lunas
                                        </span>
                                    @else
                                        <span
                                            class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-warning-light text-warning">
                                            Belum Lunas
                                        </span>
                                    @endif
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

        {{-- Pagination --}}
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

    {{-- Script untuk Chart --}}
    @push('scripts')
        <script>
            // Data dari server
            const monthlyTrendData = @json($monthlyTrend);
            const statusDistributionData = @json($statusDistribution);

            // Monthly Sales Chart
            const monthlySalesCtx = document.getElementById('monthlySalesChart').getContext('2d');
            new Chart(monthlySalesCtx, {
                type: 'line',
                data: {
                    labels: monthlyTrendData.map(item => item.month_name),
                    datasets: [{
                            label: 'Penjualan (Rp)',
                            data: monthlyTrendData.map(item => item.selling),
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#3b82f6',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                        },
                        {
                            label: 'Profit (Rp)',
                            data: monthlyTrendData.map(item => item.profit),
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#10b981',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                                font: {
                                    size: 12
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + (value / 1000000).toFixed(1) + 'jt';
                                }
                            }
                        }
                    }
                }
            });

            // Status Distribution Chart
            const statusLabels = statusDistributionData.map(item => item.status);
            const statusCounts = statusDistributionData.map(item => item.count);

            const statusDistributionCtx = document.getElementById('statusDistributionChart').getContext('2d');
            new Chart(statusDistributionCtx, {
                type: 'doughnut',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        data: statusCounts,
                        backgroundColor: [
                            '#10b981',
                            '#ef4444'
                        ],
                        borderColor: '#fff',
                        borderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });
        </script>
    @endpush
@endsection
