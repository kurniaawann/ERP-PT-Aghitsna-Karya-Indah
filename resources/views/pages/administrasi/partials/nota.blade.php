{{-- =====================================================================
     Partial Tab: Nota (dipakai pada halaman Surat Menyurat)
     Data tersedia dari indexData() controller:
     - $notas : koleksi Nota (paginate)
     - $search, $tipe, $executives, $divisions
     ===================================================================== --}}

    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- ═══════════════════════════════════════════════════════════
             TOOLBAR: Pencarian & Tombol Aksi
             ═══════════════════════════════════════════════════════════ --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">

            {{-- Form Pencarian & Filter --}}
            <form method="GET" action="{{ route('surat-menyurat.index', ['tab' => 'nota']) }}"
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

    {{-- Hidden input untuk route print selected (digunakan oleh JS). --}}
    <input type="hidden" id="nota-print-selected-route" value="{{ route('nota.administrasi.export.pdf.selected') }}">

    @push('scripts')
        @vite('resources/js/pages/administrasi/nota/index.js')
    @endpush