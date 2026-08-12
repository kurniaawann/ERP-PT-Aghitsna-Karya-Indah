{{-- =====================================================================
     Partial: Laporan Semen (dalam Laporan Akhir)
     Menampilkan data lengkap gabungan DO Semen (header) + Data Semen
     (baris detail) beserta ringkasan statistik. Form disubmit ke route
     report.final dengan tab=cement agar tetap berada di halaman Laporan Akhir.
     Export tetap ke endpoint terpisah (report.cement.export.pdf / excel).
     ===================================================================== --}}
<div class="space-y-6">

    {{-- ==================== FILTER SECTION ==================== --}}
    <div class="bg-surface-base p-6 rounded-xl shadow">
        <form method="GET" action="{{ route('report.final') }}" id="cementFilterForm"
            class="flex flex-col min-[1520px]:flex-row items-stretch min-[1520px]:items-center gap-3">

            {{-- Hidden: pertahankan tab aktif --}}
            <input type="hidden" name="tab" value="cement">

            {{-- Filter Bulan --}}
            <x-filters.month-filter :value="request('month')" responsive="custom" />

            {{-- Filter Tahun --}}
            <x-filters.year-filter :value="request('year')" responsive="custom" />

            {{-- Search (submit manual) --}}
            <x-filters.search-input :value="request('search')" placeholder="Cari nomor DO atau tanggal..." responsive="custom" />
        </form>
    </div>

    {{-- ==================== SUMMARY CARDS ==================== --}}
    <div class="grid grid-cols-1 min-[1272px]:grid-cols-3 min-[1520px]:grid-cols-6 gap-4">
        <div class="bg-surface-base p-4 rounded-xl shadow">
            <p class="text-sm font-medium text-text-secondary">Total DO</p>
            <h3 class="text-2xl font-bold text-primary mt-1">{{ number_format($summary['total_do']) }}</h3>
        </div>
        <div class="bg-surface-base p-4 rounded-xl shadow">
            <p class="text-sm font-medium text-text-secondary">Baris Data Semen</p>
            <h3 class="text-2xl font-bold text-text-primary mt-1">{{ number_format($summary['total_rows']) }}</h3>
        </div>
        <div class="bg-surface-base p-4 rounded-xl shadow">
            <p class="text-sm font-medium text-text-secondary">Total Volume</p>
            <h3 class="text-2xl font-bold text-text-primary mt-1">{{ number_format($summary['total_volume']) }} zak</h3>
        </div>
        <div class="bg-surface-base p-4 rounded-xl shadow">
            <p class="text-sm font-medium text-text-secondary">Total Penjualan</p>
            <h3 class="text-xl font-bold text-text-primary mt-1 truncate">Rp {{ number_format($summary['total_subtotal'], 0, ',', '.') }}</h3>
        </div>
        <div class="bg-surface-base p-4 rounded-xl shadow">
            <p class="text-sm font-medium text-text-secondary">Total Modal</p>
            <h3 class="text-xl font-bold text-warning mt-1 truncate">Rp {{ number_format($summary['total_modal'], 0, ',', '.') }}</h3>
        </div>
        <div class="bg-surface-base p-4 rounded-xl shadow">
            <p class="text-sm font-medium text-text-secondary">Total Profit</p>
            <h3 class="text-xl font-bold text-success mt-1 truncate">Rp {{ number_format($summary['total_profit'], 0, ',', '.') }}</h3>
        </div>
    </div>

    {{-- ==================== DETAIL TABEL (Master-Detail) ==================== --}}
    <div class="bg-surface-base rounded-xl shadow overflow-hidden">
        <div class="p-6 border-b border-border-light">
            <h3 class="text-lg font-semibold text-text-primary">Detail Laporan Semen</h3>
            <p class="text-sm text-text-secondary mt-1">Gabungan data DO Semen beserta seluruh baris Data Semen.</p>
        </div>

        <div class="overflow-x-auto">
            <div class="max-h-[600px] overflow-y-auto">
                <table class="w-full">
                    <thead class="bg-surface-secondary border-b border-border-light sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-text-secondary w-12">No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary">No DO</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary">Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary">Nama Proyek</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-text-secondary">Volume</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-text-secondary">Satuan</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary">Harga</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary">Jumlah</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary">Tgl Lunas</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary">Harga Modal</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary">Profit</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200">
                        @forelse($cementDeliveryOrders as $cementDeliveryOrder)
                            {{-- Baris Header DO --}}
                            <tr class="bg-surface-secondary hover:bg-surface-hover transition-colors">
                                <td class="px-4 py-3 text-center text-sm font-semibold text-primary">{{ $cementDeliveryOrder->no_urutan }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-primary">{{ $cementDeliveryOrder->no }}</td>
                                <td class="px-4 py-3 text-sm text-text-primary">
                                    {{ $cementDeliveryOrder->tanggal?->format('d M Y') ?: '-' }}
                                    <div class="text-xs text-text-secondary">
                                        Datang: {{ $cementDeliveryOrder->tanggal_datang?->format('d M Y') ?: '-' }}
                                        · Bayar: {{ $cementDeliveryOrder->tanggal_bayar?->format('d M Y') ?: '-' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs text-text-secondary">
                                    {{ $cementDeliveryOrder->jumlah_baris }} baris ·
                                    {{ number_format($cementDeliveryOrder->total_volume, 0, ',', '.') }} zak
                                </td>
                                <td class="px-4 py-3 text-center text-text-secondary">-</td>
                                <td class="px-4 py-3 text-center text-text-secondary">-</td>
                                <td class="px-4 py-3 text-right text-text-secondary">-</td>
                                <td class="px-4 py-3 text-right font-medium text-text-primary">
                                    Rp {{ number_format($cementDeliveryOrder->subtotal, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-text-secondary">-</td>
                                <td class="px-4 py-3 text-right text-text-primary">
                                    Rp {{ number_format($cementDeliveryOrder->harga_modal, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-success">
                                    Rp {{ number_format($cementDeliveryOrder->profit, 0, ',', '.') }}
                                </td>
                            </tr>

                            {{-- Baris Detail Data Semen --}}
                            @forelse ($cementDeliveryOrder->cements as $cement)
                                <tr class="bg-white hover:bg-surface-secondary transition-colors">
                                    <td class="px-4 py-3"></td>
                                    <td class="px-4 py-3 pl-8 text-xs text-text-secondary">{{ $cement->no }}</td>
                                    <td class="px-4 py-3 text-sm text-text-primary">{{ $cement->tanggal?->format('d M Y') ?: '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-text-primary">{{ $cement->nama_proyek }}</td>
                                    <td class="px-4 py-3 text-center text-sm text-text-primary">{{ number_format($cement->jumlah, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-center text-sm text-text-secondary">{{ $cement->satuan ?: 'zak' }}</td>
                                    <td class="px-4 py-3 text-right text-sm text-text-primary">Rp {{ number_format($cement->harga, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right text-sm font-medium text-text-primary">Rp {{ number_format($cement->total, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm text-text-primary">{{ $cement->tanggal_lunas?->format('d M Y') ?: '-' }}</td>
                                    <td class="px-4 py-3 text-center text-sm text-text-secondary">-</td>
                                    <td class="px-4 py-3 text-right text-sm text-success">Rp {{ number_format($cement->profit, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr class="bg-white">
                                    <td colspan="11" class="px-4 py-3 pl-8 text-xs text-text-secondary italic">
                                        Tidak ada data semen dalam DO ini.
                                    </td>
                                </tr>
                            @endforelse
                        @empty
                            <tr>
                                <td colspan="11" class="px-6 py-12 text-center">
                                    <p class="text-text-secondary mb-0">
                                        <i class="fas fa-inbox mr-2"></i>Tidak ada data semen untuk periode ini
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-border-light">
            {{ $cementDeliveryOrders->appends(request()->query())->links() }}
        </div>
    </div>

</div>
