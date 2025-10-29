@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Laporan Penjualan')

@section('content')
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-gray-700 mb-4">Laporan Penjualan</h1>

        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            <!-- Form Pencarian -->
            <form method="GET" action="{{ route('sales-report.index') }}" class="w-full md:w-auto md:max-w-md md:flex-1">
                <label for="search-input" class="sr-only">Cari Laporan</label>

                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 20 20" aria-hidden="true">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                            </svg>
                        </span>

                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari proyek..."
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-3 pl-10 text-sm text-gray-900 
                       focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light" />
                    </div>

                    <button type="submit"
                        class="rounded-lg bg-btn-search hover:bg-btn-search-hover px-4 md:px-6 py-3.5 text-sm font-medium text-white 
                   focus:outline-none focus:ring-4 focus:ring-primary-light whitespace-nowrap transition-colors duration-200">
                        Cari
                    </button>
                </div>
            </form>

            <!-- Aksi di Kanan -->
            <div class="flex items-center gap-3 mt-2 sm:mt-0 flex-col sm:flex-row w-full sm:w-auto">
                <div class="flex gap-2 w-full sm:w-auto">
                    <!-- Tombol Hapus -->
                    <button type="button" onclick="openModal('deleteModal')"
                        class="flex items-center justify-center gap-2 bg-btn-delete hover:bg-btn-delete-hover text-white px-3 py-1.5 rounded-lg transition-colors duration-200 flex-1 sm:flex-initial">
                        <i class="fa-solid fa-trash w-4 h-4"></i>
                        Hapus
                    </button>

                    <!-- Tombol Tambah -->
                    <button type="button" onclick="openModal('addModal')"
                        class="flex items-center justify-center gap-2 rounded-lg bg-btn-add hover:bg-btn-add-hover px-4 py-2 text-sm font-medium text-white focus:outline-none focus:ring-4 focus:ring-success-light flex-1 sm:flex-initial transition-colors duration-200">
                        <i class="fa-solid fa-plus"></i>
                        Tambah Laporan
                    </button>
                </div>
            </div>
        </div>

        {{-- Form Hapus Terpilih --}}
        <form id="deleteForm" method="POST" action="{{ route('sales-report.destroySelected') }}">
            @csrf
            @method('DELETE')
            <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                <div class="inline-block min-w-full align-middle">
                    <div class="border-2 border-gray-300 rounded-xl overflow-hidden shadow-sm">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <tr>
                                    <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                                    <th class="p-2 text-center">ID Laporan</th>
                                    <th class="p-2 text-left">Tanggal</th>
                                    <th class="p-2 text-left">Proyek</th>
                                    <th class="p-2 text-left">Nama Barang</th>
                                    <th class="p-2 text-center">Qty</th>
                                    <th class="p-2 text-center">HPP (Harga Modal)</th>
                                    <th class="p-2 text-center">Harga Jual</th>
                                    <th class="p-2 text-center">Profit</th>
                                    <th class="p-2 text-center">Status</th>
                                    <th class="p-2 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($salesReports as $index => $sale)
                                    @php
                                        $saleItems = is_string($sale->items)
                                            ? json_decode($sale->items, true)
                                            : $sale->items;
                                        $itemCount = count($saleItems);
                                        $verticalAlign = $itemCount >= 3 ? 'align-middle' : 'align-top';
                                    @endphp

                                    @foreach ($saleItems as $itemIndex => $saleItem)
                                        <tr
                                            class="{{ $itemIndex === 0 ? 'border-t-2 border-primary/40' : 'border-t border-gray-200' }} transition-colors duration-150 hover:bg-gray-100">
                                            @if ($itemIndex === 0)
                                                <td class="p-2 text-center {{ $verticalAlign }}"
                                                    rowspan="{{ $itemCount }}">
                                                    <input type="checkbox" name="selected_sales[]"
                                                        value="{{ $sale->id_sales_report }}"
                                                        class="w-4 h-4 accent-primary cursor-pointer">
                                                </td>
                                                <td class="p-2 text-center {{ $verticalAlign }}"
                                                    rowspan="{{ $itemCount }}">
                                                    {{ $sale->id_sales_report }}</td>
                                                <td class="p-2 text-sm {{ $verticalAlign }}"
                                                    rowspan="{{ $itemCount }}">
                                                    {{ $sale->date->format('d-m-Y') }}</td>
                                                <td class="p-2 font-medium {{ $verticalAlign }}"
                                                    rowspan="{{ $itemCount }}">
                                                    {{ $sale->name_proyek }}</td>
                                            @endif

                                            {{-- Nama Barang --}}
                                            <td class="p-2">
                                                {{ $saleItem['name_item'] ?? '-' }}

                                            </td>

                                            {{-- QTY --}}
                                            <td class="p-2 text-center">{{ $saleItem['quantity'] ?? 0 }}</td>

                                            {{-- Harga Modal (satuan | total) --}}
                                            <td class="p-2 text-center text-sm whitespace-nowrap">
                                                Rp {{ number_format($saleItem['capital_price'] ?? 0, 0, ',', '.') }} |
                                                <span class="font-semibold">Rp
                                                    {{ number_format(($saleItem['capital_price'] ?? 0) * ($saleItem['quantity'] ?? 0), 0, ',', '.') }}</span>
                                            </td>

                                            {{-- Harga Jual (satuan | total) --}}
                                            <td class="p-2 text-center text-sm whitespace-nowrap">
                                                Rp {{ number_format($saleItem['selling_price'] ?? 0, 0, ',', '.') }} |
                                                <span class="font-semibold">Rp
                                                    {{ number_format(($saleItem['selling_price'] ?? 0) * ($saleItem['quantity'] ?? 0), 0, ',', '.') }}</span>
                                            </td>

                                            @if ($itemIndex === 0)
                                                {{-- Profit (untuk seluruh proyek) --}}
                                                <td class="p-2 text-center font-medium text-success {{ $verticalAlign }}"
                                                    rowspan="{{ $itemCount }}">
                                                    Rp {{ number_format($sale->total_profit, 0, ',', '.') }}
                                                </td>

                                                {{-- Status --}}
                                                <td class="p-2 text-center {{ $verticalAlign }}"
                                                    rowspan="{{ $itemCount }}">
                                                    @if ($sale->status === 'Lunas')
                                                        <span
                                                            class="px-3 py-1.5 rounded-lg text-sm font-medium bg-success-light text-success inline-flex items-center gap-2">
                                                            <i class="fa-solid fa-check-circle"></i> Lunas
                                                        </span>
                                                    @else
                                                        <span
                                                            class="px-3 py-1.5 rounded-lg text-sm font-medium bg-warning-light text-warning">
                                                            {{ $sale->status }}
                                                        </span>
                                                    @endif
                                                </td>

                                                {{-- Aksi --}}
                                                <td class="p-2 text-center {{ $verticalAlign }}"
                                                    rowspan="{{ $itemCount }}">
                                                    @if (!$sale->isLunas())
                                                        <div class="flex flex-col gap-2">
                                                            <button type="button"
                                                                onclick="openModal('editModal-{{ $sale->id_sales_report }}')"
                                                                class="flex items-center justify-center gap-2 bg-btn-edit hover:bg-btn-edit-hover text-white px-3 py-1 rounded-lg transition-colors duration-200">
                                                                <i class="fa-solid fa-pen w-4 h-4"></i>
                                                                Edit
                                                            </button>
                                                            <button type="button"
                                                                onclick="openModal('statusModal-{{ $sale->id_sales_report }}')"
                                                                class="flex items-center justify-center gap-2 bg-success hover:bg-success/90 text-white px-3 py-1 rounded-lg transition-colors duration-200">
                                                                <i class="fa-solid fa-check-circle w-4 h-4"></i>
                                                                Status
                                                            </button>
                                                        </div>
                                                    @else
                                                        <span class="text-gray-400 text-sm">-</span>
                                                    @endif
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center p-4 text-gray-500">Data tidak ditemukan.
                                        </td>
                                    </tr>
                                @endforelse

                                {{-- Grand Total Row --}}
                                @if ($salesReports->isNotEmpty())
                                    <tr
                                        class="bg-gradient-to-r from-primary/20 to-primary/10 border-t-4 border-primary font-bold text-base">
                                        <td colspan="6" class="p-3 text-right text-gray-800">
                                            TOTAL PENJUALAN & PROFIT
                                        </td>
                                        <td class="p-3 text-center text-gray-800">
                                            Rp {{ number_format($grandTotals->grand_total_capital ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="p-3 text-center text-gray-800">
                                            Rp {{ number_format($grandTotals->grand_total_selling ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="p-3 text-right text-success font-bold text-lg">
                                            Rp {{ number_format($grandTotals->grand_total_profit ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td colspan="2"></td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>

        {{-- Pagination --}}
        <div class="flex mt-4 justify-center">
            <div class="flex items-center gap-3 bg-white border border-gray-300 rounded-lg px-4 py-2 shadow-sm">
                <a href="{{ $salesReports->appends(request()->query())->previousPageUrl() }}"
                    class="flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100 transition-colors duration-200
                    {{ $salesReports->onFirstPage() ? 'opacity-40 pointer-events-none cursor-not-allowed' : 'hover:border-primary' }}">
                    &lt;
                </a>

                <span class="text-sm font-medium text-gray-700">
                    {{ $salesReports->currentPage() }}
                    <span class="text-gray-400">/</span>
                    {{ $salesReports->lastPage() }}
                </span>

                <a href="{{ $salesReports->appends(request()->query())->nextPageUrl() }}"
                    class="flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100 transition-colors duration-200
                    {{ !$salesReports->hasMorePages() ? 'opacity-40 pointer-events-none cursor-not-allowed' : 'hover:border-primary' }}">
                    &gt;
                </a>
            </div>
        </div>
    </div>

    {{-- Modal Tambah --}}
    <x-modal id="addModal" title="Tambah Laporan Penjualan" action="{{ route('sales-report.store') }}" method="POST"
        buttonText="Simpan">

        <div class="mb-3">
            <label class="block text-gray-700 mb-1">Tanggal <span class="text-error">*</span></label>
            <input type="date" name="date" class="w-full border rounded p-2" required>
        </div>

        <div class="mb-3">
            <label class="block text-gray-700 mb-1">Nama Proyek <span class="text-error">*</span></label>
            <input type="text" name="name_proyek" class="w-full border rounded p-2"
                placeholder="Contoh: PROYEK KAHFI" required>
        </div>

        <div id="items-container" class="mb-4">
            <label class="block text-gray-700 font-semibold mb-2">Item-Item Barang <span
                    class="text-error">*</span></label>
            <div id="items-list">
                <div class="item-row mb-3 p-3 border rounded bg-gray-50">
                    <div class="flex items-center gap-2 mb-2">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="item-from-stock accent-primary">
                            <span class="text-sm">Dari Stok</span>
                        </label>
                    </div>

                    <select class="item-select w-full border rounded p-2 mb-2" disabled>
                        <option value="">-- Pilih Barang --</option>
                        @foreach ($items as $item)
                            <option value="{{ $item->id_item }}" data-name="{{ $item->name_item }}"
                                data-capital="{{ $item->capital_price }}" data-selling="{{ $item->selling_price }}"
                                data-stock="{{ $item->quantity }}">
                                {{ $item->name_item }} (Stok: {{ $item->quantity }})
                            </option>
                        @endforeach
                    </select>

                    <input type="text" class="item-name w-full border rounded p-2 mb-2" placeholder="Nama Barang *"
                        required>

                    <div class="grid grid-cols-3 gap-2">
                        <input type="number" class="item-qty border rounded p-2" placeholder="Qty *" required
                            min="1" value="1">
                        <input type="number" class="item-capital border rounded p-2" placeholder="Harga Modal *"
                            required min="0">
                        <input type="number" class="item-selling border rounded p-2" placeholder="Harga Jual *" required
                            min="0">
                    </div>

                    <button type="button"
                        class="remove-item mt-2 bg-btn-delete text-white px-3 py-1 rounded hover:bg-btn-delete-hover w-full">
                        <i class="fa-solid fa-trash"></i> Hapus Item
                    </button>
                </div>
            </div>
            <button type="button" id="add-item"
                class="bg-primary text-white px-4 py-2 rounded hover:bg-primary-hover w-full">
                <i class="fa-solid fa-plus"></i> Tambah Item
            </button>
        </div>

        <input type="hidden" name="items" id="items-json" value="[]">
    </x-modal>

    {{-- Modal Edit untuk setiap sale --}}
    @foreach ($salesReports as $sale)
        @if (!$sale->isLunas())
            <x-modal id="editModal-{{ $sale->id_sales_report }}" title="Edit Laporan Penjualan"
                action="{{ route('sales-report.update', $sale->id_sales_report) }}" method="PUT" buttonText="Update">

                <div class="mb-3">
                    <label class="block text-gray-700 mb-1">ID Laporan</label>
                    <input type="text" value="{{ $sale->id_sales_report }}"
                        class="w-full border rounded p-2 bg-gray-100 cursor-not-allowed" readonly>
                </div>

                <div class="mb-3">
                    <label class="block text-gray-700 mb-1">Tanggal <span class="text-error">*</span></label>
                    <input type="date" name="date" value="{{ $sale->date->format('Y-m-d') }}"
                        class="w-full border rounded p-2" required>
                </div>

                <div class="mb-3">
                    <label class="block text-gray-700 mb-1">Nama Proyek <span class="text-error">*</span></label>
                    <input type="text" name="name_proyek" value="{{ $sale->name_proyek }}"
                        class="w-full border rounded p-2" required>
                </div>

                <div id="items-container-edit-{{ $sale->id_sales_report }}" class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Item-Item Barang</label>
                    <div id="items-list-edit-{{ $sale->id_sales_report }}">
                        @php
                            $existingItems = is_string($sale->items) ? json_decode($sale->items, true) : $sale->items;
                        @endphp
                        @foreach ($existingItems as $index => $item)
                            <div class="item-row-edit mb-3 p-3 border rounded bg-gray-50"
                                data-index="{{ $index }}">
                                {{-- Checkbox Dari Stok --}}
                                <div class="flex items-center gap-2 mb-2">
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" class="item-from-stock-edit accent-primary"
                                            {{ !empty($item['from_stock']) && $item['from_stock'] !== 'false' ? 'checked' : '' }}>
                                        <span class="text-sm">Dari Stok</span>
                                    </label>
                                </div>

                                {{-- Dropdown Pilih Barang --}}
                                <select class="item-select-edit w-full border rounded p-2 mb-2"
                                    {{ empty($item['from_stock']) || $item['from_stock'] === 'false' ? 'disabled' : '' }}>
                                    <option value="">-- Pilih Barang --</option>
                                    @foreach ($items as $stockItem)
                                        <option value="{{ $stockItem->id_item }}"
                                            data-name="{{ $stockItem->name_item }}"
                                            data-capital="{{ $stockItem->capital_price }}"
                                            data-selling="{{ $stockItem->selling_price }}"
                                            data-stock="{{ $stockItem->quantity }}"
                                            {{ !empty($item['id_item']) && $item['id_item'] == $stockItem->id_item ? 'selected' : '' }}>
                                            {{ $stockItem->name_item }} (Stok: {{ $stockItem->quantity }})
                                        </option>
                                    @endforeach
                                </select>

                                {{-- Input Nama Barang --}}
                                <input type="text" name="items[{{ $index }}][name_item]"
                                    value="{{ $item['name_item'] ?? '' }}"
                                    class="item-name-edit w-full border rounded p-2 mb-2" placeholder="Nama Barang *"
                                    {{ !empty($item['from_stock']) && $item['from_stock'] !== 'false' ? 'readonly' : '' }}
                                    required>

                                <div class="grid grid-cols-3 gap-2">
                                    <input type="number" name="items[{{ $index }}][quantity]"
                                        value="{{ $item['quantity'] ?? 0 }}" class="item-qty-edit border rounded p-2"
                                        placeholder="Qty *" required min="1">
                                    <input type="number" name="items[{{ $index }}][capital_price]"
                                        value="{{ $item['capital_price'] ?? 0 }}"
                                        class="item-capital-edit border rounded p-2" placeholder="Harga Modal *"
                                        {{ !empty($item['from_stock']) && $item['from_stock'] !== 'false' ? 'readonly' : '' }}
                                        required min="0">
                                    <input type="number" name="items[{{ $index }}][selling_price]"
                                        value="{{ $item['selling_price'] ?? 0 }}"
                                        class="item-selling-edit border rounded p-2" placeholder="Harga Jual *"
                                        {{ !empty($item['from_stock']) && $item['from_stock'] !== 'false' ? 'readonly' : '' }}
                                        required min="0">
                                </div>

                                <input type="hidden" name="items[{{ $index }}][from_stock]"
                                    class="from-stock-hidden"
                                    value="{{ !empty($item['from_stock']) && $item['from_stock'] !== 'false' ? 'true' : 'false' }}">
                                <input type="hidden" name="items[{{ $index }}][id_item]" class="id-item-hidden"
                                    value="{{ $item['id_item'] ?? '' }}">

                                <button type="button"
                                    class="remove-item-edit mt-2 bg-btn-delete text-white px-3 py-1 rounded hover:bg-btn-delete-hover w-full">
                                    <i class="fa-solid fa-trash"></i> Hapus Item
                                </button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button"
                        class="add-item-edit bg-primary text-white px-4 py-2 rounded hover:bg-primary-hover w-full"
                        data-sale-id="{{ $sale->id_sales_report }}">
                        <i class="fa-solid fa-plus"></i> Tambah Item
                    </button>
                </div>
            </x-modal>
        @endif

        {{-- Modal Update Status --}}
        @if (!$sale->isLunas())
            <x-modal id="statusModal-{{ $sale->id_sales_report }}" title="Update Status Pembayaran"
                action="{{ route('sales-report.updateStatus', $sale->id_sales_report) }}" method="POST"
                buttonText="Update Status">
                @method('PATCH')

                <div class="mb-4">
                    <p class="text-gray-700 mb-3">Update status pembayaran laporan penjualan:</p>
                    <div class="bg-gray-50 p-3 rounded-lg mb-4">
                        <p class="font-semibold text-gray-800">{{ $sale->name_proyek }}</p>
                        <p class="text-sm text-gray-600">Tanggal: {{ $sale->date->format('d-m-Y') }}</p>
                        <p class="text-sm text-gray-600">Total Profit: Rp
                            {{ number_format($sale->total_profit, 0, ',', '.') }}</p>
                    </div>

                    <label class="block text-gray-700 font-semibold mb-2">Status Pembayaran <span
                            class="text-error">*</span></label>
                    <select name="status"
                        class="w-full border border-gray-300 rounded-lg p-3 focus:border-primary focus:ring-2 focus:ring-primary-light"
                        required>
                        <option value="Belum Lunas" {{ $sale->status === 'Belum Lunas' ? 'selected' : '' }}>Belum Lunas
                        </option>
                        <option value="Lunas" {{ $sale->status === 'Lunas' ? 'selected' : '' }}>Lunas</option>
                    </select>

                    <div class="bg-warning-light border-l-4 border-warning p-4 mt-4 rounded">
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-exclamation-triangle text-warning text-xl mt-0.5"></i>
                            <div>
                                <p class="font-semibold text-warning mb-1">Peringatan Penting!</p>
                                <p class="text-sm text-gray-700">
                                    Setelah status diubah menjadi <strong>"Lunas"</strong>, data laporan penjualan ini
                                    <strong>tidak dapat diubah atau diedit lagi</strong>.
                                    Pastikan semua informasi sudah benar sebelum mengonfirmasi.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </x-modal>
        @endif
    @endforeach

    {{-- Modal Hapus Bulk --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        <p>Apakah Anda yakin ingin menghapus data yang dipilih?</p>
    </x-modal>

    <script>
        function submitDeleteForm() {
            const form = document.getElementById('deleteForm');
            form.submit();
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Select All Checkbox functionality
            const selectAllCheckbox = document.getElementById('selectAll');
            const saleCheckboxes = document.querySelectorAll('input[name="selected_sales[]"]');

            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    saleCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                });
            }

            saleCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    if (!this.checked) {
                        selectAllCheckbox.checked = false;
                    } else {
                        const allChecked = Array.from(saleCheckboxes).every(cb => cb.checked);
                        selectAllCheckbox.checked = allChecked;
                    }
                });
            });

            // Handle from_stock checkbox
            document.querySelectorAll('.item-from-stock').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const row = this.closest('.item-row');
                    const select = row.querySelector('.item-select');
                    const nameInput = row.querySelector('.item-name');
                    const capitalInput = row.querySelector('.item-capital');
                    const sellingInput = row.querySelector('.item-selling');

                    if (this.checked) {
                        select.disabled = false;
                        select.required = true;
                        nameInput.readOnly = true;
                        capitalInput.readOnly = true;
                        sellingInput.readOnly = true;
                    } else {
                        select.disabled = true;
                        select.required = false;
                        select.value = '';
                        nameInput.readOnly = false;
                        capitalInput.readOnly = false;
                        sellingInput.readOnly = false;
                    }
                });
            });

            // Handle item selection from stock
            document.querySelectorAll('.item-select').forEach(select => {
                select.addEventListener('change', function() {
                    const row = this.closest('.item-row');
                    const selectedOption = this.options[this.selectedIndex];

                    if (selectedOption.value) {
                        const nameInput = row.querySelector('.item-name');
                        const capitalInput = row.querySelector('.item-capital');
                        const sellingInput = row.querySelector('.item-selling');

                        nameInput.value = selectedOption.dataset.name;
                        capitalInput.value = selectedOption.dataset.capital;
                        sellingInput.value = selectedOption.dataset.selling;
                    }
                });
            });

            // Add item button
            if (document.getElementById('add-item')) {
                document.getElementById('add-item').addEventListener('click', function(e) {
                    e.preventDefault();
                    const itemsContainer = document.getElementById('items-list');
                    const newItem = document.createElement('div');
                    newItem.className = 'item-row mb-3 p-3 border rounded bg-gray-50';
                    newItem.innerHTML = `
                        <div class="flex items-center gap-2 mb-2">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" class="item-from-stock accent-primary">
                                <span class="text-sm">Dari Stok</span>
                            </label>
                        </div>

                        <select class="item-select w-full border rounded p-2 mb-2" disabled>
                            <option value="">-- Pilih Barang --</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id_item }}" 
                                        data-name="{{ $item->name_item }}"
                                        data-capital="{{ $item->capital_price }}"
                                        data-selling="{{ $item->selling_price }}"
                                        data-stock="{{ $item->quantity }}">
                                    {{ $item->name_item }} (Stok: {{ $item->quantity }})
                                </option>
                            @endforeach
                        </select>

                        <input type="text" class="item-name w-full border rounded p-2 mb-2" placeholder="Nama Barang *" required>
                        
                        <div class="grid grid-cols-3 gap-2">
                            <input type="number" class="item-qty border rounded p-2" placeholder="Qty *" required min="1" value="1">
                            <input type="number" class="item-capital border rounded p-2" placeholder="Harga Modal *" required min="0">
                            <input type="number" class="item-selling border rounded p-2" placeholder="Harga Jual *" required min="0">
                        </div>
                        
                        <button type="button" class="remove-item mt-2 bg-btn-delete text-white px-3 py-1 rounded hover:bg-btn-delete-hover w-full">
                            <i class="fa-solid fa-trash"></i> Hapus Item
                        </button>
                    `;
                    itemsContainer.appendChild(newItem);
                    attachItemListeners();
                });
            }

            function attachItemListeners() {
                document.querySelectorAll('.remove-item').forEach(btn => {
                    btn.removeEventListener('click', removeItemHandler);
                    btn.addEventListener('click', removeItemHandler);
                });

                document.querySelectorAll('.item-from-stock').forEach(checkbox => {
                    checkbox.removeEventListener('change', toggleStockHandler);
                    checkbox.addEventListener('change', toggleStockHandler);
                });

                document.querySelectorAll('.item-select').forEach(select => {
                    select.removeEventListener('change', selectItemHandler);
                    select.addEventListener('change', selectItemHandler);
                });
            }

            function removeItemHandler(e) {
                e.preventDefault();
                const itemsContainer = document.getElementById('items-list');
                const remainingItems = itemsContainer.querySelectorAll('.item-row');

                if (remainingItems.length <= 1) {
                    alert('Minimal harus ada 1 item!');
                    return;
                }

                this.closest('.item-row').remove();
            }

            function toggleStockHandler() {
                const row = this.closest('.item-row');
                const select = row.querySelector('.item-select');
                const nameInput = row.querySelector('.item-name');
                const capitalInput = row.querySelector('.item-capital');
                const sellingInput = row.querySelector('.item-selling');

                if (this.checked) {
                    select.disabled = false;
                    select.required = true;
                    nameInput.readOnly = true;
                    capitalInput.readOnly = true;
                    sellingInput.readOnly = true;
                } else {
                    select.disabled = true;
                    select.required = false;
                    select.value = '';
                    nameInput.readOnly = false;
                    capitalInput.readOnly = false;
                    sellingInput.readOnly = false;
                }
            }

            function selectItemHandler() {
                const row = this.closest('.item-row');
                const selectedOption = this.options[this.selectedIndex];

                if (selectedOption.value) {
                    const nameInput = row.querySelector('.item-name');
                    const capitalInput = row.querySelector('.item-capital');
                    const sellingInput = row.querySelector('.item-selling');

                    nameInput.value = selectedOption.dataset.name;
                    capitalInput.value = selectedOption.dataset.capital;
                    sellingInput.value = selectedOption.dataset.selling;
                }
            }

            attachItemListeners();

            // Handle add form submission
            const addModal = document.getElementById('addModal');
            if (addModal) {
                const addForm = addModal.querySelector('form');
                if (addForm) {
                    addForm.addEventListener('submit', function(e) {
                        const items = [];
                        const itemRows = document.querySelectorAll('.item-row');

                        itemRows.forEach(row => {
                            const fromStockCheck = row.querySelector('.item-from-stock');
                            const itemSelect = row.querySelector('.item-select');
                            const itemName = row.querySelector('.item-name').value;
                            const qty = parseInt(row.querySelector('.item-qty').value) || 0;
                            const capital = parseInt(row.querySelector('.item-capital').value) || 0;
                            const selling = parseInt(row.querySelector('.item-selling').value) || 0;

                            if (itemName && qty > 0) {
                                const item = {
                                    name_item: itemName,
                                    quantity: qty,
                                    capital_price: capital,
                                    selling_price: selling,
                                    from_stock: fromStockCheck.checked,
                                    id_item: fromStockCheck.checked ? itemSelect.value : null
                                };
                                items.push(item);
                            }
                        });

                        if (items.length === 0) {
                            e.preventDefault();
                            alert('Minimal harus ada 1 item dengan data lengkap!');
                            return false;
                        }

                        document.getElementById('items-json').value = JSON.stringify(items);
                        return true;
                    });
                }
            }

            // Handle edit form - add item button
            document.querySelectorAll('.add-item-edit').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const saleId = this.getAttribute('data-sale-id');
                    const itemsContainer = document.getElementById('items-list-edit-' + saleId);
                    const currentItems = itemsContainer.querySelectorAll('.item-row-edit');
                    const newIndex = currentItems.length;

                    const newItem = document.createElement('div');
                    newItem.className = 'item-row-edit mb-3 p-3 border rounded bg-gray-50';
                    newItem.setAttribute('data-index', newIndex);
                    newItem.innerHTML = `
                        <div class="flex items-center gap-2 mb-2">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" class="item-from-stock-edit accent-primary">
                                <span class="text-sm">Dari Stok</span>
                            </label>
                        </div>

                        <select class="item-select-edit w-full border rounded p-2 mb-2" disabled>
                            <option value="">-- Pilih Barang --</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id_item }}" 
                                        data-name="{{ $item->name_item }}"
                                        data-capital="{{ $item->capital_price }}"
                                        data-selling="{{ $item->selling_price }}"
                                        data-stock="{{ $item->quantity }}">
                                    {{ $item->name_item }} (Stok: {{ $item->quantity }})
                                </option>
                            @endforeach
                        </select>

                        <input type="text" name="items[${newIndex}][name_item]"
                            class="item-name-edit w-full border rounded p-2 mb-2" placeholder="Nama Barang *" required>
                        
                        <div class="grid grid-cols-3 gap-2">
                            <input type="number" name="items[${newIndex}][quantity]"
                                class="item-qty-edit border rounded p-2" placeholder="Qty *" required min="1" value="1">
                            <input type="number" name="items[${newIndex}][capital_price]"
                                class="item-capital-edit border rounded p-2" placeholder="Harga Modal *" required min="0" value="0">
                            <input type="number" name="items[${newIndex}][selling_price]"
                                class="item-selling-edit border rounded p-2" placeholder="Harga Jual *" required min="0" value="0">
                        </div>

                        <input type="hidden" name="items[${newIndex}][from_stock]" class="from-stock-hidden" value="false">
                        <input type="hidden" name="items[${newIndex}][id_item]" class="id-item-hidden" value="">

                        <button type="button"
                            class="remove-item-edit mt-2 bg-btn-delete text-white px-3 py-1 rounded hover:bg-btn-delete-hover w-full">
                            <i class="fa-solid fa-trash"></i> Hapus Item
                        </button>
                    `;
                    itemsContainer.appendChild(newItem);
                    attachEditRemoveListeners();
                    attachEditStockListeners();
                });
            });

            // Handle checkbox "Dari Stok" di modal edit
            function attachEditStockListeners() {
                document.querySelectorAll('.item-from-stock-edit').forEach(checkbox => {
                    checkbox.removeEventListener('change', toggleEditStockHandler);
                    checkbox.addEventListener('change', toggleEditStockHandler);
                });

                document.querySelectorAll('.item-select-edit').forEach(select => {
                    select.removeEventListener('change', selectEditItemHandler);
                    select.addEventListener('change', selectEditItemHandler);
                });
            }

            function toggleEditStockHandler() {
                const row = this.closest('.item-row-edit');
                const select = row.querySelector('.item-select-edit');
                const nameInput = row.querySelector('.item-name-edit');
                const capitalInput = row.querySelector('.item-capital-edit');
                const sellingInput = row.querySelector('.item-selling-edit');
                const fromStockHidden = row.querySelector('.from-stock-hidden');

                if (this.checked) {
                    select.disabled = false;
                    select.required = true;
                    nameInput.readOnly = true;
                    capitalInput.readOnly = true;
                    sellingInput.readOnly = true;
                    fromStockHidden.value = 'true';
                } else {
                    select.disabled = true;
                    select.required = false;
                    select.value = '';
                    nameInput.readOnly = false;
                    capitalInput.readOnly = false;
                    sellingInput.readOnly = false;
                    fromStockHidden.value = 'false';
                    row.querySelector('.id-item-hidden').value = '';
                }
            }

            function selectEditItemHandler() {
                const row = this.closest('.item-row-edit');
                const selectedOption = this.options[this.selectedIndex];

                if (selectedOption.value) {
                    const nameInput = row.querySelector('.item-name-edit');
                    const capitalInput = row.querySelector('.item-capital-edit');
                    const sellingInput = row.querySelector('.item-selling-edit');
                    const idItemHidden = row.querySelector('.id-item-hidden');

                    nameInput.value = selectedOption.dataset.name;
                    capitalInput.value = selectedOption.dataset.capital;
                    sellingInput.value = selectedOption.dataset.selling;
                    idItemHidden.value = selectedOption.value;
                }
            }

            // Initialize edit stock listeners for existing items
            attachEditStockListeners();

            function attachEditRemoveListeners() {
                document.querySelectorAll('.remove-item-edit').forEach(btn => {
                    btn.removeEventListener('click', removeEditItemHandler);
                    btn.addEventListener('click', removeEditItemHandler);
                });
            }

            function removeEditItemHandler(e) {
                e.preventDefault();
                const itemsContainer = this.closest('[id^="items-list-edit-"]');
                const remainingItems = itemsContainer.querySelectorAll('.item-row-edit');

                if (remainingItems.length <= 1) {
                    alert('Minimal harus ada 1 item!');
                    return;
                }

                this.closest('.item-row-edit').remove();

                // Re-index items
                itemsContainer.querySelectorAll('.item-row-edit').forEach((row, index) => {
                    row.querySelectorAll('input[name^="items"]').forEach(input => {
                        const fieldName = input.name.match(/\[(\w+)\]$/)[1];
                        input.name = `items[${index}][${fieldName}]`;
                    });
                });
            }

            attachEditRemoveListeners();
        });
    </script>
@endsection
