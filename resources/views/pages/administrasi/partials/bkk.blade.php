{{-- =====================================================================
     Partial Tab: Bukti Kas Keluar (dipakai pada halaman Surat Menyurat)
     Revisi klien: Bukti Kas Keluar tidak lagi menjadi submenu sidebar,
     melainkan tab di Surat Menyurat (role Admin & Super Admin).
     Data tersedia dari CashOutProofController::indexData():
     - $cashOuts   : paginator CashOutProof
     - $search     : keyword pencarian
     - $executives : petinggi untuk dropdown tanda tangan
     ===================================================================== --}}
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- ═══════════════════════════════════════════════════════════
             HEADER: Container utama dengan background surface
             ═══════════════════════════════════════════════════════════ --}}

        {{-- ═══════════════════════════════════════════════════════════
             TOOLBAR: Filter Pencarian & Tombol Aksi
             ═══════════════════════════════════════════════════════════ --}}

        {{-- Filter Pencarian dan Tombol Aksi --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">

            {{-- Form Pencarian & Filter --}}
            <form method="GET" action="{{ route('surat-menyurat.index', ['tab' => 'bkk']) }}"
                class="w-full min-[1530px]:w-auto min-[1530px]:flex-1 flex flex-col min-[1530px]:flex-row gap-3">
                {{-- Pertahankan tab aktif saat filter/pencarian dikirim (form GET membuang query di action) --}}
                <input type="hidden" name="tab" value="bkk">
                <x-filters.month-filter :value="request('month')" responsive="custom" />
                <x-filters.year-filter :value="request('year')" responsive="custom" />
                <x-filters.search-input :value="request('search')" placeholder="Cari bukti kas keluar..." responsive="custom" />
            </form>

            {{-- Tombol Aksi: Export PDF, Hapus, Tambah --}}
            <div class="flex items-center gap-2 mt-2 min-[1530px]:mt-0 w-full min-[1530px]:w-auto">
                <div class="flex flex-col min-[1530px]:flex-row gap-2 w-full min-[1530px]:w-auto">
                    <x-buttons.print-dropdown-with-selected
                        :pdfRoute="route('cash-out-proof.export.pdf')"
                        :queryParams="['search' => request('search'), 'month' => request('month'), 'year' => request('year')]"
                        responsive="custom" fill />

                    <x-buttons.delete-button modalId="deleteModal" />

                    <x-buttons.add-button modalId="addModal" text="Tambah BKK" />
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             TABEL: Daftar bukti kas keluar
             ═══════════════════════════════════════════════════════════ --}}

        {{-- Tabel Data Bukti Kas Keluar --}}
        @include('components.administrasi.cash-out-proof.table', ['cashOuts' => $cashOuts])

    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         PAGINATION: Navigasi halaman data
         ═══════════════════════════════════════════════════════════════ --}}

    {{-- Pagination --}}
    <x-pagination :paginator="$cashOuts" />

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL TAMBAH: Form tambah bukti kas keluar baru
         ═══════════════════════════════════════════════════════════════ --}}

    {{-- Modal Form Tambah Bukti Kas Keluar --}}
    @include('components.administrasi.cash-out-proof.add-modal', ['executives' => $executives])

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL EDIT: Form edit bukti kas keluar (satu modal per baris).
         Alur: iterasi setiap $cashOut pada halaman aktif lalu render
         modal edit untuk masing-masing baris data.
         ═══════════════════════════════════════════════════════════════ --}}
    @foreach ($cashOuts as $cashOut)
        @include('components.administrasi.cash-out-proof.edit-modal', ['cashOut' => $cashOut, 'executives' => $executives])
    @endforeach

    {{-- Modal Konfirmasi Hapus Massal --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true"
        onConfirm="submitDeleteForm()" buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- Hidden input untuk route print selected (digunakan oleh JS).
         JS membaca nilai route ini saat user memilih baris lalu klik
         "Print Selected" pada print-dropdown-with-selected. --}}
    <input type="hidden" id="cash-out-proof-print-selected-route" value="{{ route('cash-out-proof.export.pdf.selected') }}">

    {{-- ═══════════════════════════════════════════════════════════════
         JAVASCRIPT: Load via Vite (modular)
         ═══════════════════════════════════════════════════════════════ --}}
    @push('scripts')
        @vite('resources/js/pages/administrasi/cash-out-proof/index.js')
    @endpush
