{{-- =====================================================================
     Partial Tab: Tanda Terima Dokumen (dipakai pada halaman Surat Menyurat)
     Data tersedia dari indexData() controller:
     - $documents : koleksi DocumentReceipt (paginate)
     - $search    : keyword pencarian
     ===================================================================== --}}

    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- ═══════════════════════════════════════════════════════════
             TOOLBAR: Pencarian & Tombol Aksi
             ═══════════════════════════════════════════════════════════ --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">

            {{-- Form Pencarian & Filter --}}
            <form method="GET" action="{{ route('surat-menyurat.index', ['tab' => 'document-receipt']) }}"
                class="w-full min-[1530px]:w-auto min-[1530px]:flex-1 flex flex-col min-[1530px]:flex-row gap-3">
                <x-filters.month-filter :value="request('month')" responsive="custom" />
                <x-filters.year-filter :value="request('year')" responsive="custom" />
                <x-filters.search-input :value="request('search')" placeholder="Cari dokumen..." responsive="custom" />
            </form>

            {{-- Tombol Aksi: Print, Hapus, Tambah --}}
            <div class="flex items-center gap-2 mt-2 min-[1530px]:mt-0 w-full min-[1530px]:w-auto">
                <div class="flex flex-col min-[1530px]:flex-row gap-2 w-full min-[1530px]:w-auto">

                    {{-- Dropdown Export PDF --}}
                    <x-buttons.print-dropdown-with-selected :pdfRoute="route('document-receipt.export.pdf')" :queryParams="['search' => request('search'), 'month' => request('month'), 'year' => request('year')]" responsive="custom" fill />

                    {{-- Tombol Hapus Massal --}}
                    <x-buttons.delete-button modalId="deleteModal" />

                    {{-- Tombol Tambah Dokumen --}}
                    <x-buttons.add-button modalId="addModal" text="Tambah Dokumen" />
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             TABEL: Komponen tabel daftar dokumen
             ═══════════════════════════════════════════════════════════ --}}
        @include('components.administrasi.document-receipt.table', ['documents' => $documents])

    </div>

    {{-- Pagination --}}
    <x-pagination :paginator="$documents" />

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL TAMBAH: Form tambah dokumen baru
         ═══════════════════════════════════════════════════════════════ --}}
    @include('components.administrasi.document-receipt.add-modal')

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL EDIT: Form edit dokumen (satu modal per dokumen).
         ═══════════════════════════════════════════════════════════════ --}}
    @foreach ($documents as $document)
        @include('components.administrasi.document-receipt.edit-modal', ['document' => $document])
    @endforeach

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL HAPUS: Konfirmasi hapus massal
         ═══════════════════════════════════════════════════════════════ --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- Hidden input untuk route print selected (digunakan oleh JS). --}}
    <input type="hidden" id="document-receipt-print-selected-route" value="{{ route('document-receipt.export.pdf.selected') }}">

    @push('scripts')
        @vite('resources/js/pages/administrasi/document-receipt/index.js')
    @endpush