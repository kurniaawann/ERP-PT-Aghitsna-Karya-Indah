@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Laporan Rekap Pengeluaran')

@section('content')
    <div class="space-y-6">
        {{-- Filter Section --}}
        <div class="bg-white p-6 rounded-xl shadow">
            <form method="GET" action="{{ route('report.expense') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                    {{-- Filter Bulan --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Bulan</label>
                        <select id="month-select" name="month"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tahun</label>
                        <select id="year-select" name="year"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
                        <select id="category-select" name="category"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipe</label>
                        <select id="type-select" name="type"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            <option value="">Semua Tipe</option>
                            <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}>Pemasukan</option>
                            <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>Pengeluaran
                            </option>
                        </select>
                    </div>

                    {{-- Search --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cari</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Cari transaksi..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                    </div>

                    {{-- Button --}}
                    <div class="flex items-end">
                        <button type="submit"
                            class="w-full px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors duration-200 font-medium">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Total Pemasukan --}}
            <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Pemasukan</p>
                        <h3 class="text-2xl font-bold text-green-600 mt-2">
                            Rp {{ number_format($summary['total_income'], 0, ',', '.') }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">{{ $summary['income_count'] }} transaksi</p>
                    </div>
                    <div class="p-4 bg-green-100 rounded-full">
                        <i class="fas fa-arrow-down text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            {{-- Total Pengeluaran --}}
            <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Pengeluaran</p>
                        <h3 class="text-2xl font-bold text-red-600 mt-2">
                            Rp {{ number_format($summary['total_expense'], 0, ',', '.') }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">{{ $summary['expense_count'] }} transaksi</p>
                    </div>
                    <div class="p-4 bg-red-100 rounded-full">
                        <i class="fas fa-arrow-up text-red-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            {{-- Saldo --}}
            <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Saldo</p>
                        <h3
                            class="text-2xl font-bold {{ $summary['balance'] >= 0 ? 'text-blue-600' : 'text-red-600' }} mt-2">
                            Rp {{ number_format(abs($summary['balance']), 0, ',', '.') }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $summary['balance'] >= 0 ? 'Surplus' : 'Defisit' }}
                        </p>
                    </div>
                    <div class="p-4 {{ $summary['balance'] >= 0 ? 'bg-blue-100' : 'bg-red-100' }} rounded-full">
                        <i
                            class="fas fa-balance-scale {{ $summary['balance'] >= 0 ? 'text-blue-600' : 'text-red-600' }} text-2xl"></i>
                    </div>
                </div>
            </div>

            {{-- Total Transaksi --}}
            <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Transaksi</p>
                        <h3 class="text-2xl font-bold text-gray-900 mt-2">
                            {{ $summary['total_transactions'] }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">Semua transaksi</p>
                    </div>
                    <div class="p-4 bg-purple-100 rounded-full">
                        <i class="fas fa-receipt text-purple-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cash Flow Analysis --}}
        <div class="bg-white p-6 rounded-xl shadow">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Analisis Cash Flow</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <span class="text-gray-700 font-medium">Saldo Awal Periode</span>
                    <span class="text-lg font-semibold text-gray-900">
                        Rp {{ number_format($cashFlow['opening_balance'], 0, ',', '.') }}
                    </span>
                </div>
                <div class="flex items-center justify-between p-4 bg-green-50 rounded-lg">
                    <span class="text-gray-700 font-medium flex items-center">
                        <i class="fas fa-plus-circle text-green-600 mr-2"></i>
                        Pemasukan
                    </span>
                    <span class="text-lg font-semibold text-green-600">
                        Rp {{ number_format($cashFlow['total_income'], 0, ',', '.') }}
                    </span>
                </div>
                <div class="flex items-center justify-between p-4 bg-red-50 rounded-lg">
                    <span class="text-gray-700 font-medium flex items-center">
                        <i class="fas fa-minus-circle text-red-600 mr-2"></i>
                        Pengeluaran
                    </span>
                    <span class="text-lg font-semibold text-red-600">
                        Rp {{ number_format($cashFlow['total_expense'], 0, ',', '.') }}
                    </span>
                </div>
                <div class="h-px bg-gray-300 my-2"></div>
                <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg">
                    <span class="text-gray-700 font-medium flex items-center">
                        <i class="fas fa-equals text-blue-600 mr-2"></i>
                        Net Cash Flow
                    </span>
                    <span
                        class="text-lg font-semibold {{ $cashFlow['net_cash_flow'] >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                        Rp {{ number_format(abs($cashFlow['net_cash_flow']), 0, ',', '.') }}
                    </span>
                </div>
                <div class="flex items-center justify-between p-4 bg-indigo-50 rounded-lg border-2 border-indigo-200">
                    <span class="text-gray-800 font-bold flex items-center">
                        <i class="fas fa-wallet text-indigo-600 mr-2"></i>
                        Saldo Akhir Periode
                    </span>
                    <span class="text-xl font-bold text-indigo-600">
                        Rp {{ number_format($cashFlow['closing_balance'], 0, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Category Table --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">📊 Ringkasan Per Kategori</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Kategori</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Transaksi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pemasukan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pengeluaran</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($categoryDistribution as $category)
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $category['category_name'] }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $category['count'] }} transaksi
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold">
                                    @if ($category['category_type'] === 'EXPENSE')
                                        <span class="text-gray-400">-</span>
                                    @else
                                        <span class="text-green-600">Rp
                                            {{ number_format($category['income'], 0, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold">
                                    @if ($category['category_type'] === 'INCOME')
                                        <span class="text-gray-400">-</span>
                                    @else
                                        <span class="text-red-600">Rp
                                            {{ number_format($category['expense'], 0, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">
                                    Rp {{ number_format($category['total'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                    Tidak ada data kategori
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Detail Transactions Table --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Detail Transaksi</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No.
                                Invoice</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Kategori</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Deskripsi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pemasukan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pengeluaran</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($expenseRecaps as $expense)
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ \Carbon\Carbon::parse($expense->transaction_date)->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $expense->invoice_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        {{ $expense->category ? $expense->category->name : '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ $expense->description }}</div>
                                    @if ($expense->notes)
                                        <div class="text-xs text-gray-500 mt-1">{{ $expense->notes }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                                    @if ($expense->income_amount > 0)
                                        Rp {{ number_format($expense->income_amount, 0, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-red-600">
                                    @if ($expense->expense_amount > 0)
                                        Rp {{ number_format($expense->expense_amount, 0, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    Tidak ada data transaksi
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="bg-white p-4 rounded-xl shadow">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="text-sm text-gray-700 font-semibold">
                    Halaman {{ $expenseRecaps->currentPage() }} dari {{ $expenseRecaps->lastPage() }}
                </div>
                <div>
                    {{ $expenseRecaps->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
