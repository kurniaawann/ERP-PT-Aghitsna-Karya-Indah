{{-- =====================================================================
     Partial Tab: Rekap Alumunium (dipakai pada halaman Rekap tab aluminium)
     Data tersedia dari RecapAlumuniumController::indexData():
     - $invoices    : Paginator InvoiceAlumunium (10/halaman)
     - $totals      : Objek ringkasan (total qty, nominal, dsb)
     - $periodTitle : Label periode yang sedang difilter
     ===================================================================== --}}

    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Rekap Alumunium</h1>

        {{-- Toolbar Filter & Aksi (form mengarah ke tab aktif) --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            <form method="GET" action="{{ route('rekap.index', ['tab' => 'aluminium']) }}"
                class="w-full min-[1530px]:w-auto min-[1530px]:flex-1 flex flex-col min-[1530px]:flex-row gap-3">
                <x-filters.month-filter :value="request('month')" responsive="custom" />
                <x-filters.year-filter :value="request('year')" responsive="custom" />
                <x-filters.search-input :value="request('search')" placeholder="Cari invoice, penerima, atau proyek..." responsive="custom" />
            </form>

            <div class="flex items-center gap-2 mt-2 min-[1530px]:mt-0 w-full min-[1530px]:w-auto">
                <div class="flex flex-col min-[1530px]:flex-row gap-2 w-full min-[1530px]:w-auto">
                    <x-buttons.print-dropdown
                        :excelRoute="route('recap-alumunium.export.excel')"
                        :pdfRoute="route('recap-alumunium.export.pdf')"
                        :queryParams="[
                            'search' => request('search'),
                            'month' => request('month'),
                            'year' => request('year'),
                        ]"
                        responsive="custom"
                    />
                </div>
            </div>
        </div>

        <x-finance.aluminium-recaps.summary-cards :totals="$totals" />

        <x-finance.aluminium-recaps.table :invoices="$invoices" :totals="$totals" />
    </div>

    {{-- Pagination --}}
    <x-pagination :paginator="$invoices" />

    {{-- Modal Detail per Invoice --}}
    @foreach ($invoices as $invoice)
        <x-finance.aluminium-invoices.detail-modal :invoice="$invoice" />
    @endforeach