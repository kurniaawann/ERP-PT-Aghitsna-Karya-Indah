{{-- =====================================================================
     Partial: Laporan Penjualan (dalam Laporan Akhir, gaya Filament)
     Logika/data sama dengan halaman Laporan Penjualan lama (SalesReportController),
     hanya form disubmit ke route report.final dengan tab=sales agar tetap
     berada di halaman Laporan Akhir. Export tetap ke endpoint terpisah
     (report.sales.export.pdf / report.sales.export.excel) lewat tombol Export
     di header halaman.

     Hook JS (report/sales-reports/index.js): canvas #monthlySalesChart &
     #statusDistributionChart + window.monthlyTrendData / statusDistributionData.
     ===================================================================== --}}
@php
    $rupiah = fn ($value) => 'Rp ' . number_format($value ?? 0, 0, ',', '.');
    // Badge status pembayaran (sama seperti x-report.sales-reports.status-badge)
    $statusBadge = fn ($status) => $status === 'Lunas'
        ? ['color' => 'success', 'icon' => 'check-circle', 'label' => 'Lunas']
        : ['color' => 'warning', 'icon' => null, 'label' => 'Belum Lunas'];
@endphp

<div class="flex flex-col gap-y-6">

    {{-- SECTION: Filter (bulan, tahun, status, pencarian) --}}
    <x-report.final-report.filter-bar tab="sales">
        <div class="sm:w-44">
            <x-fi.month-select :value="request('month')" onchange="this.form.requestSubmit()" />
        </div>
        <div class="sm:w-36">
            <x-fi.year-select :value="request('year')" onchange="this.form.requestSubmit()" />
        </div>
        <div class="col-span-2 sm:w-44">
            <x-fi.select name="status" aria-label="Filter Status" placeholder="Semua Status"
                :options="['Lunas' => 'Lunas', 'Belum Lunas' => 'Belum Lunas']" :value="request('status')"
                onchange="this.form.requestSubmit()" data-fi-select />
        </div>
        <x-fi.search-input :value="request('search')" placeholder="Cari proyek..." class="col-span-2 sm:w-64" />
    </x-report.final-report.filter-bar>

    {{-- SECTION: Kartu Ringkasan --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-fi.stat label="Total Penjualan" icon="chart-bar" color="primary" size="sm"
            :description="$summary['total_transactions'] . ' transaksi'">
            {{ $rupiah($summary['total_selling']) }}
        </x-fi.stat>
        <x-fi.stat label="Total Modal" icon="banknotes" color="warning" size="sm" description="HPP">
            {{ $rupiah($summary['total_capital']) }}
        </x-fi.stat>
        <x-fi.stat label="Total Profit" icon="currency-dollar" color="success" size="sm"
            :description="'Margin: ' . $summary['profit_margin'] . '%'">
            {{ $rupiah($summary['total_profit']) }}
        </x-fi.stat>
        <x-fi.stat label="Rata-rata/Transaksi" icon="calculator" size="sm" description="Per transaksi">
            {{ $rupiah($summary['avg_transaction']) }}
        </x-fi.stat>
    </div>

    {{-- SECTION: Grafik --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-fi.section heading="Trend Penjualan Bulanan">
            <div class="relative h-[300px]">
                <canvas id="monthlySalesChart"></canvas>
            </div>
        </x-fi.section>

        <x-fi.section heading="Status Pembayaran">
            <div class="relative h-[300px]">
                <canvas id="statusDistributionChart"></canvas>
            </div>
        </x-fi.section>
    </div>

    {{-- SECTION: Ringkasan Status Pembayaran --}}
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        @foreach ([
            ['Transaksi Lunas', 'bg-emerald-500', $summary['paid_count'], 'Total Nilai', $summary['paid_amount'], 'text-emerald-600'],
            ['Transaksi Belum Lunas', 'bg-red-500', $summary['unpaid_count'], 'Total Piutang', $summary['unpaid_amount'], 'text-red-600'],
        ] as [$statusHeading, $statusDot, $statusCount, $statusAmountLabel, $statusAmount, $statusAmountColor])
            <x-fi.card class="p-6">
                <h3 class="flex items-center gap-x-2 text-base font-semibold text-gray-950">
                    <span class="h-2.5 w-2.5 rounded-full {{ $statusDot }}"></span>
                    {{ $statusHeading }}
                </h3>
                <dl class="mt-4 grid gap-y-2 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-500">Jumlah Transaksi:</dt>
                        <dd class="font-semibold tabular-nums text-gray-950">{{ $statusCount }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-500">{{ $statusAmountLabel }}:</dt>
                        <dd class="font-semibold tabular-nums {{ $statusAmountColor }}">{{ $rupiah($statusAmount) }}</dd>
                    </div>
                </dl>
            </x-fi.card>
        @endforeach
    </div>

    {{-- SECTION: Top 5 Proyek --}}
    <x-fi.section heading="Top 5 Proyek Terbaik (Berdasarkan Profit)" flush>
        <div class="relative overflow-x-auto">
            <table class="w-full table-auto divide-y divide-gray-200 text-start">
                <thead class="bg-gray-50">
                    <tr>
                        <x-fi.table.th align="center" class="w-1">Ranking</x-fi.table.th>
                        <x-fi.table.th>Tanggal</x-fi.table.th>
                        <x-fi.table.th>Nama Proyek</x-fi.table.th>
                        <x-fi.table.th align="end">Penjualan</x-fi.table.th>
                        <x-fi.table.th align="end">Modal</x-fi.table.th>
                        <x-fi.table.th align="end">Profit</x-fi.table.th>
                        <x-fi.table.th>Status</x-fi.table.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                    @forelse ($topProjects as $index => $project)
                        @php $badge = $statusBadge($project->status); @endphp
                        <tr class="bg-white transition duration-75 hover:bg-gray-50">
                            <x-fi.table.td align="center">
                                @if ($index == 0)
                                    <span class="text-2xl" title="Peringkat 1">&#x1F947;</span>
                                @elseif ($index == 1)
                                    <span class="text-2xl" title="Peringkat 2">&#x1F948;</span>
                                @elseif ($index == 2)
                                    <span class="text-2xl" title="Peringkat 3">&#x1F949;</span>
                                @else
                                    <span class="font-semibold tabular-nums text-gray-500">{{ $index + 1 }}</span>
                                @endif
                            </x-fi.table.td>
                            <x-fi.table.td>{{ \Carbon\Carbon::parse($project->date)->format('d M Y') }}</x-fi.table.td>
                            <x-fi.table.td class="min-w-[12rem] whitespace-normal">
                                <div class="font-medium">{{ $project->name_proyek }}</div>
                                <div class="text-xs text-gray-500">{{ $project->no_faktur }}</div>
                            </x-fi.table.td>
                            <x-fi.table.td align="end" class="tabular-nums">{{ $rupiah($project->total_selling) }}</x-fi.table.td>
                            <x-fi.table.td align="end" class="tabular-nums">{{ $rupiah($project->total_capital) }}</x-fi.table.td>
                            <x-fi.table.td align="end" class="font-semibold tabular-nums text-emerald-600">{{ $rupiah($project->total_profit) }}</x-fi.table.td>
                            <x-fi.table.td>
                                <x-fi.badge :color="$badge['color']" :icon="$badge['icon']">{{ $badge['label'] }}</x-fi.badge>
                            </x-fi.table.td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <x-fi.empty-state icon="chart-bar" heading="Tidak ada data proyek" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-fi.section>

    {{-- SECTION: Detail Transaksi + Paginasi --}}
    <x-fi.section heading="Detail Transaksi Penjualan" flush>
        <div class="relative overflow-x-auto">
            <table class="w-full table-auto divide-y divide-gray-200 text-start">
                <thead class="bg-gray-50">
                    <tr>
                        <x-fi.table.th>ID</x-fi.table.th>
                        <x-fi.table.th>Tanggal</x-fi.table.th>
                        <x-fi.table.th>Nama Proyek</x-fi.table.th>
                        <x-fi.table.th align="end">Modal</x-fi.table.th>
                        <x-fi.table.th align="end">Penjualan</x-fi.table.th>
                        <x-fi.table.th align="end">Profit</x-fi.table.th>
                        <x-fi.table.th>Status</x-fi.table.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                    @forelse ($salesRecaps as $recap)
                        @php $badge = $statusBadge($recap->status); @endphp
                        <tr class="bg-white transition duration-75 hover:bg-gray-50">
                            <x-fi.table.td class="font-medium text-primary">{{ $recap->no_faktur }}</x-fi.table.td>
                            <x-fi.table.td>{{ \Carbon\Carbon::parse($recap->date)->format('d M Y') }}</x-fi.table.td>
                            <x-fi.table.td class="min-w-[12rem] whitespace-normal font-medium">{{ $recap->name_proyek }}</x-fi.table.td>
                            <x-fi.table.td align="end" class="tabular-nums">{{ $rupiah($recap->total_capital) }}</x-fi.table.td>
                            <x-fi.table.td align="end" class="tabular-nums">{{ $rupiah($recap->total_selling) }}</x-fi.table.td>
                            <x-fi.table.td align="end" class="font-semibold tabular-nums text-emerald-600">{{ $rupiah($recap->total_profit) }}</x-fi.table.td>
                            <x-fi.table.td>
                                <x-fi.badge :color="$badge['color']" :icon="$badge['icon']">{{ $badge['label'] }}</x-fi.badge>
                            </x-fi.table.td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <x-fi.empty-state icon="inbox" heading="Tidak ada data transaksi" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
            <x-fi.pagination :paginator="$salesRecaps" />
        </div>
    </x-fi.section>
</div>

{{-- Data grafik & JavaScript --}}
@push('scripts')
    <script>
        window.monthlyTrendData = @json($monthlyTrend);
        window.statusDistributionData = @json($statusDistribution);
    </script>
    @vite(['resources/js/pages/report/sales-reports/index.js'])
@endpush
