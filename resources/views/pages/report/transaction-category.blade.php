{{-- =====================================================================
     Halaman: Kategori Transaksi
     Tujuan: Mengelola daftar kategori transaksi (Pemasukan/Pengeluaran):
             pencarian & filter tipe, toggle status aktif/nonaktif, edit,
             tambah kategori, dan hapus massal dengan modal warning untuk
             kategori yang sedang digunakan.
     Data dari TransactionCategoryController@index (logic di
     TransactionCategoryService):
     - $categories    : paginator kategori (kolom: name, code, type,
                        sort_order, is_active)
     - $existingCodes : map [id => code] untuk validasi kode duplikat
                        di frontend
     - $usedCategoryIds : daftar id kategori yang sedang dipakai transaksi
     Filter (GET): type (INCOME/EXPENSE), search
     Komponen yang di-include:
     - layouts.app
     - x-filters.select-filter & x-filters.search-input (toolbar filter)
     - x-buttons.add-button (buka modal tambah)
     - components.report.transaction-category.table (daftar kategori)
     - x-pagination
     - components.report.transaction-category.add-modal
     - components.report.transaction-category.edit-modal (per kategori)
     - x-modal (konfirmasi hapus massal) + modal warning "sedang digunakan"
     JS yang di-load:
     - resources/js/pages/report/transaction-categories/index.js
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Kategori Transaksi')

@section('content')
    {{-- Container utama halaman Kategori Transaksi --}}
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        {{-- Header --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Kategori Transaksi</h1>

        {{-- Toolbar: Filter + Action Buttons --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Filter Tipe dan Pencarian --}}
            <form method="GET" action="{{ route('transaction-category.index') }}" id="filterForm"
                class="w-full min-[1520px]:w-auto min-[1520px]:flex-1 flex flex-col min-[1520px]:flex-row gap-3">

                {{-- Filter Tipe (Pemasukan/Pengeluaran) --}}
                <x-filters.select-filter name="type" :value="request('type')" :options="collect([
                    (object) ['id' => 'INCOME', 'name' => 'Pemasukan'],
                    (object) ['id' => 'EXPENSE', 'name' => 'Pengeluaran'],
                ])" placeholder="Semua Tipe"
                    :autoSubmit="true" responsive="custom" />

                {{-- Input Pencarian --}}
                <x-filters.search-input :value="request('search')" placeholder="Cari kategori..." responsive="custom" />
            </form>

            {{-- Tombol Aksi --}}
            <div class="flex items-center gap-2 mt-2 min-[1520px]:mt-0 w-full min-[1520px]:w-auto">
                <div class="flex flex-col min-[1520px]:flex-row gap-2 w-full min-[1520px]:w-auto">
                    <button type="button" id="delete-button" onclick="checkAndDelete()" disabled
                        class="w-full min-[1520px]:w-auto flex items-center justify-center gap-2 bg-btn-delete hover:bg-btn-delete-hover text-white px-3 py-3.5 rounded-lg transition-colors duration-200 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-btn-delete">
                        <i class="fa-solid fa-trash w-4 h-4"></i>
                        <span>Hapus</span>
                    </button>

                    <x-buttons.add-button modalId="addModal" text="Tambah Kategori" responsive="custom" />
                </div>
            </div>
        </div>

        {{-- Tabel Kategori Transaksi --}}
        {{-- Daftar kategori dengan checkbox (untuk hapus massal), toggle
             status aktif/nonaktif (dipanggil via JS toggleStatus()),
             dan tombol edit yang membuka modal editModal-{id}. --}}
        @include('components.report.transaction-category.table')
    </div>

    {{-- Pagination --}}
    <x-pagination :paginator="$categories" />

    {{-- Modal Tambah Kategori --}}
    @include('components.report.transaction-category.add-modal')

    {{-- Modal Edit untuk setiap kategori --}}
    @foreach ($categories as $category)
        @include('components.report.transaction-category.edit-modal', ['category' => $category])
    @endforeach

    {{-- Modal Konfirmasi Bulk Delete --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()" buttonText="Hapus">
        <p class="text-text-primary mb-4">Apakah Anda yakin ingin menghapus kategori yang dipilih?</p>
        <p class="text-sm text-error">
            <i class="fa-solid fa-exclamation-triangle"></i> Kategori yang sedang digunakan tidak akan dihapus.
        </p>
    </x-modal>

    {{-- Modal Peringatan Kategori Sedang Digunakan --}}
    {{-- Ditampilkan saat user mencoba menghapus kategori yang sedang
         dipakai transaksi. Daftar nama kategori diisi dinamis oleh JS
         (getUsedCategoryIds dari server) ke elemen #usedCategoriesList. --}}
    <div id="warningUsedModal"
        class="fixed inset-0 bg-surface-hover bg-opacity-50 hidden items-center justify-center p-4 z-50 transition-opacity duration-300">
        <div
            class="bg-surface-base rounded-2xl shadow-2xl max-w-md w-full transform transition-all duration-300 scale-95 opacity-0">
            {{-- Header --}}
            <div class="bg-error p-6 rounded-t-2xl">
                <div class="flex items-center gap-3">
                    <div class="bg-surface-base bg-opacity-20 p-3 rounded-full">
                        <i class="fa-solid fa-exclamation-circle text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white">Kategori Sedang Digunakan</h3>
                </div>
            </div>

            {{-- Body --}}
            <div class="p-6">
                <p class="text-text-primary mb-4">
                    Kategori berikut sedang digunakan dalam transaksi dan <strong>tidak dapat dihapus</strong>:
                </p>

                <div class="bg-error-light border-2 border-error rounded-lg p-4 mb-4 max-h-48 overflow-y-auto">
                    <ul id="usedCategoriesList" class="list-disc list-inside space-y-1 text-sm text-error">
                        {{-- Daftar kategori akan diisi oleh JavaScript --}}
                    </ul>
                </div>

                <p class="text-sm text-text-label">
                    <i class="fa-solid fa-info-circle text-primary"></i> Untuk menghapus kategori ini, pastikan tidak
                    ada transaksi yang menggunakannya.
                </p>
            </div>

            {{-- Footer --}}
            <div class="bg-surface-secondary px-6 py-4 rounded-b-2xl flex justify-end">
                <button type="button" onclick="closeWarningModal()"
                    class="px-6 py-2.5 bg-primary hover:bg-primary-hover text-white rounded-lg font-medium transition-colors duration-200 focus:outline-none focus:ring-4 focus:ring-primary-light">
                    <i class="fa-solid fa-check mr-2"></i> Mengerti
                </button>
            </div>
        </div>
    </div>

    {{-- Data untuk JavaScript --}}
    <script>
        window.csrfToken = '{{ csrf_token() }}';
        window.existingCodes = @json(array_values($existingCodes ?? []));
        window.existingCodesWithId = @json($existingCodes ?? []);
    </script>

    {{-- Modular JavaScript --}}
    @vite('resources/js/pages/report/transaction-categories/index.js')
@endsection
