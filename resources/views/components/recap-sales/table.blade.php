{{-- Sales Report Table Component --}}
<form id="deleteForm" method="POST" action="{{ route('recap-sales.destroySelected') }}">
    @csrf
    @method('DELETE')
    <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
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
                        @forelse($salesRecaps as $index => $sale)
                            @php
                                $saleItems = is_string($sale->items) ? json_decode($sale->items, true) : $sale->items;
                                $itemCount = count($saleItems);
                                $verticalAlign = $itemCount >= 3 ? 'align-middle' : 'align-top';
                            @endphp

                            @foreach ($saleItems as $itemIndex => $saleItem)
                                <tr
                                    class="{{ $itemIndex === 0 ? 'border-t-2 border-primary/40' : 'border-t border-border' }} transition-colors duration-150 hover:bg-surface-hover">
                                    @if ($itemIndex === 0)
                                        <td class="p-2 text-center {{ $verticalAlign }}" rowspan="{{ $itemCount }}">
                                            <input type="checkbox" name="selected_sales[]"
                                                value="{{ $sale->id_sales_recap }}"
                                                class="w-4 h-4 accent-primary cursor-pointer">
                                        </td>
                                        <td class="p-2 text-center {{ $verticalAlign }}" rowspan="{{ $itemCount }}">
                                            {{ $sale->id_sales_recap }}</td>
                                        <td class="p-2 text-sm {{ $verticalAlign }}" rowspan="{{ $itemCount }}">
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
                                    <td class="p-2 text-center">
                                        {{ $saleItem['quantity'] ?? 0 }}</td>

                                    {{-- Harga Modal (satuan | total) --}}
                                    <td class="p-2 text-center text-sm whitespace-nowrap">
                                        Rp
                                        {{ number_format($saleItem['capital_price'] ?? 0, 0, ',', '.') }}
                                        |
                                        <span class="font-semibold">Rp
                                            {{ number_format(($saleItem['capital_price'] ?? 0) * ($saleItem['quantity'] ?? 0), 0, ',', '.') }}</span>
                                    </td>

                                    {{-- Harga Jual (satuan | total) --}}
                                    <td class="p-2 text-center text-sm whitespace-nowrap">
                                        Rp
                                        {{ number_format($saleItem['selling_price'] ?? 0, 0, ',', '.') }}
                                        |
                                        <span class="font-semibold">Rp
                                            {{ number_format(($saleItem['selling_price'] ?? 0) * ($saleItem['quantity'] ?? 0), 0, ',', '.') }}</span>
                                    </td>

                                    @if ($itemIndex === 0)
                                        {{-- Profit (untuk seluruh proyek) --}}
                                        <td class="p-2 text-center font-medium text-success {{ $verticalAlign }}"
                                            rowspan="{{ $itemCount }}">
                                            Rp
                                            {{ number_format($sale->total_profit, 0, ',', '.') }}
                                        </td>

                                        {{-- Status --}}
                                        <td class="p-2 text-center {{ $verticalAlign }}"
                                            rowspan="{{ $itemCount }}">
                                            @if ($sale->status === 'Lunas')
                                                <span
                                                    class="px-3 py-1.5 rounded-lg text-sm font-medium bg-success-light text-success inline-flex items-center gap-2">
                                                    <i class="fa-solid fa-check-circle"></i>
                                                    Lunas
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
                                                        onclick="openModal('editModal-{{ $sale->id_sales_recap }}')"
                                                        class="flex items-center justify-center gap-2 bg-btn-edit hover:bg-btn-edit-hover text-white px-3 py-1 rounded-lg transition-colors duration-200">
                                                        <i class="fa-solid fa-pen w-4 h-4"></i>
                                                        Edit
                                                    </button>
                                                    <button type="button"
                                                        onclick="openModal('statusModal-{{ $sale->id_sales_recap }}')"
                                                        class="flex items-center justify-center gap-2 bg-success hover:bg-success/90 text-white px-3 py-1 rounded-lg transition-colors duration-200">
                                                        <i class="fa-solid fa-check-circle w-4 h-4"></i>
                                                        Status
                                                    </button>
                                                </div>
                                            @else
                                                <span class="text-text-tertiary text-sm">-</span>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="11" class="text-center p-4 text-text-secondary">Data tidak ditemukan.
                                </td>
                            </tr>
                        @endforelse

                        {{-- Grand Total Row --}}
                        @if ($salesRecaps->isNotEmpty())
                            <tr
                                class="bg-gradient-to-r from-primary/20 to-primary/10 border-t-4 border-primary font-bold text-base">
                                <td colspan="6" class="p-3 text-right text-text-heading">
                                    TOTAL PENJUALAN & PROFIT
                                </td>
                                <td class="p-3 text-center text-text-heading">
                                    Rp
                                    {{ number_format($grandTotals->grand_total_capital ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="p-3 text-center text-text-heading">
                                    Rp
                                    {{ number_format($grandTotals->grand_total_selling ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="p-3 text-right text-success font-bold text-lg">
                                    Rp
                                    {{ number_format($grandTotals->grand_total_profit ?? 0, 0, ',', '.') }}
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

