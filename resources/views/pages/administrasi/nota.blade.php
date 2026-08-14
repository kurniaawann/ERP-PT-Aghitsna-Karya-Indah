{{-- =====================================================================
     Halaman Utama Modul Nota Administrasi
     PT Aghitsna Karya Indah

     Tujuan: Menampilkan daftar nota dengan pencarian, paginasi,
             tambah/edit lewat modal, hapus massal, dan export PDF.

     Data dari NotaController@index:
     - $notas  : koleksi Nota (paginate 15/halaman, hanya data milik user
                 yang login, urut created_at terbaru; pencarian berdasarkan
                 id_nota, nama_proyek, kepada, faktur_no, sj_no)
     - $search : keyword pencarian
     - $tipe   : filter tipe nota (sewa_jual|proyek|kosong)

     Komponen:
     - Header: Judul halaman
     - Toolbar: Form pencarian, tombol print, hapus massal, tambah
     - Modal Pilih Tipe: Memilih tipe nota sebelum form add tampil
     - Tabel: Daftar nota dengan checkbox
     - Modal Tambah: Form tambah nota baru
     - Modal Edit: Form edit nota (satu per nota)
     - Modal Hapus: Konfirmasi hapus massal
     - Pagination: Navigasi halaman

     JS: @vite('resources/js/pages/administrasi/nota/index.js')
         + hidden input nota-print-selected-route (route export PDF data
           terpilih yang dipakai JS)
     ===================================================================== --}}

@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Nota')

@section('content')
    {{-- ═══════════════════════════════════════════════════════════════
         HEADER: Container utama dengan background surface
         ═══════════════════════════════════════════════════════════════ --}}
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- Header: Judul Halaman --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Nota</h1>

        {{-- ═══════════════════════════════════════════════════════════
             TOOLBAR: Pencarian & Tombol Aksi
             ═══════════════════════════════════════════════════════════ --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">

            {{-- Form Pencarian & Filter --}}
            <form method="GET" action="{{ route('nota.administrasi.index') }}"
                class="w-full min-[1530px]:w-auto min-[1530px]:flex-1 flex flex-col min-[1530px]:flex-row gap-3">
                <x-filters.tipe-nota-filter :value="request('tipe')" responsive="custom" />
                <x-filters.month-filter :value="request('month')" responsive="custom" />
                <x-filters.year-filter :value="request('year')" responsive="custom" />
                <x-filters.search-input :value="request('search')" placeholder="Cari nota..." responsive="custom" />
            </form>

            {{-- Tombol Aksi: Print, Hapus, Tambah --}}
            <div class="flex items-center gap-2 mt-2 min-[1530px]:mt-0 w-full min-[1530px]:w-auto">
                <div class="flex flex-col min-[1530px]:flex-row gap-2 w-full min-[1530px]:w-auto">

{{-- Dropdown Export PDF --}}
                    <x-buttons.print-dropdown-with-selected :pdfRoute="route('nota.administrasi.export.pdf')" :queryParams="['search' => request('search'), 'month' => request('month'), 'year' => request('year'), 'tipe' => request('tipe')]" responsive="custom" fill />

                    {{-- Tombol Hapus Massal --}}
                    <x-buttons.delete-button modalId="deleteModal" />

                    {{-- Tombol Tambah Nota --}}
                    <x-buttons.add-button modalId="pilihTipeModal" text="Tambah Nota" />
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             TABEL: Komponen tabel daftar nota
             ═══════════════════════════════════════════════════════════ --}}
        @include('components.administrasi.nota.table', ['notas' => $notas])

    </div>

    {{-- Pagination --}}
    <x-pagination :paginator="$notas" />

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL PILIH TIPE: Memilih tipe nota sebelum form add tampil
         ═══════════════════════════════════════════════════════════════ --}}
    <x-modal id="pilihTipeModal" title="Pilih Tipe Nota" hideFooter>
        <p class="text-sm text-text-secondary mb-4">Pilih tipe nota yang ingin ditambahkan.</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <button type="button" onclick="closeModal('pilihTipeModal'); openModal('addModal')"
                class="flex flex-col items-center gap-2 border-2 border-border-strong rounded-xl p-5 bg-surface-base hover:bg-surface-secondary hover:border-primary transition-all duration-200">
                <i class="fa-solid fa-boxes-stacked text-3xl text-primary"></i>
                <span class="font-semibold text-text-primary">Nota Sewa/Jual</span>
                <span class="text-xs text-text-secondary text-center">Design nota existing (faktur, sj, biaya tambahan, PPN)</span>
            </button>
            <button type="button" onclick="closeModal('pilihTipeModal'); openModal('addModalProyek')"
                class="flex flex-col items-center gap-2 border-2 border-border-strong rounded-xl p-5 bg-surface-base hover:bg-surface-secondary hover:border-primary transition-all duration-200">
                <i class="fa-solid fa-diagram-project text-3xl text-primary"></i>
                <span class="font-semibold text-text-primary">Nota Proyek</span>
                <span class="text-xs text-text-secondary text-center">Design nota proyek (nama proyek, quantity, satuan)</span>
            </button>
        </div>
    </x-modal>

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL TAMBAH: Form tambah nota baru
         ═══════════════════════════════════════════════════════════════ --}}
    @include('components.administrasi.nota.add-modal')
    @include('components.administrasi.nota.add-modal-proyek')

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL EDIT: Form edit nota (satu modal per nota).
         Alur: iterasi setiap $nota pada halaman aktif lalu render satu
         modal edit per baris data. Modal mengikuti tipe nota.
         ═══════════════════════════════════════════════════════════════ --}}
    @foreach ($notas as $nota)
        @if ($nota->tipe_nota === \App\Models\Administrasi\Nota::TIPE_PROYEK)
            @include('components.administrasi.nota.edit-modal-proyek', ['nota' => $nota])
        @else
            @include('components.administrasi.nota.edit-modal', ['nota' => $nota])
        @endif
    @endforeach

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL HAPUS: Konfirmasi hapus massal
         ═══════════════════════════════════════════════════════════════ --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- Hidden input untuk route print selected (digunakan oleh JS).
         JS membaca nilai route ini saat user memilih baris lalu klik
         "Print Selected" pada print-dropdown-with-selected. --}}
    <input type="hidden" id="nota-print-selected-route" value="{{ route('nota.administrasi.export.pdf.selected') }}">

    {{-- ═══════════════════════════════════════════════════════════════
         JAVASCRIPT: Load via Vite (modular)
         ═══════════════════════════════════════════════════════════════ --}}
    @push('scripts')
        @vite('resources/js/pages/administrasi/nota/index.js')
    @endpush
@endsection
