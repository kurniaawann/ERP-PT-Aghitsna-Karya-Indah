{{-- Table Invoice Barang (gaya Filament).
     Toolbar (x-finance.invoice-table-toolbar): pencarian + filter bulan /
     tahun / status, lalu bar aksi massal (muncul saat ada invoice dicentang);
     paginasi di bawah. Jumlah invoice terpilih dihitung Alpine; status
     disabled #delete-button tetap diatur item-invoices/index.js.

     Hook JS (item-invoices/index.js): form#deleteForm, #selectAll,
     input[name="selected_invoices[]"], #delete-button. --}}
@props(['invoices'])

@php
    $isFiltered = request()->filled('search') || request()->filled('month') || request()->filled('year') || request()->filled('status');
@endphp

<x-fi.card x-data="{ selected: 0 }"
    x-init="selected = $el.querySelectorAll('[name^=selected_invoices]:checked').length"
    x-on:change="selected = $el.querySelectorAll('[name^=selected_invoices]:checked').length">

    {{-- Toolbar: pencarian (kiri) + filter (kanan), bar aksi massal saat ada yang dicentang --}}
    <x-finance.invoice-table-toolbar :action="route('item-invoice.index')"
        searchPlaceholder="Cari invoice, penerima, keterangan..." />

    <form id="deleteForm" method="POST" action="{{ route('item-invoice.destroySelected') }}">
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
                        <x-fi.table.th>No Invoice</x-fi.table.th>
                        <x-fi.table.th>Tanggal</x-fi.table.th>
                        <x-fi.table.th>Kepada</x-fi.table.th>
                        <x-fi.table.th>Keterangan</x-fi.table.th>
                        <x-fi.table.th align="end">Total</x-fi.table.th>
                        <x-fi.table.th>Status</x-fi.table.th>
                        <x-fi.table.th align="center" class="z-10 bg-gray-50 md:sticky md:right-0 md:shadow-[inset_1px_0_0_0_theme(colors.gray.200)]">Aksi</x-fi.table.th>
                    </tr>
                </thead>

                {{-- Body Tabel --}}
                <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                    @forelse ($invoices as $invoice)
                        @php
                            $isLunas = $invoice->salesRecap?->status === 'Lunas';
                            $paid = $invoice->getTotalPaidAmount();
                            $fullyPaid = $invoice->isFullyPaid();
                        @endphp

                        <tr class="bg-white transition duration-75 hover:bg-gray-50 has-[:checked]:bg-blue-50">
                            <td class="w-1 px-2 py-4 sm:ps-4">
                                <input type="checkbox" name="selected_invoices[]" value="{{ $invoice->invoice_number }}"
                                    class="fi-checkbox" title="Pilih {{ $invoice->invoice_number }}">
                            </td>

                            <x-fi.table.td>
                                <button type="button" onclick="openModal('detailModal-{{ $invoice->invoice_number }}')"
                                    class="font-medium text-primary hover:underline" title="Lihat detail">
                                    {{ $invoice->invoice_number }}
                                </button>
                            </x-fi.table.td>

                            <x-fi.table.td>{{ $invoice->invoice_date?->format('d M Y') }}</x-fi.table.td>

                            <x-fi.table.td class="min-w-[10rem] whitespace-normal">
                                <div class="font-medium">{{ $invoice->recipient }}</div>
                                @if ($invoice->regarding)
                                    <div class="text-sm text-gray-500">{{ $invoice->regarding }}</div>
                                @endif
                            </x-fi.table.td>

                            <x-fi.table.td class="min-w-[12rem] max-w-xs whitespace-normal text-gray-700">
                                {{ $invoice->project_description ?: '-' }}
                            </x-fi.table.td>

                            <x-fi.table.td align="end" class="tabular-nums">
                                <div class="font-semibold">Rp {{ number_format($invoice->getNetAmount(), 0, ',', '.') }}</div>
                                @if (! $isLunas && $paid > 0)
                                    <div class="text-sm text-gray-500">Terbayar Rp {{ number_format($paid, 0, ',', '.') }}</div>
                                @endif
                            </x-fi.table.td>

                            <x-fi.table.td>
                                <x-fi.badge :color="$isLunas ? 'success' : 'warning'">{{ $invoice->status_label }}</x-fi.badge>
                            </x-fi.table.td>

                            {{-- Aksi: tombol ⋮ (sticky di kanan agar tetap terlihat saat tabel di-scroll) --}}
                            <x-fi.table.td align="center" class="bg-inherit md:sticky md:right-0 md:shadow-[inset_1px_0_0_0_theme(colors.gray.200)]">
                                <x-fi.dropdown label="Aksi {{ $invoice->invoice_number }}" width="w-56" class="-my-1.5">
                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="eye" onclick="openModal('detailModal-{{ $invoice->invoice_number }}')">
                                            Lihat Detail
                                        </x-fi.dropdown.item>
                                        {{-- Invoice lunas tetap dibuka di halaman edit (terkunci) untuk mengelola bukti bayar --}}
                                        <x-fi.dropdown.item :icon="$isLunas ? 'lock-closed' : 'pencil-square'"
                                            :href="route('item-invoice.edit', $invoice->invoice_number)">
                                            {{ $isLunas ? 'Buka (sudah lunas)' : 'Edit' }}
                                        </x-fi.dropdown.item>
                                        <x-fi.dropdown.item icon="arrow-up-tray"
                                            onclick="openPaymentProofUpload('barang', '{{ addslashes($invoice->invoice_number) }}', false)"
                                            :disabled="$fullyPaid" :hint="$fullyPaid ? 'Pembayaran sudah penuh' : null">
                                            Upload Bukti Bayar
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>

                                    <x-fi.dropdown.list label="Cetak">
                                        <x-fi.dropdown.item icon="document-text" :href="route('item-invoice.print.pdf', $invoice->invoice_number)">
                                            PDF
                                        </x-fi.dropdown.item>
                                        <x-fi.dropdown.item icon="table-cells" :href="route('item-invoice.print.excel', $invoice->invoice_number)">
                                            Excel
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>

                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="trash" color="danger"
                                            data-delete-row="{{ $invoice->invoice_number }}"
                                            data-delete-row-label="invoice {{ $invoice->invoice_number }}">
                                            Hapus
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>
                                </x-fi.dropdown>
                            </x-fi.table.td>
                        </tr>
                    @empty
                        {{-- Pesan jika data kosong --}}
                        <tr>
                            <td colspan="8">
                                @if ($isFiltered)
                                    <x-fi.empty-state icon="magnifying-glass" heading="Data tidak ditemukan"
                                        description="Tidak ada invoice barang yang cocok dengan filter/pencarian.">
                                        <x-fi.button color="gray" icon="x-mark" :href="route('item-invoice.index')">Reset filter</x-fi.button>
                                    </x-fi.empty-state>
                                @else
                                    <x-fi.empty-state icon="document-text" heading="Belum ada invoice barang"
                                        description="Klik “Tambah Invoice” untuk membuat invoice pertama." />
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
