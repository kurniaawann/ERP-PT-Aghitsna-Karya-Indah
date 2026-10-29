{{-- =====================================================================
     Konten: Rekap Proyek (gaya Filament)
     Dipakai di dua tempat:
     - pages/finance/rekap.blade.php (Super Admin, tab "proyek"; $tabs & $tab
       tersedia → judul "Rekap" + tab, filter ke route('rekap.index') + tab)
     - pages/finance/project-recaps.blade.php (halaman Rekap Proyek sendiri, admin)

     Data dari RecapProyekController::indexData():
     - $recaps : Paginator ProjectRecap (10/halaman)

     JS: @vite('resources/js/pages/finance/project-recaps/index.js',
               'resources/js/pages/finance/payment-proofs/embedded.js')
     ===================================================================== --}}
@php
    $inRekap = isset($tabs);
    $indexUrl = $inRekap ? route('rekap.index', ['tab' => 'proyek']) : route('recap-proyek.index');
@endphp

<div class="flex flex-col gap-y-6">

    {{-- SECTION: Header Halaman + Aksi (Tambah) --}}
    <x-fi.page-header :heading="$inRekap ? 'Rekap' : 'Rekap Proyek'"
        :breadcrumbs="$inRekap
            ? ['Finance' => null, 'Rekap' => route('rekap.index'), 'Rekap Proyek' => null]
            : ['Report' => null, 'Rekap Proyek' => $indexUrl]">
        <x-slot:actions>
            <x-fi.button icon="plus" onclick="openModal('addModal')">Tambah Proyek</x-fi.button>
        </x-slot:actions>
    </x-fi.page-header>

    {{-- SECTION: Tab Rekap (Super Admin) --}}
    @if ($inRekap)
        <x-finance.recap-tabs :tabs="$tabs" active="proyek" />
    @endif

    {{-- SECTION: Tabel Rekap Proyek (+ filter status, pencarian, hapus massal, paginasi) --}}
    <x-finance.project-recaps.table :recaps="$recaps" :indexUrl="$indexUrl" :filterTab="$inRekap ? 'proyek' : null" />
</div>

{{-- SECTION: Modal Tambah --}}
@include('components.finance.project-recaps.add-modal')

{{-- SECTION: Modal Detail & Edit (satu per rekap) --}}
@foreach ($recaps as $recap)
    @include('components.finance.project-recaps.detail-modal', ['recap' => $recap])
    @include('components.finance.project-recaps.edit-modal', ['recap' => $recap])
@endforeach

{{-- SECTION: Modal Upload & Hapus Bukti Pembayaran (shared, dipicu menu Aksi / modal Edit) --}}
@include('components.finance.payment-proofs.upload-modal')
@include('components.finance.payment-proofs.delete-modal')

{{-- SECTION: Modal Konfirmasi Hapus Massal --}}
<x-fi.modal id="deleteModal" heading="Hapus Rekap Proyek Terpilih" icon="trash" color="danger" width="md"
    onConfirm="submitDeleteForm()" submitLabel="Ya, Hapus">
    <p>Apakah Anda yakin ingin menghapus data rekap proyek yang dipilih?</p>
    <p class="mt-3">
        Laporan Keuangan Proyek beserta seluruh transaksi "Bon"-nya pada rekap tersebut juga akan ikut terhapus
        (tidak dapat dikembalikan).
    </p>
    <p class="mt-3">File design dari data yang dihapus juga akan ikut terhapus.</p>
</x-fi.modal>

{{-- SECTION: Modal Konfirmasi Hapus 1 Rekap (menu Aksi ⋮ tiap baris) --}}
{{-- Mengirim satu id ke route('recap-proyek.destroySelected') yang sama dengan hapus massal. --}}
<x-fi.delete-row-modal :action="route('recap-proyek.destroySelected')" field="selected_recaps" heading="Hapus Rekap Proyek"
    warning="Laporan Keuangan Proyek beserta seluruh transaksi “Bon”-nya dan file design rekap ini juga ikut terhapus." />

@push('scripts')
    @vite(['resources/js/pages/finance/project-recaps/index.js', 'resources/js/pages/finance/payment-proofs/embedded.js'])
@endpush
