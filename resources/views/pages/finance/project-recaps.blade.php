{{-- =====================================================================
     Halaman: Rekap Proyek (Standalone, role admin — menu Report)
     Tujuan: Menampilkan daftar rekap proyek yang diinput manual dengan
             kolom No (auto-generate), Nama Proyek, Total RAB, Uang Masuk
             (DP, superadmin), Terbayar, Sisa Pembayaran, status + progress,
             dan file design. Mendukung tambah, lihat detail, edit, upload
             bukti pembayaran (kategori Rekap Proyek), dan hapus.
     Tampilan memakai komponen bergaya Filament (resources/views/components/fi).

     Super Admin diarahkan ke halaman Rekap ber-tab (route('rekap.index', tab=proyek))
     yang memakai konten yang sama.

     Data dari RecapProyekController@index (indexData()):
     - $recaps : Paginator ProjectRecap dari
                 RecapProyekService::buildIndexQuery($request),
                 difilter oleh search & status.

     Konten: pages/finance/partials/rekap-proyek.blade.php
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Rekap Proyek')

@section('content')
    @include('pages.finance.partials.rekap-proyek')
@endsection
