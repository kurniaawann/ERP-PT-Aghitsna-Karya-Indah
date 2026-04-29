@extends('layouts.app')

@section('title', 'Laporan Stok Barang')

@section('content')
    <div class="px-4 py-4">
        <!-- Header -->
        <div class="mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 mb-1">📊 Laporan Stok Barang</h2>
                <p class="text-gray-500 mb-0">Pantau pergerakan dan nilai persediaan barang Anda</p>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <form method="GET" action="{{ route('stock-report.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="start_date" class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Mulai</label>
                    <input type="date"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        id="start_date" name="start_date" value="{{ $startDate }}" required>
                </div>

                <div>
                    <label for="end_date" class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Akhir</label>
                    <input type="date"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        id="end_date" name="end_date" value="{{ $endDate }}" required>
                </div>

                <div>
                    <label for="item_id" class="block text-sm font-semibold text-gray-700 mb-2">Pilih Barang
                        (Opsional)</label>
                    <select
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        id="item_id" name="item_id">
                        <option value="">- Semua Barang -</option>
                        @foreach ($items as $item)
                            <option value="{{ $item->id_item }}" {{ $selectedItemId === $item->id_item ? 'selected' : '' }}>
                                {{ $item->id_item }} - {{ $item->name_item }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg transition-colors duration-200">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Summary Card -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <!-- Card 1: Total Jumlah Barang -->
            <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Total Jumlah Barang</p>
                        <h5 class="text-2xl font-bold text-gray-800">{{ number_format($summary['total_items']) }} item</h5>
                    </div>
                    <i class="fas fa-box-open text-blue-500 text-3xl opacity-20"></i>
                </div>
            </div>

            <!-- Card 2: Stok Akhir Total -->
            <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Stok Akhir Total</p>
                        <h5 class="text-2xl font-bold text-gray-800">{{ number_format($summary['total_ending_stock']) }}
                            unit</h5>
                    </div>
                    <i class="fas fa-check-circle text-green-500 text-3xl opacity-20"></i>
                </div>
            </div>

            <!-- Card 3: Nilai Stok Total -->
            <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-yellow-500">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Nilai Stok Total</p>
                        <h5 class="text-xl font-bold text-gray-800 truncate">Rp
                            {{ number_format($summary['total_stock_value']) }}</h5>
                    </div>
                    <i class="fas fa-coins text-yellow-500 text-3xl opacity-20"></i>
                </div>
            </div>
        </div>

        <!-- Detail Summary -->
        <div class="bg-white rounded-lg shadow-sm mb-6 overflow-hidden">
            <div class="bg-gray-100 px-6 py-4 border-b border-gray-200">
                <h6 class="text-sm font-semibold text-gray-800 mb-0">📈 Ringkasan Pergerakan Stok Periode
                    {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} -
                    {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</h6>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-center">
                    <div>
                        <h6 class="text-gray-500 text-sm mb-2">Stok Awal</h6>
                        <h5 class="text-xl font-bold text-blue-600">{{ number_format($summary['total_beginning_stock']) }}
                        </h5>
                    </div>
                    <div>
                        <h6 class="text-gray-500 text-sm mb-2">Stok Masuk</h6>
                        <h5 class="text-xl font-bold text-green-600">{{ number_format($summary['total_stock_in']) }}</h5>
                    </div>
                    <div>
                        <h6 class="text-gray-500 text-sm mb-2">Stok Keluar</h6>
                        <h5 class="text-xl font-bold text-red-600">{{ number_format($summary['total_stock_out']) }}</h5>
                    </div>
                    <div>
                        <h6 class="text-gray-500 text-sm mb-2">Retur Barang</h6>
                        <h5 class="text-xl font-bold text-yellow-600">{{ number_format($summary['total_returns']) }}</h5>
                    </div>
                    <div>
                        <h6 class="text-gray-500 text-sm mb-2">Stok Akhir</h6>
                        <h5 class="text-xl font-bold text-purple-600">
                            {{ number_format($summary['total_beginning_stock'] + $summary['total_stock_in'] - $summary['total_stock_out'] - $summary['total_returns']) }}
                        </h5>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Table -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="bg-gray-100 px-6 py-4 border-b border-gray-200">
                <h6 class="text-sm font-semibold text-gray-800 mb-0">📋 Detail Laporan Stok</h6>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-700 w-12">No</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">ID Barang</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Nama Barang</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Stok Awal</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Masuk</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Keluar</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Retur</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Stok Akhir</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Harga Satuan</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Nilai Stok</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($reportData as $index => $item)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-center text-sm text-gray-700">{{ $index + 1 }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span
                                        class="inline-block px-2 py-1 bg-gray-300 text-gray-800 rounded text-xs font-semibold">{{ $item['id_item'] }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-800">{{ $item['name_item'] }}</td>
                                <td class="px-6 py-4 text-sm text-right">
                                    <span
                                        class="inline-block px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-semibold">{{ number_format($item['beginning_stock']) }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-right">
                                    <span
                                        class="inline-block px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-semibold">{{ number_format($item['stock_in']) }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-right">
                                    <span
                                        class="inline-block px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-semibold">{{ number_format($item['stock_out']) }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-right">
                                    <span
                                        class="inline-block px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-semibold">{{ number_format($item['returns']) }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-right">
                                    <span
                                        class="inline-block px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs font-semibold">{{ number_format($item['ending_stock']) }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-right text-gray-700">Rp
                                    {{ number_format($item['capital_price']) }}</td>
                                <td class="px-6 py-4 text-sm text-right font-bold text-gray-900">Rp
                                    {{ number_format($item['stock_value']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-12 text-center">
                                    <p class="text-gray-500 mb-0">
                                        <i class="fas fa-inbox mr-2"></i>Tidak ada data stok untuk periode ini
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
