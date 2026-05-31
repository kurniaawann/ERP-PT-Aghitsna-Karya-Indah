@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Laporan Rekap Pengeluaran')

@section('content')
    <div class="space-y-6">
        {{-- Filter Section --}}
        <div class="bg-surface-base p-6 rounded-xl shadow">
            <form method="GET" action="{{ route('report.expense') }}" class="space-y-4">
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

                    {{-- Filter Kategori --}}
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">Kategori</label>
                        <select id="category-select" name="category"
                            class="w-full px-4 py-2 border border-border-strong rounded-lg bg-surface-base text-text-input focus:ring-2 focus:ring-primary focus:border-transparent"
                            onchange="this.form.requestSubmit()">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ request('category') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter Tipe --}}
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">Tipe</label>
                        <select id="type-select" name="type"
                            class="w-full px-4 py-2 border border-border-strong rounded-lg bg-surface-base text-text-input focus:ring-2 focus:ring-primary focus:border-transparent"
                            onchange="this.form.requestSubmit()">
                            <option value="">Semua Tipe</option>
                            <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}>Pemasukan</option>
                            <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>Pengeluaran
                            </option>
                        </select>
                    </div>

                    {{-- Search --}}
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">Cari</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Cari transaksi..."
                            class="w-full px-4 py-2 border border-border-strong rounded-lg bg-surface-base text-text-input focus:ring-2 focus:ring-primary focus:border-transparent"
                            oninput="this.form.requestSubmit()">
                    </div>
                </div>
            </form>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Total Pemasukan --}}
            <div class="bg-surface-base p-6 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-text-secondary">Total Pemasukan</p>
                        <h3 class="text-2xl font-bold text-success mt-2">
                            Rp {{ number_format($summary['total_income'], 0, ',', '.') }}
                        </h3>
                        <p class="text-xs text-text-secondary mt-1">{{ $summary['income_count'] }} transaksi</p>
                    </div>
                    <div class="p-4 bg-success-light rounded-full">
                        <i class="fas fa-arrow-down text-success text-2xl"></i>
                    </div>
                </div>
            </div>

            {{-- Total Pengeluaran --}}
            <div class="bg-surface-base p-6 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-text-secondary">Total Pengeluaran</p>
                        <h3 class="text-2xl font-bold text-error mt-2">
                            Rp {{ number_format($summary['total_expense'], 0, ',', '.') }}
                        </h3>
                        <p class="text-xs text-text-secondary mt-1">{{ $summary['expense_count'] }} transaksi</p>
                    </div>
                    <div class="p-4 bg-error-light rounded-full">
                        <i class="fas fa-arrow-up text-error text-2xl"></i>
                    </div>
                </div>
            </div>

            {{-- Saldo --}}
            <div class="bg-surface-base p-6 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-text-secondary">Saldo</p>
                        <h3 class="text-2xl font-bold {{ $summary['balance'] >= 0 ? 'text-primary' : 'text-error' }} mt-2">
                            Rp {{ number_format(abs($summary['balance']), 0, ',', '.') }}
                        </h3>
                        <p class="text-xs text-text-secondary mt-1">
                            {{ $summary['balance'] >= 0 ? 'Surplus' : 'Defisit' }}
                        </p>
                    </div>
                    <div class="p-4 {{ $summary['balance'] >= 0 ? 'bg-primary-light' : 'bg-error-light' }} rounded-full">
                        <i
                            class="fas fa-balance-scale {{ $summary['balance'] >= 0 ? 'text-primary' : 'text-error' }} text-2xl"></i>
                    </div>
                </div>
            </div>

            {{-- Total Transaksi --}}
            <div class="bg-surface-base p-6 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-text-secondary">Total Transaksi</p>
                        <h3 class="text-2xl font-bold text-text-primary mt-2">
                            {{ $summary['total_transactions'] }}
                        </h3>
                        <p class="text-xs text-text-secondary mt-1">Semua transaksi</p>
                    </div>
                    <div class="p-4 bg-secondary-light rounded-full">
                        <i class="fas fa-receipt text-text-primary text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts Section --}}
        <div class="grid grid-cols-1 gap-6">
            {{-- Monthly Trend Chart --}}
            <div class="bg-surface-base p-6 rounded-xl shadow">
                <h3 class="text-lg font-semibold text-text-primary mb-4">📈 Trend Pemasukan & Pengeluaran Bulanan</h3>
                <div style="position: relative; height: 350px;">
                    <canvas id="monthlyTrendChart"></canvas>
                </div>
            </div>

            {{-- Category Expense Chart --}}
            <div class="bg-surface-base p-6 rounded-xl shadow">
                <h3 class="text-lg font-semibold text-text-primary mb-4">📊 Total Pengeluaran Per Kategori</h3>
                <div style="position: relative; height: 400px;">
                    <canvas id="categoryExpenseChart"></canvas>
                </div>
            </div>

            {{-- Income vs Expense Comparison --}}
            <div class="bg-surface-base p-6 rounded-xl shadow">
                <h3 class="text-lg font-semibold text-text-primary mb-4">💰 Perbandingan Pemasukan vs Pengeluaran</h3>
                <div style="position: relative; height: 300px;">
                    <canvas id="incomeVsExpenseChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Cash Flow Analysis --}}
        <div class="bg-surface-base p-6 rounded-xl shadow">
            <h3 class="text-lg font-semibold text-text-primary mb-6">Rincian Cash Flow</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-surface-secondary rounded-lg">
                    <span class="text-text-primary font-medium">Saldo Awal Periode</span>
                    <span class="text-lg font-semibold text-text-primary">
                        Rp {{ number_format($cashFlow['opening_balance'], 0, ',', '.') }}
                    </span>
                </div>
                <div class="flex items-center justify-between p-4 bg-success-light rounded-lg">
                    <span class="text-text-primary font-medium flex items-center">
                        <i class="fas fa-plus-circle text-success mr-2"></i>
                        Pemasukan
                    </span>
                    <span class="text-lg font-semibold text-success">
                        Rp {{ number_format($cashFlow['total_income'], 0, ',', '.') }}
                    </span>
                </div>
                <div class="flex items-center justify-between p-4 bg-error-light rounded-lg">
                    <span class="text-text-primary font-medium flex items-center">
                        <i class="fas fa-minus-circle text-error mr-2"></i>
                        Pengeluaran
                    </span>
                    <span class="text-lg font-semibold text-error">
                        Rp {{ number_format($cashFlow['total_expense'], 0, ',', '.') }}
                    </span>
                </div>
                <div class="h-px bg-border-light my-2"></div>
                <div class="flex items-center justify-between p-4 bg-primary-light rounded-lg">
                    <span class="text-text-primary font-medium flex items-center">
                        <i class="fas fa-equals text-primary mr-2"></i>
                        Net Cash Flow
                    </span>
                    <span
                        class="text-lg font-semibold {{ $cashFlow['net_cash_flow'] >= 0 ? 'text-primary' : 'text-error' }}">
                        Rp {{ number_format(abs($cashFlow['net_cash_flow']), 0, ',', '.') }}
                    </span>
                </div>
                <div
                    class="flex items-center justify-between p-4 bg-secondary-light rounded-lg border-2 border-border-strong">
                    <span class="text-text-primary font-bold flex items-center">
                        <i class="fas fa-wallet text-text-primary mr-2"></i>
                        Saldo Akhir Periode
                    </span>
                    <span class="text-xl font-bold text-text-primary">
                        Rp {{ number_format($cashFlow['closing_balance'], 0, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Category Table --}}
        <div class="bg-surface-base rounded-xl shadow overflow-hidden">
            <div class="p-6 border-b border-border-light">
                <h3 class="text-lg font-semibold text-text-primary">📊 Ringkasan Per Kategori</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-surface-secondary">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Kategori</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Transaksi</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Pemasukan</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Pengeluaran</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-base divide-y divide-border-light">
                        @forelse($categoryDistribution as $category)
                            <tr class="hover:bg-surface-secondary transition-colors duration-150">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-text-primary">{{ $category['category_name'] }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-text-primary">
                                    {{ $category['count'] }} transaksi
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold">
                                    @if ($category['category_type'] === 'EXPENSE')
                                        <span class="text-text-tertiary">-</span>
                                    @else
                                        <span class="text-success">Rp
                                            {{ number_format($category['income'], 0, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold">
                                    @if ($category['category_type'] === 'INCOME')
                                        <span class="text-text-tertiary">-</span>
                                    @else
                                        <span class="text-error">Rp
                                            {{ number_format($category['expense'], 0, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-text-primary font-semibold">
                                    Rp {{ number_format($category['total'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-text-secondary">
                                    Tidak ada data kategori
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
                <h3 class="text-lg font-semibold text-text-primary">Detail Transaksi</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-surface-secondary">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Tanggal</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                No.
                                Invoice</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Kategori</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Deskripsi</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Pemasukan</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                                Pengeluaran</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-base divide-y divide-border-light">
                        @forelse($expenseRecaps as $expense)
                            <tr class="hover:bg-surface-secondary transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-text-primary">
                                    {{ \Carbon\Carbon::parse($expense->transaction_date)->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-text-primary">
                                    {{ $expense->invoice_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-surface-secondary text-text-primary">
                                        {{ $expense->category ? $expense->category->name : '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-text-primary">{{ $expense->description }}</div>
                                    @if ($expense->notes)
                                        <div class="text-xs text-text-secondary mt-1">{{ $expense->notes }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-success">
                                    @if ($expense->income_amount > 0)
                                        Rp {{ number_format($expense->income_amount, 0, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-error">
                                    @if ($expense->expense_amount > 0)
                                        Rp {{ number_format($expense->expense_amount, 0, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-text-secondary">
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
                    Halaman {{ $expenseRecaps->currentPage() }} dari {{ $expenseRecaps->lastPage() }}
                </div>
                <div>
                    {{ $expenseRecaps->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Script untuk Chart --}}
    @push('scripts')
        <script>
            // Data dari server
            const monthlyTrendData = @json($monthlyTrend);
            const categoryDistributionData = @json($categoryDistribution);

            // Monthly Trend Chart (Income vs Expense)
            const monthlyTrendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
            new Chart(monthlyTrendCtx, {
                type: 'line',
                data: {
                    labels: monthlyTrendData.map(item => item.month_name),
                    datasets: [{
                            label: 'Pemasukan (Rp)',
                            data: monthlyTrendData.map(item => item.income),
                            borderColor: '#22c55e',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#22c55e',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                        },
                        {
                            label: 'Pengeluaran (Rp)',
                            data: monthlyTrendData.map(item => item.expense),
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#ef4444',
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

            // Warna yang lebih terang dan berbeda untuk setiap kategori
            const categoryColors = {
                'UANG MASUK PENJUALAN': '#22c55e',
                'UPAH KERJA / KASBON': '#ff6b6b',
                'ATK / OPERASIONAL & ALAT': '#3b82f6',
                'PENGELUARAN MATERIAL': '#8b5cf6',
                'PENGELUARAN PEMBELIAN COIL': '#f59e0b',
                'TRANSPORT': '#14b8a6',
                'TOKEN LISTRIK': '#06b6d4',
                'LAIN - LAIN': '#ec4899'
            };

            // Category Expense Chart (Bar Chart - Horizontal)
            const categoryNames = categoryDistributionData.map(item => item.category_name);
            const categoryExpenses = categoryDistributionData.map(item => item.expense);
            const categoryChartColors = categoryNames.map(name => categoryColors[name] || '#9ca3af');

            const categoryExpenseCtx = document.getElementById('categoryExpenseChart').getContext('2d');
            new Chart(categoryExpenseCtx, {
                type: 'bar',
                data: {
                    labels: categoryNames,
                    datasets: [{
                        label: 'Total Pengeluaran (Rp)',
                        data: categoryExpenses,
                        backgroundColor: categoryChartColors,
                        borderRadius: 6,
                        borderSkipped: false,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            labels: {
                                padding: 15,
                                font: {
                                    size: 12
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
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

            // Income vs Expense Comparison (Doughnut Chart)
            const incomeVsExpenseCtx = document.getElementById('incomeVsExpenseChart').getContext('2d');
            new Chart(incomeVsExpenseCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Total Pemasukan', 'Total Pengeluaran'],
                    datasets: [{
                        data: [
                            @json($summary['total_income']),
                            @json($summary['total_expense'])
                        ],
                        backgroundColor: ['#22c55e', '#ef4444'],
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
