{{-- =====================================================================
     Halaman: Laporan Keuangan Proyek (Daftar, gaya Filament)
     Tujuan: Menampilkan daftar Laporan Keuangan Proyek (satu per Rekap
             Proyek, dibuat otomatis saat Rekap Proyek dibuat). Menu Aksi ⋮
             tiap baris: Lihat Detail (modal tabel transaksi "Bon"), Edit
             (halaman form gabungan Rekap Proyek + transaksi), Export PDF/Excel
             (pratinjau di dalam halaman), dan Hapus.
     Data dari ProjectFinancialReportController@index:
     - $recaps : paginator Rekap Proyek (dengan relasi financialReport.items)
     Halaman form (pengganti modal Tambah/Edit lama):
     - route('project-financial-report.create') → Tambah Transaksi
     - route('project-financial-report.edit', $recap) → Edit
     Komponen yang di-include:
     - components.report.project-financial-report.table
     - components.report.project-financial-report.detail-modal (per rekap)
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Laporan Keuangan Proyek')

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- SECTION: Header Halaman + Aksi (Tambah Transaksi) --}}
        <x-fi.page-header heading="Laporan Keuangan Proyek"
            :breadcrumbs="['Report' => null, 'Laporan Keuangan Proyek' => route('project-financial-report.index')]">
            <x-slot:actions>
                <x-fi.button icon="plus" :href="route('project-financial-report.create')">Tambah Transaksi</x-fi.button>
            </x-slot:actions>
        </x-fi.page-header>

        {{-- SECTION: Tabel (+ filter bulan/tahun, pencarian, hapus massal, paginasi) --}}
        <x-report.project-financial-report.table :recaps="$recaps" />
    </div>

    {{-- SECTION: Modal Detail per Rekap --}}
    @foreach ($recaps as $recap)
        @include('components.report.project-financial-report.detail-modal', ['recap' => $recap])
    @endforeach

    {{-- SECTION: Modal Konfirmasi Hapus Massal --}}
    <x-fi.modal id="deleteModal" heading="Hapus Data Terpilih" icon="trash" color="danger" width="md"
        onConfirm="submitDeleteForm()" submitLabel="Ya, Hapus">
        Apakah kamu yakin ingin menghapus rekap proyek dan laporan keuangannya yang dipilih?
    </x-fi.modal>

    {{-- SECTION: Modal Konfirmasi Hapus 1 Rekap (menu Aksi ⋮ tiap baris) --}}
    {{-- Mengirim satu id ke route('recap-proyek.destroySelected') yang sama dengan hapus massal. --}}
    <x-fi.delete-row-modal :action="route('recap-proyek.destroySelected')" field="selected_recaps" heading="Hapus Data"
        warning="Rekap proyek beserta laporan keuangan dan seluruh transaksi “Bon”-nya akan ikut terhapus." />

    @push('scripts')
        @vite(['resources/js/pages/report/project-financial-report/index.js'])
    @endpush
@endsection
