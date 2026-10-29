{{-- =====================================================================
     Halaman: Rekap Alumunium
     Tujuan: Menampilkan rekap/ringkasan invoice alumunium dengan kartu
             ringkasan, filter bulan/tahun & pencarian, detail invoice
             (modal), serta export ke Excel/PDF untuk periode yang aktif.
     Tampilan memakai komponen bergaya Filament (resources/views/components/fi).

     Super Admin diarahkan ke halaman Rekap ber-tab (route('rekap.index', tab=aluminium))
     yang memakai konten yang sama.

     Data dari RecapAlumuniumController@index (indexData()):
     - $invoices   : Paginator InvoiceAlumunium dari
                     RecapAlumuniumService::getPaginatedInvoices(),
                     difilter oleh request('search'), ('month'), ('year').
     - $totals     : Objek ringkasan hasil RecapAlumuniumService::buildTotals()
                     untuk seluruh data periode.
     - $periodTitle: Label periode (bulan/tahun) yang sedang difilter.

     Konten: pages/finance/partials/rekap-aluminium.blade.php
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Rekap Alumunium')

@section('content')
    @include('pages.finance.partials.rekap-aluminium')
@endsection
