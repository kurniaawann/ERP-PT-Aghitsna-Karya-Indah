{{-- =====================================================================
     Halaman Utama Modul Surat Perintah Kerja (SPK)
     PT Aghitsna Karya Indah

     Tujuan: Menampilkan daftar SPK dengan pencarian, paginasi,
             tambah/edit/detail lewat modal, hapus massal, dan export
             PDF & Word.

     Data dari SuratPerintahKerjaController@index:
     - $suratPerintahKerjas : koleksi SuratPerintahKerja (paginate 15/halaman)
     - $search              : keyword pencarian

     JS: @vite('resources/js/pages/administrasi/surat-perintah-kerja/index.js')
     ===================================================================== --}}

@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Surat Perintah Kerja')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        <h1 class="text-2xl font-semibold text-text-primary mb-4">Surat Perintah Kerja</h1>

        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            <form method="GET" action="{{ route('surat-perintah-kerja.administrasi.index') }}"
                class="w-full min-[1280px]:w-auto min-[1280px]:flex-1 flex flex-col min-[1280px]:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari surat perintah kerja..." />
            </form>

            <div class="flex items-center gap-2 mt-2 min-[1280px]:mt-0 w-full min-[1280px]:w-auto">
                <div class="flex flex-col min-[1280px]:flex-row gap-2 w-full min-[1280px]:w-auto">

                    {{-- Tombol Hapus Massal --}}
                    <x-buttons.delete-button modalId="deleteModal" />

                    {{-- Tombol Tambah SPK --}}
                    <x-buttons.add-button modalId="addModal" text="Tambah Surat Perintah Kerja" />
                </div>
            </div>
        </div>

        @include('components.administrasi.surat-perintah-kerja.table', ['suratPerintahKerjas' => $suratPerintahKerjas])

    </div>

    <x-pagination :paginator="$suratPerintahKerjas" />

    @include('components.administrasi.surat-perintah-kerja.add-modal')

    @foreach ($suratPerintahKerjas as $spk)
        @include('components.administrasi.surat-perintah-kerja.detail-modal', ['spk' => $spk])
    @endforeach

    @foreach ($suratPerintahKerjas as $spk)
        @include('components.administrasi.surat-perintah-kerja.edit-modal', ['spk' => $spk])
    @endforeach

    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus surat perintah kerja yang dipilih?
    </x-modal>

    @push('scripts')
        @vite('resources/js/pages/administrasi/surat-perintah-kerja/index.js')

        <script>
            function showDetailModal(id) {
                const modal = document.getElementById('detailModal-' + id);
                if (modal) {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                }
            }
        </script>
    @endpush
@endsection
