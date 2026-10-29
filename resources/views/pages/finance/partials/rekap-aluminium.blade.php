{{-- =====================================================================
     Konten: Rekap Alumunium (gaya Filament)
     Dipakai di dua tempat:
     - pages/finance/rekap.blade.php (Super Admin, tab "aluminium"; $tabs & $tab
       tersedia → judul "Rekap" + tab, filter ke route('rekap.index') + tab)
     - pages/finance/aluminium-recaps.blade.php (halaman Rekap Alumunium sendiri)

     Data dari RecapAlumuniumController::indexData():
     - $invoices    : Paginator InvoiceAlumunium (10/halaman)
     - $totals      : Objek ringkasan (total invoice, jumlah, lunas)
     - $periodTitle : Label periode yang sedang difilter

     JS: tidak ada modul khusus (filter submit lewat onchange; modal bawaan).
     ===================================================================== --}}
@php
    $inRekap = isset($tabs);
    $indexUrl = $inRekap ? route('rekap.index', ['tab' => 'aluminium']) : route('recap-alumunium.index');
@endphp

<div class="flex flex-col gap-y-6">

    {{-- SECTION: Header Halaman + Aksi (Export) --}}
    <x-fi.page-header :heading="$inRekap ? 'Rekap' : 'Rekap Alumunium'"
        :breadcrumbs="$inRekap
            ? ['Finance' => null, 'Rekap' => route('rekap.index'), 'Rekap Alumunium' => null]
            : ['Finance' => null, 'Rekap Alumunium' => $indexUrl]">
        <x-slot:actions>
            {{-- Dropdown Export (Excel & PDF, mengikuti filter aktif) --}}
            <x-fi.export-dropdown :excelRoute="route('recap-alumunium.export.excel')"
                :pdfRoute="route('recap-alumunium.export.pdf')" :queryParams="[
                    'search' => request('search'),
                    'month' => request('month'),
                    'year' => request('year'),
                ]" />
        </x-slot:actions>
    </x-fi.page-header>

    {{-- SECTION: Tab Rekap (Super Admin) --}}
    @if ($inRekap)
        <x-finance.recap-tabs :tabs="$tabs" active="aluminium" />
    @endif

    {{-- SECTION: Kartu Ringkasan --}}
    <x-finance.aluminium-recaps.summary-cards :totals="$totals" />

    {{-- SECTION: Tabel Rekap (+ filter, pencarian, total, paginasi) --}}
    <x-finance.aluminium-recaps.table :invoices="$invoices" :totals="$totals" :indexUrl="$indexUrl"
        :filterTab="$inRekap ? 'aluminium' : null" />
</div>

{{-- SECTION: Modal Detail per Invoice --}}
@foreach ($invoices as $invoice)
    <x-finance.aluminium-invoices.detail-modal :invoice="$invoice" />
@endforeach
