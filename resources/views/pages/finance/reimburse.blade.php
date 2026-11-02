{{-- ═══════════════════════════════════════════════════════════════════════════
     HALAMAN INDEX REIMBURSEMENT (gaya Filament)
     Tujuan: Menampilkan daftar reimbursement dengan filter status/bulan/
             tahun & pencarian, aksi CRUD, persetujuan (approve/reject),
             dan export PDF/Excel.
     Data dari ReimburseController@index:
     - $reimburses: Paginator Reimburse (15/halaman) hasil
                    ReimburseService::buildFilteredQuery($request),
                    difilter search, status, month, year.
     - $notaOptions: opsi "Ambil dari Nota" di modal Tambah (superadmin).
     Logika role:
     - superadmin: dapat menambah & mengedit reimburse (status draft).
     - admin     : dapat menyetujui/menolak reimburse (bulk approve/reject).
                   Setelah "Setujui", pratinjau PDF reimburse yang disetujui
                   langsung terbuka di dalam halaman (flash reimburse_preview_ids).
     - semua     : cetak PDF/Excel semua data (sesuai filter) atau hanya baris
                   yang dicentang (Export Dipilih).
     Pengajuan otomatis: setiap Nota buatan superadmin (termasuk nota proyek
     dari Invoice Semen) otomatis membuat reimburse draft yang tertaut
     (kolom id_nota, badge "Dari Nota" + link pratinjau PDF nota).
     Komponen yang di-include:
     - x-fi.page-header + x-fi.export-dropdown (Semua / Dipilih)  : header & aksi
     - components.finance.reimburse.table                  : toolbar + tabel + paginasi
     - components.finance.reimburse.add-modal / edit-modal : modal CRUD (superadmin)
     - components.finance.reimburse.approve-modal / reject-modal : modal persetujuan (admin)
     - x-fi.modal / x-fi.delete-row-modal                  : konfirmasi hapus massal / satu data
     JS: @vite('resources/js/pages/finance/reimburse/index.js')
     ═══════════════════════════════════════════════════════════════════════════ --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Reimbursement')

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- ─── Header Halaman + Aksi (Export, Tambah) ───────────────────────── --}}
        <x-fi.page-header heading="Reimbursement"
            :breadcrumbs="['Report' => null, 'Reimbursement' => route('reimburse.index')]">
            <x-slot:actions>
                {{-- Export: Semua (sesuai filter) + Dipilih (checkbox ids[]) --}}
                <x-fi.export-dropdown
                    :pdfRoute="route('reimburse.export.pdf')"
                    :excelRoute="route('reimburse.export.excel')"
                    :pdfSelectedRoute="route('reimburse.export.pdf.selected')"
                    :excelSelectedRoute="route('reimburse.export.excel.selected')"
                    :queryParams="[
                        'search' => request('search'),
                        'status' => request('status'),
                        'month'  => request('month'),
                        'year'   => request('year'),
                    ]"
                    :selectedQueryParams="[
                        'search' => request('search'),
                        'month'  => request('month'),
                        'year'   => request('year'),
                    ]" />

                {{-- Tombol Tambah (Super Admin only) --}}
                @if (Auth::user()->role === 'superadmin')
                    <x-fi.button icon="plus" onclick="openModal('addModal')">Tambah Reimburse</x-fi.button>
                @endif
            </x-slot:actions>
        </x-fi.page-header>

        {{-- ─── Tabel Reimbursement (+ aksi massal, filter, paginasi) ────────── --}}
        @include('components.finance.reimburse.table', ['reimburses' => $reimburses])
    </div>

    {{-- ─── Modal Tambah (Super Admin only) ────────────────────────────────── --}}
    @if (Auth::user()->role === 'superadmin')
        @include('components.finance.reimburse.add-modal')
    @endif

    {{-- ─── Modal Edit per baris (Super Admin only, status draft) ─────────── --}}
    {{-- Alur: hanya superadmin yang boleh mengedit, dan hanya reimburse
         berstatus 'draft' yang masih bisa diubah (sudah disetujui/ditolak
         tidak boleh diedit lagi). --}}
    @foreach ($reimburses as $reimburse)
        @if ($reimburse->status === 'draft' && Auth::user()->role === 'superadmin')
            @include('components.finance.reimburse.edit-modal', ['reimburse' => $reimburse])
        @endif
    @endforeach

    {{-- ─── Modal Konfirmasi Approve / Reject (Admin only) ────────────────── --}}
    @if (Auth::user()->role === 'admin')
        @include('components.finance.reimburse.approve-modal')
        @include('components.finance.reimburse.reject-modal')
    @endif

    {{-- ─── Modal Konfirmasi Hapus Massal ──────────────────────────────────── --}}
    <x-fi.modal id="deleteModal" heading="Hapus Data Terpilih" icon="trash" color="danger" width="md"
        onConfirm="submitDeleteForm()" submitLabel="Ya, Hapus">
        Apakah Anda yakin ingin menghapus reimburse yang dipilih?
    </x-fi.modal>

    {{-- ─── Modal Konfirmasi Hapus 1 Reimburse (menu Aksi ⋮ tiap baris) ───── --}}
    {{-- Mengirim satu kode ke route('reimburse.destroy') yang sama dengan hapus massal (ids[]). --}}
    <x-fi.delete-row-modal :action="route('reimburse.destroy')" field="ids" heading="Hapus Reimburse" />

    {{-- ─── Pratinjau Otomatis Setelah Disetujui ───────────────────────────── --}}
    {{-- Setelah admin memilih "Setujui", dokumen PDF reimburse yang disetujui
         langsung dipratinjau di dalam halaman (bukan tab baru). URL dibaca
         oleh JS halaman lalu dibuka lewat window.openDocumentPreview(). --}}
    @if (!empty(session('reimburse_preview_ids')))
        <input type="hidden" id="reimburse-approved-preview-url"
            value="{{ route('reimburse.export.pdf', ['ids' => session('reimburse_preview_ids')]) }}">
    @endif

    {{-- ─── JavaScript Module ───────────────────────────────────────────────── --}}
    @push('scripts')
        @vite('resources/js/pages/finance/reimburse/index.js')
    @endpush
@endsection
