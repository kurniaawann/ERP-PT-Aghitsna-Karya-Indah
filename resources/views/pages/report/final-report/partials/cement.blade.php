{{-- =====================================================================
     Partial: Laporan Semen (dalam Laporan Akhir, gaya Filament)
     Menampilkan data lengkap gabungan DO Semen (header) + Data Semen
     (baris detail) beserta ringkasan statistik. Form disubmit ke route
     report.final dengan tab=cement agar tetap berada di halaman Laporan Akhir.
     Export tetap ke endpoint terpisah (report.cement.export.pdf / excel) lewat
     tombol Export di header halaman.
     ===================================================================== --}}
@php
    $qty = fn ($value) => number_format($value ?? 0, 0, ',', '.');
    $rupiah = fn ($value) => 'Rp ' . number_format($value ?? 0, 0, ',', '.');
@endphp

<div class="flex flex-col gap-y-6">

    {{-- SECTION: Filter (bulan, tahun, pencarian) --}}
    <x-report.final-report.filter-bar tab="cement" formId="cementFilterForm" :keys="['month', 'year', 'search']">
        <div class="sm:w-44">
            <x-fi.month-select :value="request('month')" onchange="this.form.requestSubmit()" />
        </div>
        <div class="sm:w-36">
            <x-fi.year-select :value="request('year')" onchange="this.form.requestSubmit()" />
        </div>
        <x-fi.search-input :value="request('search')" placeholder="Cari nomor DO atau tanggal..." class="col-span-2 sm:w-72" />
    </x-report.final-report.filter-bar>

    {{-- SECTION: Kartu Ringkasan --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
        <x-fi.stat label="Total DO" icon="truck" color="primary">{{ $qty($summary['total_do']) }}</x-fi.stat>
        <x-fi.stat label="Baris Data Semen" icon="table-cells">{{ $qty($summary['total_rows']) }}</x-fi.stat>
        <x-fi.stat label="Total Volume" icon="cube">
            {{ $qty($summary['total_volume']) }} <span class="text-base font-medium text-gray-500">zak</span>
        </x-fi.stat>
        <x-fi.stat label="Total Penjualan" icon="chart-bar" size="sm">{{ $rupiah($summary['total_subtotal']) }}</x-fi.stat>
        <x-fi.stat label="Total Modal" icon="banknotes" color="warning" size="sm">{{ $rupiah($summary['total_modal']) }}</x-fi.stat>
        <x-fi.stat label="Total Profit" icon="currency-dollar" color="success" size="sm">{{ $rupiah($summary['total_profit']) }}</x-fi.stat>
    </div>

    {{-- SECTION: Detail Tabel (Master-Detail: baris DO + baris Data Semen) --}}
    <x-fi.section heading="Detail Laporan Semen" description="Gabungan data DO Semen beserta seluruh baris Data Semen." flush>
        <div class="relative max-h-[600px] overflow-auto">
            <table class="w-full table-auto divide-y divide-gray-200 text-start">
                <thead class="sticky top-0 z-10 bg-gray-50">
                    <tr>
                        <x-fi.table.th dense align="center" class="w-1">No</x-fi.table.th>
                        <x-fi.table.th dense>No DO</x-fi.table.th>
                        <x-fi.table.th dense>Tanggal</x-fi.table.th>
                        <x-fi.table.th dense>Nama Proyek</x-fi.table.th>
                        <x-fi.table.th dense align="center">Volume</x-fi.table.th>
                        <x-fi.table.th dense align="center">Satuan</x-fi.table.th>
                        <x-fi.table.th dense align="end">Harga</x-fi.table.th>
                        <x-fi.table.th dense align="end">Jumlah</x-fi.table.th>
                        <x-fi.table.th dense>Tgl Lunas</x-fi.table.th>
                        <x-fi.table.th dense align="end">Harga Modal</x-fi.table.th>
                        <x-fi.table.th dense align="end">Profit</x-fi.table.th>
                    </tr>
                </thead>

                @forelse ($cementDeliveryOrders as $cementDeliveryOrder)
                    @php
                        $doDates = array_filter([
                            $cementDeliveryOrder->tanggal_datang ? 'Datang: ' . $cementDeliveryOrder->tanggal_datang->format('d M Y') : null,
                            $cementDeliveryOrder->tanggal_bayar ? 'Bayar: ' . $cementDeliveryOrder->tanggal_bayar->format('d M Y') : null,
                        ]);
                    @endphp
                    <tbody class="divide-y divide-gray-100 whitespace-nowrap border-b border-gray-200 text-gray-950">
                        {{-- Baris Header DO --}}
                        <tr class="bg-gray-50">
                            <x-fi.table.td dense align="center" class="font-semibold tabular-nums text-primary">{{ $cementDeliveryOrder->no_urutan }}</x-fi.table.td>
                            <x-fi.table.td dense class="font-semibold text-primary">{{ $cementDeliveryOrder->no }}</x-fi.table.td>
                            <x-fi.table.td dense>
                                <div class="font-medium">{{ $cementDeliveryOrder->tanggal?->format('d M Y') }}</div>
                                @foreach ($doDates as $doDate)
                                    <div class="text-xs text-gray-500">{{ $doDate }}</div>
                                @endforeach
                            </x-fi.table.td>
                            <x-fi.table.td dense class="text-xs text-gray-500">
                                {{ $cementDeliveryOrder->jumlah_baris }} baris · {{ $qty($cementDeliveryOrder->total_volume) }} zak
                            </x-fi.table.td>
                            <x-fi.table.td dense></x-fi.table.td>
                            <x-fi.table.td dense></x-fi.table.td>
                            <x-fi.table.td dense></x-fi.table.td>
                            <x-fi.table.td dense align="end" class="font-semibold tabular-nums">{{ $rupiah($cementDeliveryOrder->subtotal) }}</x-fi.table.td>
                            <x-fi.table.td dense></x-fi.table.td>
                            <x-fi.table.td dense align="end" class="tabular-nums">{{ $rupiah($cementDeliveryOrder->harga_modal) }}</x-fi.table.td>
                            <x-fi.table.td dense align="end" class="font-semibold tabular-nums text-emerald-600">
                                {{-- Profit minus (harga modal > penjualan) tidak ditampilkan --}}
                                @if ($cementDeliveryOrder->profit >= 0)
                                    {{ $rupiah($cementDeliveryOrder->profit) }}
                                @endif
                            </x-fi.table.td>
                        </tr>

                        {{-- Baris Detail Data Semen --}}
                        @forelse ($cementDeliveryOrder->cements as $cement)
                            <tr class="bg-white transition duration-75 hover:bg-gray-50">
                                <x-fi.table.td dense></x-fi.table.td>
                                <x-fi.table.td dense class="ps-6 text-xs text-gray-500">
                                    <span class="inline-flex items-center gap-x-1">
                                        <x-fi.icon name="arrow-turn-down-right" class="h-3.5 w-3.5 text-gray-300" />
                                        {{ $cement->no }}
                                    </span>
                                </x-fi.table.td>
                                <x-fi.table.td dense>{{ $cement->tanggal?->format('d M Y') ?: '' }}</x-fi.table.td>
                                <x-fi.table.td dense class="min-w-[10rem] whitespace-normal">{{ $cement->nama_proyek }}</x-fi.table.td>
                                <x-fi.table.td dense align="center" class="tabular-nums">{{ $qty($cement->jumlah) }}</x-fi.table.td>
                                <x-fi.table.td dense align="center" class="text-gray-500">{{ $cement->satuan ?: 'zak' }}</x-fi.table.td>
                                <x-fi.table.td dense align="end" class="tabular-nums">{{ $rupiah($cement->harga) }}</x-fi.table.td>
                                <x-fi.table.td dense align="end" class="font-medium tabular-nums">{{ $rupiah($cement->total) }}</x-fi.table.td>
                                <x-fi.table.td dense>{{ $cement->tanggal_lunas?->format('d M Y') ?: '' }}</x-fi.table.td>
                                <x-fi.table.td dense></x-fi.table.td>
                                {{-- Profit hanya dihitung per DO (harga modal milik DO), bukan per baris --}}
                                <x-fi.table.td dense></x-fi.table.td>
                            </tr>
                        @empty
                            <tr class="bg-white">
                                <td colspan="11" class="px-2 py-3 ps-6 text-xs italic text-gray-500 sm:ps-10">
                                    Tidak ada data semen dalam DO ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                @empty
                    <tbody>
                        <tr>
                            <td colspan="11">
                                <x-fi.empty-state icon="inbox" heading="Tidak ada data semen untuk periode ini" />
                            </td>
                        </tr>
                    </tbody>
                @endforelse
            </table>
        </div>

        {{-- Paginasi --}}
        <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
            <x-fi.pagination :paginator="$cementDeliveryOrders" />
        </div>
    </x-fi.section>
</div>
