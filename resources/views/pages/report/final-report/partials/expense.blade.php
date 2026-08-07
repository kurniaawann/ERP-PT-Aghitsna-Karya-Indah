{{-- =====================================================================
     Partial: Laporan Pengeluaran (dalam Laporan Akhir)
     Logika/data sama dengan halaman Laporan Pengeluaran lama (ExpenseReportController),
     hanya form disubmit ke route report.final dengan tab=expense agar tetap
     berada di halaman Laporan Akhir. Export tetap ke endpoint terpisah
     (report.expense.export.pdf / report.expense.export.excel).
     ===================================================================== --}}
<div class="space-y-6">

    {{-- ==================== FILTER SECTION ==================== --}}
    <div class="bg-surface-base p-6 rounded-xl shadow">
        <div class="flex flex-col min-[1520px]:flex-row items-stretch min-[1520px]:items-center gap-3">

            {{-- Form Filter (kiri) --}}
            <form method="GET" action="{{ route('report.final') }}"
                class="flex flex-col min-[1520px]:flex-row items-stretch min-[1520px]:items-center gap-3 flex-1">

                {{-- Hidden: pertahankan tab aktif --}}
                <input type="hidden" name="tab" value="expense">

                {{-- Filter Bulan --}}
                <x-filters.month-filter :value="request('month')" responsive="custom" />

                {{-- Filter Tahun --}}
                <x-filters.year-filter :value="request('year')" responsive="custom" />

                {{-- Filter Kategori (tipe transaksi sudah melekat pada tiap kategori) --}}
                <x-filters.select-filter name="category" :value="request('category')" :options="$categories"
                    placeholder="Semua Kategori" :autoSubmit="true" responsive="custom" />

                {{-- Search (debounce via JS, 500ms) --}}
                <x-filters.search-input :value="request('search')" placeholder="Cari transaksi..." responsive="custom" />
            </form>

            {{-- Print Dropdown (kanan): export terpisah hanya untuk laporan ini --}}
            <div class="w-full min-[1520px]:w-auto">
                <x-buttons.print-dropdown
                    :pdfRoute="route('report.expense.export.pdf')"
                    :excelRoute="route('report.expense.export.excel')"
                    :queryParams="[
                        'month' => request('month'),
                        'year' => request('year'),
                        'category' => request('category'),
                        'search' => request('search'),
                    ]"
                    size="sm" responsive="custom" />
            </div>
        </div>
    </div>

    {{-- ==================== SUMMARY CARDS ==================== --}}
    <div class="grid grid-cols-1 min-[1272px]:grid-cols-2 min-[1520px]:grid-cols-4 gap-6">

        {{-- Total Pemasukan --}}
        <div class="bg-surface-base p-6 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-text-secondary">Total Pemasukan</p>
                    <h3 class="text-2xl font-bold text-success mt-2">
                        Rp {{ number_format($summary['total_income'], 0, ',', '.') }}
                    </h3>
                    <p class="text-xs text-text-secondary mt-1">{{ $summary['income_count'] }} transaksi</p>
                </div>
                <div class="p-4 bg-success-light rounded-full">
                    <i class="fas fa-arrow-down text-success text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Total Pengeluaran --}}
        <div class="bg-surface-base p-6 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-text-secondary">Total Pengeluaran</p>
                    <h3 class="text-2xl font-bold text-error mt-2">
                        Rp {{ number_format($summary['total_expense'], 0, ',', '.') }}
                    </h3>
                    <p class="text-xs text-text-secondary mt-1">{{ $summary['expense_count'] }} transaksi</p>
                </div>
                <div class="p-4 bg-error-light rounded-full">
                    <i class="fas fa-arrow-up text-error text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Saldo --}}
        <div class="bg-surface-base p-6 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-text-secondary">Saldo</p>
                    <h3 class="text-2xl font-bold {{ $summary['balance'] >= 0 ? 'text-primary' : 'text-error' }} mt-2">
                        Rp {{ number_format(abs($summary['balance']), 0, ',', '.') }}
                    </h3>
                    <p class="text-xs text-text-secondary mt-1">
                        {{ $summary['balance'] >= 0 ? 'Surplus' : 'Defisit' }}
                    </p>
                </div>
                <div class="p-4 {{ $summary['balance'] >= 0 ? 'bg-primary-light' : 'bg-error-light' }} rounded-full">
                    <i
                        class="fas fa-balance-scale {{ $summary['balance'] >= 0 ? 'text-primary' : 'text-error' }} text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Total Transaksi --}}
        <div class="bg-surface-base p-6 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-text-secondary">Total Transaksi</p>
                    <h3 class="text-2xl font-bold text-text-primary mt-2">
                        {{ $summary['total_transactions'] }}
                    </h3>
                    <p class="text-xs text-text-secondary mt-1">Semua transaksi</p>
                </div>
                <div class="p-4 bg-secondary-light rounded-full">
                    <i class="fas fa-receipt text-text-primary text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== CHARTS SECTION ==================== --}}
    <div class="grid grid-cols-1 gap-6">

        {{-- Monthly Trend Chart --}}
        <div class="bg-surface-base p-6 rounded-xl shadow">
            <h3 class="text-lg font-semibold text-text-primary mb-4">📈 Trend Pemasukan & Pengeluaran Bulanan</h3>
            <div style="position: relative; height: 350px;">
                <canvas id="monthlyTrendChart"></canvas>
            </div>
        </div>

        {{-- Category Expense Chart --}}
        <div class="bg-surface-base p-6 rounded-xl shadow">
            <h3 class="text-lg font-semibold text-text-primary mb-4">📊 Pemasukan & Pengeluaran Per Kategori</h3>
            <div style="position: relative; height: 400px;">
                <canvas id="categoryExpenseChart"></canvas>
            </div>
        </div>

        {{-- Income vs Expense Comparison --}}
        <div class="bg-surface-base p-6 rounded-xl shadow">
            <h3 class="text-lg font-semibold text-text-primary mb-4">💰 Perbandingan Pemasukan vs Pengeluaran</h3>
            <div style="position: relative; height: 300px;">
                <canvas id="incomeVsExpenseChart"></canvas>
            </div>
        </div>
    </div>

    {{-- ==================== CASH FLOW ANALYSIS ==================== --}}
    <div class="bg-surface-base p-6 rounded-xl shadow">
        <h3 class="text-lg font-semibold text-text-primary mb-6">Rincian Cash Flow</h3>
        <div class="space-y-4">
            <div class="flex items-center justify-between p-4 bg-surface-secondary rounded-lg">
                <span class="text-text-primary font-medium">Saldo Awal Periode</span>
                <span class="text-lg font-semibold text-text-primary">
                    Rp {{ number_format($cashFlow['opening_balance'], 0, ',', '.') }}
                </span>
            </div>
            <div class="flex items-center justify-between p-4 bg-success-light rounded-lg">
                <span class="text-text-primary font-medium flex items-center">
                    <i class="fas fa-plus-circle text-success mr-2"></i>
                    Pemasukan
                </span>
                <span class="text-lg font-semibold text-success">
                    Rp {{ number_format($cashFlow['total_income'], 0, ',', '.') }}
                </span>
            </div>
            <div class="flex items-center justify-between p-4 bg-error-light rounded-lg">
                <span class="text-text-primary font-medium flex items-center">
                    <i class="fas fa-minus-circle text-error mr-2"></i>
                    Pengeluaran
                </span>
                <span class="text-lg font-semibold text-error">
                    Rp {{ number_format($cashFlow['total_expense'], 0, ',', '.') }}
                </span>
            </div>
            <div class="h-px bg-border-light my-2"></div>
            <div class="flex items-center justify-between p-4 bg-primary-light rounded-lg">
                <span class="text-text-primary font-medium flex items-center">
                    <i class="fas fa-equals text-primary mr-2"></i>
                    Net Cash Flow
                </span>
                <span
                    class="text-lg font-semibold {{ $cashFlow['net_cash_flow'] >= 0 ? 'text-primary' : 'text-error' }}">
                    Rp {{ number_format(abs($cashFlow['net_cash_flow']), 0, ',', '.') }}
                </span>
            </div>
            <div
                class="flex items-center justify-between p-4 bg-secondary-light rounded-lg border-2 border-border-strong">
                <span class="text-text-primary font-bold flex items-center">
                    <i class="fas fa-wallet text-text-primary mr-2"></i>
                    Saldo Akhir Periode
                </span>
                <span class="text-xl font-bold text-text-primary">
                    Rp {{ number_format($cashFlow['closing_balance'], 0, ',', '.') }}
                </span>
            </div>
        </div>
    </div>

    {{-- ==================== CATEGORY SUMMARY TABLE ==================== --}}
    <div class="bg-surface-base rounded-xl shadow overflow-hidden">
        <div class="p-6 border-b border-border-light">
            <h3 class="text-lg font-semibold text-text-primary">📊 Ringkasan Per Kategori</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-surface-secondary">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                            Kategori</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                            Transaksi</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                            Pemasukan</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                            Pengeluaran</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                            Total</th>
                    </tr>
                </thead>
                <tbody class="bg-surface-base divide-y divide-border-light">
                    @forelse($categoryDistribution as $category)
                        <tr class="hover:bg-surface-secondary transition-colors duration-150">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-text-primary">{{ $category['category_name'] }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-primary">
                                {{ $category['count'] }} transaksi
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold">
                                @if ($category['category_type'] === 'EXPENSE')
                                    <span class="text-text-tertiary">-</span>
                                @else
                                    <span class="text-success">Rp
                                        {{ number_format($category['income'], 0, ',', '.') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold">
                                @if ($category['category_type'] === 'INCOME')
                                    <span class="text-text-tertiary">-</span>
                                @else
                                    <span class="text-error">Rp
                                        {{ number_format($category['expense'], 0, ',', '.') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-primary font-semibold">
                                Rp {{ number_format($category['total'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-text-secondary">
                                Tidak ada data kategori
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ==================== DETAIL TRANSACTIONS TABLE ==================== --}}
    <div class="bg-surface-base rounded-xl shadow overflow-hidden">
        <div class="p-6 border-b border-border-light">
            <h3 class="text-lg font-semibold text-text-primary">Detail Transaksi</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-surface-secondary">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                            Tanggal</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                            No. Invoice</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                            Kategori</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                            Deskripsi</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                            Pemasukan</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                            Pengeluaran</th>
                    </tr>
                </thead>
                <tbody class="bg-surface-base divide-y divide-border-light">
                    @forelse($expenseRecaps as $expense)
                        <tr class="hover:bg-surface-secondary transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-primary">
                                {{ \Carbon\Carbon::parse($expense->transaction_date)->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-text-primary">
                                {{ $expense->invoice_number }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-surface-secondary text-text-primary">
                                    {{ $expense->category?->name ?? '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-text-primary">{{ $expense->description }}</div>
                                @if ($expense->notes)
                                    <div class="text-xs text-text-secondary mt-1">{{ $expense->notes }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-success">
                                @if ($expense->income_amount > 0)
                                    Rp {{ number_format($expense->income_amount, 0, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-error">
                                @if ($expense->expense_amount > 0)
                                    Rp {{ number_format($expense->expense_amount, 0, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-text-secondary">
                                Tidak ada data transaksi
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ==================== PAGINATION ==================== --}}
    <div class="bg-surface-base p-4 rounded-xl shadow">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-sm text-text-primary font-semibold">
                Halaman {{ $expenseRecaps->currentPage() }} dari {{ $expenseRecaps->lastPage() }}
            </div>
            <div>
                {{ $expenseRecaps->links() }}
            </div>
        </div>
    </div>
</div>

{{-- Chart Data — Window Globals --}}
@push('scripts')
    <script>
        window.monthlyTrendData = @json($monthlyTrend);
        window.categoryDistributionData = @json($categoryDistribution);
        window.summaryData = @json($summary);
    </script>
    @vite(['resources/js/pages/report/expense-reports/index.js'])
@endpush
