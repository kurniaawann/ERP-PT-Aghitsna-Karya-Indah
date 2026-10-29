{{-- =====================================================================
     Halaman: Kategori Transaksi
     Tujuan: Mengelola daftar kategori transaksi (Pemasukan/Pengeluaran):
             pencarian & filter tipe/modul, toggle status aktif/nonaktif,
             edit, tambah kategori, dan hapus (massal maupun per baris) dengan
             modal warning untuk kategori yang sedang digunakan.
     Tampilan memakai komponen bergaya Filament (resources/views/components/fi).
     Data dari TransactionCategoryController@index (logic di
     TransactionCategoryService):
     - $categories      : paginator kategori (kolom: name, code, type,
                          module, sort_order, is_active)
     - $existingCodes   : map [id => code] untuk validasi kode duplikat
                          di frontend
     - $usedCategoryIds : daftar id kategori yang sedang dipakai transaksi
     Filter (GET): type (INCOME/EXPENSE), module, search
     Komponen yang di-include:
     - components.report.transaction-category.table     : toolbar (hapus massal +
                                                          filter + pencarian), tabel, paginasi
     - components.report.transaction-category.add-modal / edit-modal : modal CRUD
     - x-fi.modal (deleteModal & warningUsedModal)      : konfirmasi hapus massal &
                                                          peringatan "sedang digunakan"
     - x-fi.delete-row-modal                            : hapus 1 kategori dari menu Aksi
     JS: @vite('resources/js/pages/report/transaction-categories/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Kategori Transaksi')

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- SECTION: Header Halaman + Tombol Tambah --}}
        <x-fi.page-header heading="Kategori Transaksi"
            :breadcrumbs="['Report' => null, 'Kategori Transaksi' => route('transaction-category.index')]">
            <x-slot:actions>
                <x-fi.button icon="plus" onclick="openModal('addModal')">Tambah Kategori</x-fi.button>
            </x-slot:actions>
        </x-fi.page-header>

        {{-- SECTION: Tabel Kategori Transaksi --}}
        {{-- Daftar kategori dengan checkbox (hapus massal), toggle status
             aktif/nonaktif (toggleStatus() di index.js) dan menu Aksi ⋮. --}}
        @include('components.report.transaction-category.table')
    </div>

    {{-- SECTION: Modal Tambah Kategori --}}
    @include('components.report.transaction-category.add-modal')

    {{-- SECTION: Modal Edit (per kategori) --}}
    @foreach ($categories as $category)
        @include('components.report.transaction-category.edit-modal', ['category' => $category])
    @endforeach

    {{-- SECTION: Modal Konfirmasi Bulk Delete --}}
    {{-- Dibuka checkAndDelete() (index.js) bila tidak ada kategori terpilih yang
         sedang digunakan; konfirmasi → submitDeleteForm() submit #deleteForm. --}}
    <x-fi.modal id="deleteModal" heading="Hapus Kategori Terpilih" icon="trash" color="danger" width="md"
        onConfirm="submitDeleteForm()" submitLabel="Ya, Hapus">
        Apakah kamu yakin ingin menghapus kategori yang dipilih?
        <span class="mt-3 flex items-start justify-center gap-x-1.5 text-red-600">
            <x-fi.icon name="exclamation-triangle" class="mt-0.5 h-4 w-4 shrink-0" />
            Kategori yang sedang digunakan tidak akan dihapus.
        </span>
    </x-fi.modal>

    {{-- SECTION: Modal Konfirmasi Hapus 1 Kategori (menu Aksi ⋮ tiap baris) --}}
    {{-- Mengirim satu id ke route('transaction-category.destroySelected') yang sama
         dengan hapus massal, jadi guard "sedang digunakan" di server tetap berlaku.
         Kategori yang sedang digunakan: menu Hapus dinonaktifkan. --}}
    <x-fi.delete-row-modal :action="route('transaction-category.destroySelected')" field="selected_categories"
        heading="Hapus Kategori" />

    {{-- SECTION: Modal Peringatan Kategori Sedang Digunakan --}}
    {{-- Ditampilkan showWarningModal() (index.js) saat user mencoba menghapus
         kategori yang sedang dipakai transaksi. Daftar nama kategori diisi
         dinamis ke #usedCategoriesList. --}}
    <x-fi.modal id="warningUsedModal" heading="Kategori Sedang Digunakan" icon="exclamation-circle" color="danger"
        width="md">
        <div class="grid gap-y-4 text-sm">
            <p class="text-gray-700">
                Kategori berikut sedang digunakan dalam transaksi dan <strong class="text-gray-950">tidak dapat dihapus</strong>:
            </p>

            <div class="max-h-48 overflow-y-auto rounded-xl bg-red-50 p-4 ring-1 ring-red-600/20">
                <ul id="usedCategoriesList" class="list-inside list-disc space-y-1 text-red-700">
                    {{-- Daftar kategori akan diisi oleh JavaScript --}}
                </ul>
            </div>

            <p class="flex items-start gap-x-1.5 text-gray-500">
                <x-fi.icon name="information-circle" class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                Untuk menghapus kategori ini, pastikan tidak ada transaksi yang menggunakannya.
            </p>

            <div class="flex justify-end">
                <x-fi.button icon="check-circle" onclick="closeWarningModal()">Mengerti</x-fi.button>
            </div>
        </div>
    </x-fi.modal>

    {{-- SECTION: Data untuk JavaScript --}}
    <script>
        window.csrfToken = '{{ csrf_token() }}';
        window.existingCodes = @json(array_values($existingCodes ?? []));
        window.existingCodesWithId = @json($existingCodes ?? []);
    </script>

    {{-- SECTION: JavaScript --}}
    @push('scripts')
        @vite('resources/js/pages/report/transaction-categories/index.js')
    @endpush
@endsection
