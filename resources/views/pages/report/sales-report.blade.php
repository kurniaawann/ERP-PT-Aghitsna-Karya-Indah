@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Laporan Rekap Penjualan')

@section('content')
    <div class="space-y-6">

        {{-- Filter Section --}}
        <div class="bg-white p-6 rounded-xl shadow">
            <form method="GET" action="{{ route('report.sales') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    {{-- Filter Bulan --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Bulan</label>
                        <select name="month"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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
                        <select name="year"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Semua Status</option>
                            <option value="Lunas" {{ request('status') == 'Lunas' ? 'selected' : '' }}>Lunas</option>
                            <option value="Belum Lunas" {{ request('status') == 'Belum Lunas' ? 'selected' : '' }}>Belum
                                Lunas</option>
                        </select>
                    </div>

                    {{-- Search --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cari</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari proyek..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    {{-- Button --}}
                    <div class="flex items-end">
                        <button type="submit"
                            class="w-full px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 font-medium">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Total Penjualan --}}
            <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Penjualan</p>
                        <h3 class="text-2xl font-bold text-gray-900 mt-2">
                            Rp {{ number_format($summary['total_selling'], 0, ',', '.') }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">{{ $summary['total_transactions'] }} transaksi</p>
                    </div>
                    <div class="p-4 bg-blue-100 rounded-full">
                        <i class="fas fa-chart-line text-blue-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            {{-- Total Modal --}}
            <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Modal</p>
                        <h3 class="text-2xl font-bold text-gray-900 mt-2">
                            Rp {{ number_format($summary['total_capital'], 0, ',', '.') }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">HPP</p>
                    </div>
                    <div class="p-4 bg-orange-100 rounded-full">
                        <i class="fas fa-coins text-orange-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            {{-- Total Profit --}}
            <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Profit</p>
                        <h3 class="text-2xl font-bold text-green-600 mt-2">
                            Rp {{ number_format($summary['total_profit'], 0, ',', '.') }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">Margin: {{ $summary['profit_margin'] }}%</p>
                    </div>
                    <div class="p-4 bg-green-100 rounded-full">
                        <i class="fas fa-hand-holding-usd text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            {{-- Rata-rata Transaksi --}}
            <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Rata-rata/Transaksi</p>
                        <h3 class="text-2xl font-bold text-gray-900 mt-2">
                            Rp {{ number_format($summary['avg_transaction'], 0, ',', '.') }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">Per transaksi</p>
                    </div>
                    <div class="p-4 bg-purple-100 rounded-full">
                        <i class="fas fa-calculator text-purple-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Status Pembayaran Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-xl shadow">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span>
                    Transaksi Lunas
                </h3>
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Jumlah Transaksi:</span>
                        <span class="font-semibold text-gray-900">{{ $summary['paid_count'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Total Nilai:</span>
                        <span class="font-semibold text-green-600">Rp
                            {{ number_format($summary['paid_amount'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <span class="w-3 h-3 bg-red-500 rounded-full mr-2"></span>
                    Transaksi Belum Lunas
                </h3>
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Jumlah Transaksi:</span>
                        <span class="font-semibold text-gray-900">{{ $summary['unpaid_count'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Total Piutang:</span>
                        <span class="font-semibold text-red-600">Rp
                            {{ number_format($summary['unpaid_amount'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Top Projects Table --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">🏆 Top 5 Proyek Terbaik (Berdasarkan Profit)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ranking</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nama Proyek</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Penjualan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Modal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Profit</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($topProjects as $index => $project)
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        @if ($index == 0)
                                            <span class="text-2xl">🥇</span>
                                        @elseif($index == 1)
                                            <span class="text-2xl">🥈</span>
                                        @elseif($index == 2)
                                            <span class="text-2xl">🥉</span>
                                        @else
                                            <span class="text-gray-500 font-semibold">{{ $index + 1 }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ \Carbon\Carbon::parse($project->date)->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $project->name_proyek }}</div>
                                    <div class="text-xs text-gray-500">{{ $project->id_sales_recap }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    Rp {{ number_format($project->total_selling, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    Rp {{ number_format($project->total_capital, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                                    Rp {{ number_format($project->total_profit, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($project->status == 'Lunas')
                                        <span
                                            class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Lunas
                                        </span>
                                    @else
                                        <span
                                            class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            Belum Lunas
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                    Tidak ada data proyek
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
                <h3 class="text-lg font-semibold text-gray-900">Detail Transaksi Penjualan</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nama Proyek</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Modal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Penjualan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Profit</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($salesRecaps as $recap)
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $recap->id_sales_recap }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ \Carbon\Carbon::parse($recap->date)->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $recap->name_proyek }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    Rp {{ number_format($recap->total_capital, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    Rp {{ number_format($recap->total_selling, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                                    Rp {{ number_format($recap->total_profit, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($recap->status == 'Lunas')
                                        <span
                                            class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Lunas
                                        </span>
                                    @else
                                        <span
                                            class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            Belum Lunas
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
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
                    Halaman {{ $salesRecaps->currentPage() }} dari {{ $salesRecaps->lastPage() }}
                </div>
                <div>
                    {{ $salesRecaps->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
