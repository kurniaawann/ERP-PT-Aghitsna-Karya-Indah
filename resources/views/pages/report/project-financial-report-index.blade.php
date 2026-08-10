{{-- =====================================================================
     Halaman: Laporan Keuangan Proyek (Daftar)
     Tujuan: Menampilkan daftar seluruh Rekap Proyek beserta status laporan
             keuangannya (sudah dibuat / belum, total uang masuk, uang
             keluar, saldo). Dari sini user membuka halaman detail laporan
             per rekap proyek.
     Data dari ProjectFinancialReportController@index:
     - $recaps : paginator Rekap Proyek (dengan relasi financialReport.items)
     Komponen yang di-include:
     - layouts.app
     - x-pagination
     - components.report.project-financial-report.index-table
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Laporan Keuangan Proyek')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        {{-- ==================== Header ==================== --}}
        <div class="flex items-start justify-between flex-wrap gap-3 mb-4">
            <div>
                <h1 class="text-2xl font-semibold text-text-primary mb-1">Laporan Keuangan Proyek</h1>
                <p class="text-sm text-text-secondary">
                    Daftar rekap proyek dan status laporan keuangannya. Klik "Buka Laporan" untuk melihat detail.
                </p>
            </div>
        </div>

        {{-- ==================== Tabel ==================== --}}
        <x-report.project-financial-report.index-table :recaps="$recaps" />

        {{-- ==================== Pagination ==================== --}}
        <div class="mt-4">
            <x-pagination :paginator="$recaps" />
        </div>
    </div>
@endsection
