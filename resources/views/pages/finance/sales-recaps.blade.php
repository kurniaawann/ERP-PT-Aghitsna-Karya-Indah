{{-- =====================================================================
     Halaman: Rekap Penjualan (Sales Recaps)
     Tujuan: Menampilkan daftar rekap penjualan dengan filter bulan/tahun/
             status & pencarian proyek, export Excel/PDF, tambah/edit (halaman
             form), upload bukti, info status (Lunas/Belum Lunas), dan hapus.
     Tampilan memakai komponen bergaya Filament (resources/views/components/fi).

     Super Admin diarahkan ke halaman Rekap ber-tab (route('rekap.index', tab=sales))
     yang memakai konten yang sama.

     Data dari RecapSalesController@index (indexData()):
     - $salesRecaps: Paginator SalesRecap (10/halaman) hasil
                     RecapSalesService::buildFilteredQuery($request)
                     diurutkan created_at/date desc, difilter month/year/status/search.
     - $items      : Daftar item inventory (Items) diurutkan name_item.
     - $grandTotals: Grand totals hasil RecapSalesService::getGrandTotals()
                     (kapital, penjualan, laba) dengan filter yang sama.

     Konten: pages/finance/partials/rekap-sales.blade.php
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Rekap Penjualan')

@section('content')
    @include('pages.finance.partials.rekap-sales')
@endsection
