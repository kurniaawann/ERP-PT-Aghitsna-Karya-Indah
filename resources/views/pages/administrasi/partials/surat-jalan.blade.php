{{-- =====================================================================
     Partial Tab: Surat Jalan (dipakai pada halaman Surat Menyurat)
     Data tersedia dari indexData() controller:
     - $deliveryNotes : koleksi DeliveryNote (paginate)
     - $search        : keyword pencarian
     ===================================================================== --}}

    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- ═══════════════════════════════════════════════════════════
             TOOLBAR: Pencarian & Tombol Aksi
             ═══════════════════════════════════════════════════════════ --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">

            {{-- Form Pencarian & Filter --}}
            <form method="GET" action="{{ route('surat-menyurat.index', ['tab' => 'surat-jalan']) }}"
                class="w-full min-[1530px]:w-auto min-[1530px]:flex-1 flex flex-col min-[1530px]:flex-row gap-3">
                <x-filters.month-filter :value="request('month')" responsive="custom" />
                <x-filters.year-filter :value="request('year')" responsive="custom" />
                <x-filters.search-input :value="request('search')" placeholder="Cari surat jalan..." responsive="custom" />
            </form>

            {{-- Tombol Aksi: Print, Hapus, Tambah --}}
            <div class="flex items-center gap-2 mt-2 min-[1530px]:mt-0 w-full min-[1530px]:w-auto">
                <div class="flex flex-col min-[1530px]:flex-row gap-2 w-full min-[1530px]:w-auto">

                    {{-- Dropdown Export PDF --}}
                    <x-buttons.print-dropdown-with-selected :pdfRoute="route('delivery-note.administrasi.export.pdf')" :queryParams="['search' => request('search'), 'month' => request('month'), 'year' => request('year')]" responsive="custom" fill />

                    {{-- Tombol Hapus Massal --}}
                    <x-buttons.delete-button modalId="deleteModal" />

                    {{-- Tombol Tambah Surat Jalan --}}
                    <x-buttons.add-button modalId="addModal" text="Tambah Surat Jalan" />
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             TABEL: Komponen tabel daftar surat jalan
             ═══════════════════════════════════════════════════════════ --}}
        @include('components.administrasi.delivery-note.table', ['deliveryNotes' => $deliveryNotes])

    </div>

    {{-- Pagination --}}
    <x-pagination :paginator="$deliveryNotes" />

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL TAMBAH: Form tambah surat jalan baru
         ═══════════════════════════════════════════════════════════════ --}}
    @include('components.administrasi.delivery-note.add-modal')

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL DETAIL: Tampilan detail surat jalan (read-only).
         ═══════════════════════════════════════════════════════════════ --}}
    @foreach ($deliveryNotes as $deliveryNote)
        @include('components.administrasi.delivery-note.detail-modal', ['deliveryNote' => $deliveryNote])
    @endforeach

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL EDIT: Form edit surat jalan (satu modal per data).
         ═══════════════════════════════════════════════════════════════ --}}
    @foreach ($deliveryNotes as $deliveryNote)
        @include('components.administrasi.delivery-note.edit-modal', ['deliveryNote' => $deliveryNote])
    @endforeach

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL HAPUS: Konfirmasi hapus massal
         ═══════════════════════════════════════════════════════════════ --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus surat jalan yang dipilih?
    </x-modal>

    @push('scripts')
        @vite('resources/js/pages/administrasi/delivery-notes/index.js')

        {{-- Fungsi inline pembuka modal detail. --}}
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