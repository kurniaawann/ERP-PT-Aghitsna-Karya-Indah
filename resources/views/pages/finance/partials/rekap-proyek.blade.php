{{-- =====================================================================
     Partial Tab: Rekap Proyek (dipakai pada halaman Rekap tab proyek)
     Data tersedia dari RecapProyekController::indexData():
     - $recaps : Paginator ProjectRecap (10/halaman)
     ===================================================================== --}}

    {{-- Container Utama --}}
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Rekap Proyek</h1>

        {{-- Toolbar Pencarian & Aksi (form mengarah ke tab aktif) --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            <form method="GET" action="{{ route('rekap.index', ['tab' => 'proyek']) }}"
                class="w-full min-[1530px]:w-auto min-[1530px]:flex-1 flex flex-col min-[1530px]:flex-row gap-3">
                <x-filters.status-filter :value="request('status')" responsive="custom" />
                <x-filters.search-input :value="request('search')" placeholder="Cari nama proyek..." responsive="custom" />
            </form>

            <div class="flex items-center gap-2 mt-2 min-[1530px]:mt-0 w-full min-[1530px]:w-auto">
                <div class="flex flex-col min-[1530px]:flex-row gap-2 w-full min-[1530px]:w-auto">
                    <x-buttons.delete-button modalId="deleteModal" responsive="custom" />
                    <x-buttons.add-button modalId="addModal" text="Tambah Proyek" responsive="custom" />
                </div>
            </div>
        </div>

        {{-- Tabel Rekap Proyek --}}
        <x-finance.project-recaps.table :recaps="$recaps" />
    </div>

    {{-- Pagination --}}
    <x-pagination :paginator="$recaps" />

    {{-- Modals --}}
    @include('components.finance.project-recaps.add-modal')

    @foreach ($recaps as $recap)
        @include('components.finance.project-recaps.detail-modal', ['recap' => $recap])
        @include('components.finance.project-recaps.edit-modal', ['recap' => $recap])
    @endforeach

    {{-- Modal Upload & Hapus Bukti Pembayaran (shared, dipicu dari modal Edit) --}}
    @include('components.finance.payment-proofs.upload-modal')
    @include('components.finance.payment-proofs.delete-modal')

    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        <p class="text-text-primary mb-4">Apakah Anda yakin ingin menghapus data rekap proyek yang dipilih?</p>
        <p class="text-sm text-text-secondary mb-4">
            <i class="fa-solid fa-info-circle"></i> Laporan Keuangan Proyek beserta seluruh transaksi "Bon"-nya pada
            rekap tersebut juga akan ikut terhapus (tidak dapat dikembalikan).
        </p>
        <p class="text-sm text-text-secondary">
            <i class="fa-solid fa-info-circle"></i> File design dari data yang dihapus juga akan ikut terhapus.
        </p>
    </x-modal>

    @push('scripts')
        @vite(['resources/js/pages/finance/project-recaps/index.js', 'resources/js/pages/finance/payment-proofs/embedded.js'])
    @endpush