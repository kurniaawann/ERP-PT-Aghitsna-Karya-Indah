{{-- =====================================================================
     Partial: Laporan Pengeluaran / Kas Kantor (dalam Laporan Akhir, gaya Filament)
     Logika/data sama dengan halaman Laporan Pengeluaran lama (ExpenseReportController),
     hanya form disubmit ke route report.final dengan tab=expense agar tetap
     berada di halaman Laporan Akhir. Export tetap ke endpoint terpisah
     (report.expense.export.pdf / report.expense.export.excel) lewat tombol
     Export di header halaman.

     Hook JS (report/expense-reports/index.js): canvas #monthlyTrendChart,
     #categoryExpenseChart, #incomeVsExpenseChart + window.monthlyTrendData /
     categoryDistributionData / summaryData.
     ===================================================================== --}}
@php
    $rupiah = fn ($value) => 'Rp ' . number_format($value ?? 0, 0, ',', '.');
@endphp

<div class="flex flex-col gap-y-6">

    {{-- SECTION: Filter (bulan, tahun, kategori, pencarian) --}}
    <x-report.final-report.filter-bar tab="expense" :keys="['month', 'year', 'category', 'search']">
        <div class="sm:w-44">
            <x-fi.month-select :value="request('month')" onchange="this.form.requestSubmit()" />
        </div>
        <div class="sm:w-36">
            <x-fi.year-select :value="request('year')" onchange="this.form.requestSubmit()" />
        </div>
        {{-- Kategori (tipe transaksi sudah melekat pada tiap kategori) --}}
        <div class="col-span-2 sm:w-56">
            <x-fi.select name="category" aria-label="Filter Kategori" placeholder="Semua Kategori" prefix-icon="tag"
                onchange="this.form.requestSubmit()" data-fi-select>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </x-fi.select>
        </div>
        <x-fi.search-input :value="request('search')" placeholder="Cari transaksi..." class="col-span-2 sm:w-60" />
    </x-report.final-report.filter-bar>

    {{-- SECTION: Kartu Ringkasan --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-fi.stat label="Total Pemasukan" icon="arrow-down" color="success" size="sm"
            :description="$summary['income_count'] . ' transaksi'">
            {{ $rupiah($summary['total_income']) }}
        </x-fi.stat>
        <x-fi.stat label="Total Pengeluaran" icon="arrow-up" color="danger" size="sm"
            :description="$summary['expense_count'] . ' transaksi'">
            {{ $rupiah($summary['total_expense']) }}
        </x-fi.stat>
        <x-fi.stat label="Saldo" icon="scale" :color="$summary['balance'] >= 0 ? 'primary' : 'danger'" size="sm"
            :description="$summary['balance'] >= 0 ? 'Surplus' : 'Defisit'">
            {{ $rupiah(abs($summary['balance'])) }}
        </x-fi.stat>
        <x-fi.stat label="Total Transaksi" icon="document-text" size="sm" description="Semua transaksi">
            {{ $summary['total_transactions'] }}
        </x-fi.stat>
    </div>

    {{-- SECTION: Grafik --}}
    <x-fi.section heading="Trend Pemasukan & Pengeluaran Bulanan">
        <div class="relative h-[350px]">
            <canvas id="monthlyTrendChart"></canvas>
        </div>
    </x-fi.section>

    <x-fi.section heading="Pemasukan & Pengeluaran Per Kategori">
        <div class="relative h-[400px]">
            <canvas id="categoryExpenseChart"></canvas>
        </div>
    </x-fi.section>

    <x-fi.section heading="Perbandingan Pemasukan vs Pengeluaran">
        <div class="relative h-[300px]">
            <canvas id="incomeVsExpenseChart"></canvas>
        </div>
    </x-fi.section>

    {{-- SECTION: Rincian Cash Flow --}}
    <x-fi.section heading="Rincian Cash Flow">
        <dl class="grid gap-y-3 text-sm">
            <div class="flex items-center justify-between gap-3 rounded-xl bg-gray-50 p-4">
                <dt class="flex items-center gap-x-2 font-medium text-gray-700">
                    <x-fi.icon name="banknotes" class="h-5 w-5 shrink-0 text-gray-400" />
                    Saldo Awal Periode
                </dt>
                <dd class="text-base font-semibold tabular-nums text-gray-950">{{ $rupiah($cashFlow['opening_balance']) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3 rounded-xl bg-emerald-50 p-4">
                <dt class="flex items-center gap-x-2 font-medium text-gray-700">
                    <x-fi.icon name="arrow-down" class="h-5 w-5 shrink-0 text-emerald-600" />
                    Pemasukan
                </dt>
                <dd class="text-base font-semibold tabular-nums text-emerald-600">{{ $rupiah($cashFlow['total_income']) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3 rounded-xl bg-red-50 p-4">
                <dt class="flex items-center gap-x-2 font-medium text-gray-700">
                    <x-fi.icon name="arrow-up" class="h-5 w-5 shrink-0 text-red-600" />
                    Pengeluaran
                </dt>
                <dd class="text-base font-semibold tabular-nums text-red-600">{{ $rupiah($cashFlow['total_expense']) }}</dd>
            </div>
            <div class="h-px bg-gray-200"></div>
            <div class="flex items-center justify-between gap-3 rounded-xl bg-blue-50 p-4">
                <dt class="flex items-center gap-x-2 font-medium text-gray-700">
                    <x-fi.icon name="calculator" class="h-5 w-5 shrink-0 text-primary" />
                    Net Cash Flow
                </dt>
                <dd @class(['text-base font-semibold tabular-nums', 'text-primary' => $cashFlow['net_cash_flow'] >= 0, 'text-red-600' => $cashFlow['net_cash_flow'] < 0])>
                    {{ $rupiah(abs($cashFlow['net_cash_flow'])) }}
                </dd>
            </div>
            <div class="flex items-center justify-between gap-3 rounded-xl p-4 ring-1 ring-gray-950/10">
                <dt class="flex items-center gap-x-2 font-semibold text-gray-950">
                    <x-fi.icon name="wallet" class="h-5 w-5 shrink-0 text-gray-500" />
                    Saldo Akhir Periode
                </dt>
                <dd class="text-lg font-bold tabular-nums text-gray-950">{{ $rupiah($cashFlow['closing_balance']) }}</dd>
            </div>
        </dl>
    </x-fi.section>

    {{-- SECTION: Ringkasan Per Kategori --}}
    <x-fi.section heading="Ringkasan Per Kategori" flush>
        <div class="relative overflow-x-auto">
            <table class="w-full table-auto divide-y divide-gray-200 text-start">
                <thead class="bg-gray-50">
                    <tr>
                        <x-fi.table.th>Kategori</x-fi.table.th>
                        <x-fi.table.th>Transaksi</x-fi.table.th>
                        <x-fi.table.th align="end">Pemasukan</x-fi.table.th>
                        <x-fi.table.th align="end">Pengeluaran</x-fi.table.th>
                        <x-fi.table.th align="end">Total</x-fi.table.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                    @forelse ($categoryDistribution as $category)
                        <tr class="bg-white transition duration-75 hover:bg-gray-50">
                            <x-fi.table.td class="min-w-[12rem] whitespace-normal font-medium">{{ $category['category_name'] }}</x-fi.table.td>
                            <x-fi.table.td class="text-gray-700">{{ $category['count'] }} transaksi</x-fi.table.td>
                            <x-fi.table.td align="end" class="font-semibold tabular-nums">
                                @if ($category['category_type'] === 'EXPENSE')
                                    <span class="text-gray-400">-</span>
                                @else
                                    <span class="text-emerald-600">{{ $rupiah($category['income']) }}</span>
                                @endif
                            </x-fi.table.td>
                            <x-fi.table.td align="end" class="font-semibold tabular-nums">
                                @if ($category['category_type'] === 'INCOME')
                                    <span class="text-gray-400">-</span>
                                @else
                                    <span class="text-red-600">{{ $rupiah($category['expense']) }}</span>
                                @endif
                            </x-fi.table.td>
                            <x-fi.table.td align="end" class="font-semibold tabular-nums">{{ $rupiah($category['total']) }}</x-fi.table.td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-fi.empty-state icon="tag" heading="Tidak ada data kategori" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-fi.section>

    {{-- SECTION: Detail Transaksi + Paginasi --}}
    <x-fi.section heading="Detail Transaksi" flush>
        <div class="relative overflow-x-auto">
            <table class="w-full table-auto divide-y divide-gray-200 text-start">
                <thead class="bg-gray-50">
                    <tr>
                        <x-fi.table.th>Tanggal</x-fi.table.th>
                        <x-fi.table.th>No. Invoice</x-fi.table.th>
                        <x-fi.table.th>Kategori</x-fi.table.th>
                        <x-fi.table.th>Deskripsi</x-fi.table.th>
                        <x-fi.table.th align="end">Pemasukan</x-fi.table.th>
                        <x-fi.table.th align="end">Pengeluaran</x-fi.table.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                    @forelse ($expenseRecaps as $expense)
                        <tr class="bg-white transition duration-75 hover:bg-gray-50">
                            <x-fi.table.td>{{ \Carbon\Carbon::parse($expense->transaction_date)->format('d M Y') }}</x-fi.table.td>
                            <x-fi.table.td class="font-medium">{{ $expense->invoice_number }}</x-fi.table.td>
                            <x-fi.table.td class="max-w-[14rem] whitespace-normal">
                                <x-fi.badge color="gray" class="!whitespace-normal text-start">{{ $expense->category?->name ?? '-' }}</x-fi.badge>
                            </x-fi.table.td>
                            <x-fi.table.td class="min-w-[14rem] whitespace-normal">
                                <div>{{ $expense->description }}</div>
                                @if ($expense->notes)
                                    <div class="mt-1 text-xs text-gray-500">{{ $expense->notes }}</div>
                                @endif
                            </x-fi.table.td>
                            <x-fi.table.td align="end" :class="'font-semibold tabular-nums ' . ($expense->income_amount > 0 ? 'text-emerald-600' : 'text-gray-400')">
                                {{ $expense->income_amount > 0 ? $rupiah($expense->income_amount) : '-' }}
                            </x-fi.table.td>
                            <x-fi.table.td align="end" :class="'font-semibold tabular-nums ' . ($expense->expense_amount > 0 ? 'text-red-600' : 'text-gray-400')">
                                {{ $expense->expense_amount > 0 ? $rupiah($expense->expense_amount) : '-' }}
                            </x-fi.table.td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-fi.empty-state icon="inbox" heading="Tidak ada data transaksi" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
            <x-fi.pagination :paginator="$expenseRecaps" />
        </div>
    </x-fi.section>
</div>

{{-- Data grafik & JavaScript --}}
@push('scripts')
    <script>
        window.monthlyTrendData = @json($monthlyTrend);
        window.categoryDistributionData = @json($categoryDistribution);
        window.summaryData = @json($summary);
    </script>
    @vite(['resources/js/pages/report/expense-reports/index.js'])
@endpush
