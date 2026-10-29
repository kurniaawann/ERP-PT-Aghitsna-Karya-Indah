{{-- Table Faktur Pembelian (gaya Filament).
     Toolbar: aksi massal (muncul saat ada faktur dicentang) + filter bulan /
     tahun + pencarian; paginasi di bawah. Jumlah faktur terpilih dihitung
     Alpine untuk menampilkan grup aksi massal; status disabled #delete-button
     tetap diatur purchase-invoices/index.js.

     Hook JS (purchase-invoices/index.js): form#deleteForm, #selectAll,
     input[name="selected_invoices[]"], #delete-button. --}}
@props(['invoices'])

@php
    $isFiltered = request()->filled('search') || request()->filled('month') || request()->filled('year');
    $formatPercent = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
@endphp

<x-fi.card x-data="{ selected: 0 }"
    x-init="selected = $el.querySelectorAll('[name^=selected_invoices]:checked').length"
    x-on:change="selected = $el.querySelectorAll('[name^=selected_invoices]:checked').length">

    {{-- Toolbar --}}
    <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-3 lg:flex-row lg:items-center lg:justify-between sm:px-6">
        {{-- Aksi massal: hanya tampil saat ada faktur yang dicentang --}}
        <div x-cloak x-show="selected > 0" class="flex shrink-0 items-center gap-3">
            <span class="whitespace-nowrap text-sm text-gray-500">
                <span class="font-semibold text-gray-950" x-text="selected"></span> faktur dipilih
            </span>

            <x-fi.button id="delete-button" color="danger" size="sm" icon="trash" outlined disabled
                onclick="openModal('deleteModal')">
                Hapus yang dipilih
            </x-fi.button>
        </div>

        {{-- Form Filter & Pencarian: submit GET ke route('purchase-invoice.index').
             Bulan/tahun langsung submit saat diganti; pencarian saat Enter. --}}
        <form method="GET" action="{{ route('purchase-invoice.index') }}" id="filterForm"
            class="grid w-full grid-cols-2 gap-3 sm:flex sm:flex-wrap sm:items-center lg:ms-auto lg:w-auto lg:justify-end">
            <div class="sm:w-44">
                <x-fi.month-select :value="request('month')" onchange="this.form.requestSubmit()" />
            </div>
            <div class="sm:w-36">
                <x-fi.year-select :value="request('year')" onchange="this.form.requestSubmit()" />
            </div>
            <x-fi.search-input :value="request('search')" placeholder="Cari material, barang, atau NPWP..."
                class="col-span-2 sm:w-72" />

            @if ($isFiltered)
                <x-fi.link :href="route('purchase-invoice.index')" color="gray" icon="x-mark"
                    class="col-span-2 justify-self-start" title="Hapus semua filter & pencarian">
                    Reset
                </x-fi.link>
            @endif
        </form>
    </div>

    <form id="deleteForm" method="POST" action="{{ route('purchase-invoice.destroy-selected') }}">
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
                        <x-fi.table.th dense>Tanggal</x-fi.table.th>
                        <x-fi.table.th dense>Nama Material</x-fi.table.th>
                        <x-fi.table.th dense>Kode Seri Pajak</x-fi.table.th>
                        <x-fi.table.th dense>Nama Barang</x-fi.table.th>
                        <x-fi.table.th dense align="end">Harga Jual</x-fi.table.th>
                        <x-fi.table.th dense align="end">PPN</x-fi.table.th>
                        <x-fi.table.th dense align="end">Total</x-fi.table.th>
                        <x-fi.table.th dense align="center" class="z-10 bg-gray-50 md:sticky md:right-0 md:shadow-[inset_1px_0_0_0_theme(colors.gray.200)]">Aksi</x-fi.table.th>
                    </tr>
                </thead>

                {{-- Body Tabel --}}
                <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                    @forelse ($invoices as $invoice)
                        <tr class="bg-white transition duration-75 hover:bg-gray-50 has-[:checked]:bg-blue-50">
                            <td class="w-1 px-2 py-3 sm:ps-4">
                                <input type="checkbox" name="selected_invoices[]" value="{{ $invoice->id }}"
                                    class="fi-checkbox" title="Pilih faktur {{ $invoice->tax_number_code }}">
                            </td>

                            <x-fi.table.td dense>{{ $invoice->date?->format('d M Y') }}</x-fi.table.td>

                            {{-- Nama Material + NPWP --}}
                            <x-fi.table.td dense class="min-w-[12rem] whitespace-normal">
                                <div class="font-medium">{{ $invoice->material_name }}</div>
                                <div class="whitespace-nowrap text-sm text-gray-500">NPWP {{ $invoice->npwp }}</div>
                            </x-fi.table.td>

                            <x-fi.table.td dense class="tabular-nums text-gray-700">{{ $invoice->tax_number_code }}</x-fi.table.td>

                            {{-- Nama Barang + Keterangan --}}
                            <x-fi.table.td dense class="min-w-[10rem] whitespace-normal">
                                <div class="font-medium">{{ $invoice->item_name }}</div>
                                @if ($invoice->notes)
                                    <div class="line-clamp-2 text-sm text-gray-500" title="{{ $invoice->notes }}">{{ $invoice->notes }}</div>
                                @endif
                            </x-fi.table.td>

                            <x-fi.table.td dense align="end" class="tabular-nums">
                                Rp {{ number_format($invoice->selling_price, 0, ',', '.') }}
                            </x-fi.table.td>

                            {{-- PPN + persentase --}}
                            <x-fi.table.td dense align="end" class="tabular-nums">
                                <div>Rp {{ number_format($invoice->ppn_tax, 0, ',', '.') }}</div>
                                <div class="text-sm text-gray-500">{{ $formatPercent($invoice->ppn_percentage) }}%</div>
                            </x-fi.table.td>

                            <x-fi.table.td dense align="end" class="font-semibold tabular-nums">
                                Rp {{ number_format($invoice->total, 0, ',', '.') }}
                            </x-fi.table.td>

                            {{-- Aksi: tombol ⋮ (sticky di kanan agar tetap terlihat saat tabel di-scroll) --}}
                            <x-fi.table.td dense align="center" class="bg-inherit md:sticky md:right-0 md:shadow-[inset_1px_0_0_0_theme(colors.gray.200)]">
                                <x-fi.dropdown label="Aksi faktur {{ $invoice->tax_number_code }}" width="w-48" class="-my-1.5">
                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="pencil-square" onclick="openModal('editModal-{{ $invoice->id }}')">
                                            Edit
                                        </x-fi.dropdown.item>
                                        <x-fi.dropdown.item icon="printer" :href="route('purchase-invoice.pdf', $invoice->id)">
                                            Cetak PDF
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>

                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="trash" color="danger"
                                            data-delete-row="{{ $invoice->id }}"
                                            data-delete-row-label="faktur {{ $invoice->tax_number_code }} ({{ $invoice->item_name }})">
                                            Hapus
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>
                                </x-fi.dropdown>
                            </x-fi.table.td>
                        </tr>
                    @empty
                        {{-- Pesan jika data kosong --}}
                        <tr>
                            <td colspan="9">
                                @if ($isFiltered)
                                    <x-fi.empty-state icon="magnifying-glass" heading="Data tidak ditemukan"
                                        description="Tidak ada faktur pembelian yang cocok dengan filter/pencarian.">
                                        <x-fi.button color="gray" icon="x-mark" :href="route('purchase-invoice.index')">Reset filter</x-fi.button>
                                    </x-fi.empty-state>
                                @else
                                    <x-fi.empty-state icon="document-text" heading="Belum ada faktur pembelian"
                                        description="Klik “Tambah Faktur” untuk mencatat faktur pembelian pertama." />
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    {{-- Paginasi --}}
    <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
        <x-fi.pagination :paginator="$invoices" />
    </div>
</x-fi.card>
