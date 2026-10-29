{{-- Tabel Rekap Alumunium (gaya Filament): toolbar filter bulan / tahun +
     pencarian (kanan), tabel invoice, baris total, paginasi. Hanya baca —
     detail invoice dibuka lewat modal x-finance.aluminium-invoices.detail-modal.

     Props:
     - invoices  : Paginator InvoiceAlumunium
     - totals    : ringkasan (total_invoice, invoice_count, paid_count)
     - indexUrl  : URL halaman ini (tujuan filter & Reset)
     - filterTab : key tab halaman Rekap (hidden input "tab"), null di halaman sendiri --}}
@props(['invoices', 'totals', 'indexUrl', 'filterTab' => null])

@php
    $isFiltered = request()->filled('search') || request()->filled('month') || request()->filled('year');
@endphp

<x-fi.card>
    {{-- Toolbar: filter & pencarian (kanan). Bulan/tahun langsung submit saat diganti, pencarian saat Enter. --}}
    <div class="flex flex-wrap items-center gap-3 border-b border-gray-200 px-4 py-3 sm:px-6">
        <form method="GET" action="{{ $filterTab ? route('rekap.index') : $indexUrl }}"
            class="grid w-full grid-cols-2 gap-3 sm:flex sm:flex-wrap sm:items-center sm:justify-end xl:ms-auto xl:w-auto">
            @if ($filterTab)
                <input type="hidden" name="tab" value="{{ $filterTab }}">
            @endif

            <div class="sm:w-44">
                <x-fi.month-select :value="request('month')" onchange="this.form.requestSubmit()" />
            </div>
            <div class="sm:w-36">
                <x-fi.year-select :value="request('year')" onchange="this.form.requestSubmit()" />
            </div>
            <x-fi.search-input :value="request('search')" placeholder="Cari invoice, penerima, atau proyek..."
                class="col-span-2 sm:w-72" />

            @if ($isFiltered)
                <x-fi.link :href="$indexUrl" color="gray" icon="x-mark"
                    class="col-span-2 justify-self-start whitespace-nowrap" title="Hapus semua filter & pencarian">
                    Reset
                </x-fi.link>
            @endif
        </form>
    </div>

    <div class="relative overflow-x-auto">
        <table class="w-full table-auto divide-y divide-gray-200 text-start">

            {{-- Header Tabel --}}
            <thead class="bg-gray-50">
                <tr>
                    <x-fi.table.th align="center" class="w-1">No</x-fi.table.th>
                    <x-fi.table.th>No Invoice</x-fi.table.th>
                    <x-fi.table.th>Tanggal</x-fi.table.th>
                    <x-fi.table.th>Kepada</x-fi.table.th>
                    <x-fi.table.th>Proyek</x-fi.table.th>
                    <x-fi.table.th align="end">Total Invoice</x-fi.table.th>
                    <x-fi.table.th>Status</x-fi.table.th>
                    <x-fi.table.th align="center">Aksi</x-fi.table.th>
                </tr>
            </thead>

            {{-- Body Tabel --}}
            <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                @forelse ($invoices as $index => $invoice)
                    @php
                        // Lunas → hijau; sudah ada pembayaran → kuning; belum ada pembayaran → merah
                        $statusColor = $invoice->isFullyPaid() ? 'success' : ($invoice->getTotalPaidAmount() > 0 ? 'warning' : 'danger');
                    @endphp

                    <tr class="bg-white transition duration-75 hover:bg-gray-50">
                        <x-fi.table.td align="center" class="tabular-nums text-gray-500">{{ $invoices->firstItem() + $index }}</x-fi.table.td>

                        <x-fi.table.td>
                            <button type="button" onclick="openModal('detailModal-{{ $invoice->invoice_number }}')"
                                class="font-medium text-primary hover:underline" title="Lihat detail">
                                {{ $invoice->invoice_number }}
                            </button>
                        </x-fi.table.td>

                        <x-fi.table.td>{{ $invoice->invoice_date->format('d M Y') }}</x-fi.table.td>

                        <x-fi.table.td class="min-w-[10rem] whitespace-normal font-medium">{{ $invoice->recipient }}</x-fi.table.td>

                        <x-fi.table.td class="min-w-[10rem] max-w-xs whitespace-normal text-gray-700">
                            <div class="line-clamp-2" title="{{ $invoice->project_description }}">
                                {{ $invoice->project_description ? \Illuminate\Support\Str::limit($invoice->project_description, 35) : '-' }}
                            </div>
                        </x-fi.table.td>

                        <x-fi.table.td align="end" class="font-semibold tabular-nums">
                            Rp {{ number_format($invoice->getNetAmount(), 0, ',', '.') }}
                        </x-fi.table.td>

                        <x-fi.table.td>
                            <x-fi.badge :color="$statusColor">{{ $invoice->payment_status_label }}</x-fi.badge>
                        </x-fi.table.td>

                        {{-- Aksi: tombol ⋮ --}}
                        <x-fi.table.td align="center">
                            <x-fi.dropdown label="Aksi {{ $invoice->invoice_number }}" class="-my-1.5">
                                <x-fi.dropdown.list>
                                    <x-fi.dropdown.item icon="eye" onclick="openModal('detailModal-{{ $invoice->invoice_number }}')">
                                        Lihat Detail
                                    </x-fi.dropdown.item>
                                </x-fi.dropdown.list>
                            </x-fi.dropdown>
                        </x-fi.table.td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            @if ($isFiltered)
                                <x-fi.empty-state icon="magnifying-glass" heading="Data tidak ditemukan"
                                    description="Data rekap alumunium tidak ditemukan untuk filter/pencarian ini.">
                                    <x-fi.button color="gray" icon="x-mark" :href="$indexUrl">Reset filter</x-fi.button>
                                </x-fi.empty-state>
                            @else
                                <x-fi.empty-state icon="document-currency-dollar" heading="Data rekap alumunium tidak ditemukan"
                                    description="Rekap terisi otomatis dari Invoice Alumunium." />
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>

            {{-- Baris Total (seluruh data periode, bukan hanya halaman ini) --}}
            @if ($invoices->isNotEmpty())
                <tfoot class="border-t border-gray-200 bg-gray-50">
                    <tr>
                        <td colspan="5" class="px-3 py-3 text-end text-sm font-semibold text-gray-950 sm:ps-6">Total Rekap Alumunium</td>
                        <td class="whitespace-nowrap px-3 py-3 text-end text-sm font-semibold tabular-nums text-gray-950">
                            Rp {{ number_format($totals->total_invoice ?? 0, 0, ',', '.') }}
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    {{-- Paginasi --}}
    <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
        <x-fi.pagination :paginator="$invoices" />
    </div>
</x-fi.card>
