{{-- Tabel Rekap Penjualan (gaya Filament): toolbar (hapus massal + filter
     bulan / tahun / status + pencarian), tabel multi-baris per laporan
     (kolom laporan di-rowspan), baris total, paginasi.

     Variabel:
     - $salesRecaps : Paginator SalesRecap
     - $grandTotals : grand_total_capital / grand_total_selling / grand_total_profit
     - $indexUrl    : URL halaman ini (tujuan filter & Reset)
     - $filterTab   : key tab halaman Rekap (hidden input "tab"), null di halaman sendiri

     Hook JS (sales-recaps/index.js): form#deleteForm, #selectAll,
     input[name="selected_sales[]"], #delete-button, #month-select,
     #year-select, #status-select. --}}
@php
    $isFiltered = request()->filled('search') || request()->filled('month') || request()->filled('year') || request()->filled('status');
    $rupiah = fn ($value) => 'Rp ' . number_format($value ?? 0, 0, ',', '.');
@endphp

<x-fi.card x-data="{ selected: 0 }"
    x-init="selected = $el.querySelectorAll('[name^=selected_sales]:checked').length"
    x-on:change="selected = $el.querySelectorAll('[name^=selected_sales]:checked').length">

    {{-- Toolbar: aksi massal (kiri, saat ada yang dicentang) + filter & pencarian (kanan) --}}
    <div class="flex flex-wrap items-center gap-3 border-b border-gray-200 px-4 py-3 sm:px-6">
        <div x-cloak x-show="selected > 0" class="flex shrink-0 items-center gap-3">
            <span class="whitespace-nowrap text-sm text-gray-500">
                <span class="font-semibold text-gray-950" x-text="selected"></span> laporan dipilih
            </span>

            <x-fi.button id="delete-button" color="danger" size="sm" icon="trash" outlined disabled
                onclick="openModal('deleteModal')">
                Hapus yang dipilih
            </x-fi.button>
        </div>

        {{-- Form GET: bulan/tahun/status langsung submit saat diganti (index.js), pencarian saat Enter --}}
        <form method="GET" action="{{ $filterTab ? route('rekap.index') : $indexUrl }}"
            class="grid w-full grid-cols-2 gap-3 sm:flex sm:flex-wrap sm:items-center sm:justify-end xl:ms-auto xl:w-auto">
            @if ($filterTab)
                <input type="hidden" name="tab" value="{{ $filterTab }}">
            @endif

            <div class="sm:w-44">
                <x-fi.month-select :value="request('month')" />
            </div>
            <div class="sm:w-36">
                <x-fi.year-select :value="request('year')" />
            </div>
            <div class="col-span-2 sm:w-40">
                <x-fi.select name="status" id="status-select" aria-label="Filter Status" :value="request('status')"
                    :options="['' => 'Semua Status', 'lunas' => 'Sudah Lunas', 'belum' => 'Belum Lunas']"
                    data-fi-select />
            </div>
            <x-fi.search-input :value="request('search')" placeholder="Cari proyek..." class="col-span-2 sm:w-64" />

            @if ($isFiltered)
                <x-fi.link :href="$indexUrl" color="gray" icon="x-mark"
                    class="col-span-2 justify-self-start whitespace-nowrap" title="Hapus semua filter & pencarian">
                    Reset
                </x-fi.link>
            @endif
        </form>
    </div>

    <form id="deleteForm" method="POST" action="{{ route('recap-sales.destroySelected') }}">
        @csrf
        @method('DELETE')

        <div class="relative overflow-x-auto">
            <table class="w-full table-auto divide-y divide-gray-200 text-start">

                {{-- Header Tabel --}}
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-1 px-2 py-3 sm:ps-4">
                            <input type="checkbox" id="selectAll" class="fi-checkbox" title="Pilih semua">
                        </th>
                        <x-fi.table.th dense>ID Laporan</x-fi.table.th>
                        <x-fi.table.th dense>Tanggal</x-fi.table.th>
                        <x-fi.table.th dense>Proyek</x-fi.table.th>
                        <x-fi.table.th dense>Nama Barang</x-fi.table.th>
                        <x-fi.table.th dense align="center">Qty</x-fi.table.th>
                        <x-fi.table.th dense align="end">HPP (Harga Modal)</x-fi.table.th>
                        <x-fi.table.th dense align="end">Harga Jual</x-fi.table.th>
                        <x-fi.table.th dense align="end">Profit</x-fi.table.th>
                        <x-fi.table.th dense>Status</x-fi.table.th>
                        <x-fi.table.th dense align="center">Aksi</x-fi.table.th>
                    </tr>
                </thead>

                {{-- Body Tabel: satu <tbody> per laporan (baris item + kolom laporan di-rowspan) --}}
                @forelse ($salesRecaps as $sale)
                    @php
                        $saleItems = is_string($sale->items) ? json_decode($sale->items, true) : $sale->items;
                        $saleItems = is_array($saleItems) && count($saleItems) ? array_values($saleItems) : [[]];
                        $itemCount = count($saleItems);
                        $isLunas = $sale->isLunas();
                    @endphp

                    <tbody class="divide-y divide-gray-100 bg-white text-gray-950 transition duration-75 hover:bg-gray-50 has-[:checked]:bg-blue-50">
                        @foreach ($saleItems as $itemIndex => $saleItem)
                            @php
                                $qty = (int) ($saleItem['quantity'] ?? 0);
                                $capital = (int) ($saleItem['capital_price'] ?? 0);
                                $selling = (int) ($saleItem['selling_price'] ?? 0);
                            @endphp
                            <tr>
                                {{-- Kolom laporan (hanya di baris pertama item) --}}
                                @if ($itemIndex === 0)
                                    <td class="w-1 px-2 py-3 align-top sm:ps-4" rowspan="{{ $itemCount }}">
                                        <input type="checkbox" name="selected_sales[]" value="{{ $sale->id_sales_recap }}"
                                            class="fi-checkbox" title="Pilih {{ $sale->id_sales_recap }}">
                                    </td>
                                    <x-fi.table.td dense class="whitespace-nowrap align-top font-medium text-primary" rowspan="{{ $itemCount }}">
                                        {{ $sale->id_sales_recap }}
                                    </x-fi.table.td>
                                    <x-fi.table.td dense class="whitespace-nowrap align-top" rowspan="{{ $itemCount }}">
                                        {{ $sale->date->format('d M Y') }}
                                    </x-fi.table.td>
                                    <x-fi.table.td dense class="min-w-[9rem] max-w-[14rem] align-top font-medium" rowspan="{{ $itemCount }}">
                                        <div class="line-clamp-2" title="{{ $sale->name_proyek }}">{{ $sale->name_proyek ?: '-' }}</div>
                                    </x-fi.table.td>
                                @endif

                                {{-- Nama Barang --}}
                                <x-fi.table.td dense class="min-w-[9rem] max-w-[16rem]">
                                    <div class="line-clamp-2" title="{{ $saleItem['name_item'] ?? '-' }}">{{ $saleItem['name_item'] ?? '-' }}</div>
                                </x-fi.table.td>

                                {{-- Qty --}}
                                <x-fi.table.td dense align="center" class="whitespace-nowrap tabular-nums">{{ $qty }}</x-fi.table.td>

                                {{-- HPP: total (atas) | satuan (bawah) --}}
                                <x-fi.table.td dense align="end" class="whitespace-nowrap tabular-nums"
                                    title="Satuan: {{ $rupiah($capital) }} | Total: {{ $rupiah($capital * $qty) }}">
                                    <div class="font-medium">{{ $rupiah($capital * $qty) }}</div>
                                    <div class="text-xs text-gray-500">Satuan {{ $rupiah($capital) }}</div>
                                </x-fi.table.td>

                                {{-- Harga Jual: total (atas) | satuan (bawah) --}}
                                <x-fi.table.td dense align="end" class="whitespace-nowrap tabular-nums"
                                    title="Satuan: {{ $rupiah($selling) }} | Total: {{ $rupiah($selling * $qty) }}">
                                    <div class="font-medium">{{ $rupiah($selling * $qty) }}</div>
                                    <div class="text-xs text-gray-500">Satuan {{ $rupiah($selling) }}</div>
                                </x-fi.table.td>

                                {{-- Kolom laporan kedua (hanya di baris pertama item) --}}
                                @if ($itemIndex === 0)
                                    {{-- Profit --}}
                                    <x-fi.table.td dense align="end" class="whitespace-nowrap align-top font-semibold tabular-nums text-emerald-600" rowspan="{{ $itemCount }}">
                                        {{ $rupiah($sale->total_profit) }}
                                    </x-fi.table.td>

                                    {{-- Status --}}
                                    <x-fi.table.td dense class="align-top" rowspan="{{ $itemCount }}">
                                        @if ($sale->status === 'Lunas')
                                            <x-fi.badge color="success" icon="check-circle">Lunas</x-fi.badge>
                                        @else
                                            <x-fi.badge color="warning">{{ $sale->status }}</x-fi.badge>
                                        @endif
                                    </x-fi.table.td>

                                    {{-- Aksi: tombol ⋮ (Edit/Bukti/Status hanya untuk yang belum lunas) --}}
                                    <x-fi.table.td dense align="center" class="align-top" rowspan="{{ $itemCount }}">
                                        <x-fi.dropdown label="Aksi {{ $sale->id_sales_recap }}" class="-my-1.5">
                                            @unless ($isLunas)
                                                <x-fi.dropdown.list>
                                                    <x-fi.dropdown.item icon="pencil-square" :href="route('recap-sales.edit', $sale->id_sales_recap)">
                                                        Edit
                                                    </x-fi.dropdown.item>
                                                    <x-fi.dropdown.item icon="arrow-up-tray"
                                                        onclick="openPaymentProofUpload('rekap_penjualan', '{{ $sale->id_sales_recap }}', false)">
                                                        Upload Bukti Bayar
                                                    </x-fi.dropdown.item>
                                                    <x-fi.dropdown.item icon="check-circle" onclick="openModal('statusModal-{{ $sale->id_sales_recap }}')">
                                                        Status Pembayaran
                                                    </x-fi.dropdown.item>
                                                </x-fi.dropdown.list>
                                            @endunless

                                            {{-- Aksi destruktif dipisah di paling bawah --}}
                                            <x-fi.dropdown.list>
                                                <x-fi.dropdown.item icon="trash" color="danger"
                                                    data-delete-row="{{ $sale->id_sales_recap }}"
                                                    data-delete-row-label="{{ $sale->id_sales_recap }}{{ $sale->name_proyek ? ' · ' . $sale->name_proyek : '' }}">
                                                    Hapus
                                                </x-fi.dropdown.item>
                                            </x-fi.dropdown.list>
                                        </x-fi.dropdown>
                                    </x-fi.table.td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                @empty
                    <tbody>
                        <tr>
                            <td colspan="11">
                                @if ($isFiltered)
                                    <x-fi.empty-state icon="magnifying-glass" heading="Data tidak ditemukan"
                                        description="Tidak ada rekap penjualan yang cocok dengan filter/pencarian.">
                                        <x-fi.button color="gray" icon="x-mark" :href="$indexUrl">Reset filter</x-fi.button>
                                    </x-fi.empty-state>
                                @else
                                    <x-fi.empty-state icon="chart-bar" heading="Belum ada rekap penjualan"
                                        description="Klik “Tambah Laporan” untuk membuat rekap penjualan pertama." />
                                @endif
                            </td>
                        </tr>
                    </tbody>
                @endforelse

                {{-- Baris Total (semua data sesuai filter, bukan hanya halaman ini) --}}
                @if ($salesRecaps->isNotEmpty())
                    <tfoot class="border-t border-gray-200 bg-gray-50">
                        <tr>
                            <td colspan="6" class="px-3 py-3 text-end text-sm font-semibold text-gray-950 sm:ps-4">
                                Total Penjualan & Profit
                            </td>
                            <td class="whitespace-nowrap px-3 py-3 text-end text-sm font-semibold tabular-nums text-gray-950">
                                {{ $rupiah($grandTotals->grand_total_capital ?? 0) }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-3 text-end text-sm font-semibold tabular-nums text-gray-950">
                                {{ $rupiah($grandTotals->grand_total_selling ?? 0) }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-3 text-end text-sm font-semibold tabular-nums text-emerald-600">
                                {{ $rupiah($grandTotals->grand_total_profit ?? 0) }}
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </form>

    {{-- Paginasi --}}
    <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
        <x-fi.pagination :paginator="$salesRecaps" />
    </div>
</x-fi.card>
