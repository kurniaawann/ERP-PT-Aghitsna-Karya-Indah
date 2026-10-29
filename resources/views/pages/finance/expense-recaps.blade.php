{{-- =====================================================================
     Halaman: Rekap Pengeluaran (Expense Recaps) — untuk role admin
              ditampilkan dengan nama "Kas Kantor" (revisi klien)
     Tujuan: Menampilkan daftar & grand totals rekap pengeluaran (income,
             expense, balance) dengan filter kategori/bulan/tahun, search,
             CRUD manual, dan export Excel/PDF.
     Tampilan memakai komponen bergaya Filament (resources/views/components/fi).

     Super Admin diarahkan ke halaman Rekap ber-tab (route('rekap.index', tab=pengeluaran))
     yang memakai konten yang sama.

     Data dari RecapExpenseController@index (indexData()):
     - $expenseRecaps : Paginator ExpenseRecap (10/halaman) dari
                        RecapExpenseService::buildIndexQuery($request),
                        difilter oleh category, month, year, search.
     - $categories    : Daftar kategori pengeluaran dari
                        RecapExpenseService::getExpenseCategories().
     - $totals        : Grand totals (total_income, total_expense, balance).
     - $recapLabel    : "Kas Kantor" (admin) / "Rekap Pengeluaran" (lainnya).
     - $nextIncomeInvoiceNumber : pratinjau nomor faktur uang masuk berikutnya
                        (null untuk admin — tanpa faktur).

     Konten: pages/finance/partials/rekap-pengeluaran.blade.php
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - ' . ($recapLabel ?? 'Rekap Pengeluaran'))

@section('content')
    @include('pages.finance.partials.rekap-pengeluaran')
@endsection
