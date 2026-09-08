{{-- =====================================================================
     Partial Tab: Kwitansi (dipakai pada halaman Surat Menyurat)
     Data tersedia dari indexData() controller:
     - $kwintansis : koleksi Kwintansi (paginate)
     - $search     : keyword pencarian
     - $executives, $paymentAccounts, $invoiceType
     ===================================================================== --}}

    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- ═══════════════════════════════════════════════════════════
             TOOLBAR: Pencarian & Tombol Aksi
             ═══════════════════════════════════════════════════════════ --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">

            {{-- Form Pencarian & Filter --}}
            <form method="GET" action="{{ route('surat-menyurat.index', ['tab' => 'kwintansi']) }}"
                class="w-full min-[1530px]:w-auto min-[1530px]:flex-1 flex flex-col min-[1530px]:flex-row gap-3">
                {{-- Filter Jenis Invoice: hanya untuk role superadmin (paling kiri) --}}
                @if (auth()->user()?->role === 'superadmin')
                    <div class="w-full min-[1530px]:w-auto">
                        <label for="invoice-type-select" class="sr-only">Filter Jenis Invoice</label>
                        <select name="invoice_type" id="invoice-type-select"
                            onchange="this.form.requestSubmit()"
                            class="block w-full min-[1530px]:w-48 rounded-lg border border-border-strong bg-surface-secondary p-3 text-sm text-text-input 
                                   focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light">
                            <option value="">Semua Jenis Invoice</option>
                            <option value="proyek" @selected(request('invoice_type') === 'proyek')>Invoice Proyek</option>
                            <option value="alumunium" @selected(request('invoice_type') === 'alumunium')>Invoice Alumunium</option>
                            <option value="barang" @selected(request('invoice_type') === 'barang')>Invoice Barang</option>
                        </select>
                    </div>
                @endif

                <x-filters.month-filter :value="request('month')" responsive="custom" />
                <x-filters.year-filter :value="request('year')" responsive="custom" />
                <x-filters.search-input :value="request('search')" placeholder="Cari kwintansi..." responsive="custom" />
            </form>

            {{-- Tombol Aksi: Print, Hapus, Tambah --}}
            <div class="flex items-center gap-2 mt-2 min-[1530px]:mt-0 w-full min-[1530px]:w-auto">
                <div class="flex flex-col min-[1530px]:flex-row gap-2 w-full min-[1530px]:w-auto">

                    {{-- Dropdown Export PDF --}}
                    <x-buttons.print-dropdown-with-selected :pdfRoute="route('kwintansi.export.pdf')" :queryParams="['search' => request('search'), 'invoice_type' => request('invoice_type'), 'month' => request('month'), 'year' => request('year')]" responsive="custom" fill />

                    {{-- Tombol Hapus Massal --}}
                    <x-buttons.delete-button modalId="deleteModal" />

                    {{-- Tombol Tambah Kwintansi --}}
                    <x-buttons.add-button modalId="addModal" text="Tambah Kwintansi" />
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             TABEL: Komponen tabel daftar kwitansi
             ═══════════════════════════════════════════════════════════ --}}
        @include('components.administrasi.kwintansi.table', ['kwintansis' => $kwintansis])

    </div>

    {{-- Pagination --}}
    <x-pagination :paginator="$kwintansis" />

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL TAMBAH: Form tambah kwitansi baru
         ═══════════════════════════════════════════════════════════════ --}}
    @include('components.administrasi.kwintansi.add-modal', ['executives' => $executives, 'paymentAccounts' => $paymentAccounts])

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL EDIT: Form edit kwitansi (satu modal per kwitansi).
         ═══════════════════════════════════════════════════════════════ --}}
    @foreach ($kwintansis as $kwintansi)
        @include('components.administrasi.kwintansi.edit-modal', ['kwintansi' => $kwintansi])
    @endforeach

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL HAPUS: Konfirmasi hapus massal
         ═══════════════════════════════════════════════════════════════ --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- Hidden input untuk route print selected (digunakan oleh JS). --}}
    <input type="hidden" id="kwintansi-print-selected-route" value="{{ route('kwintansi.export.pdf.selected') }}">

    @push('scripts')
        @vite('resources/js/pages/administrasi/kwitansi/index.js')
    @endpush