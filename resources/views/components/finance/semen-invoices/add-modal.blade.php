{{-- Modal Tambah Invoice Semen --}}
<x-modal id="addModal" title="Tambah Invoice Semen" action="{{ route('semen-invoice.store') }}" method="POST"
    buttonText="Simpan" size="6xl">

    {{-- Info Invoice (header) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal Invoice <span class="text-error">*</span></label>
        <input type="date" name="invoice_date" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Tanggal invoice tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    {{-- Container: banyak proyek (satu invoice = banyak proyek) --}}
    <div id="semen-projects-container">

        <div class="flex items-center justify-between mb-2">
            <label class="block text-text-primary font-semibold">Proyek &amp; Data Semen <span
                    class="text-error">*</span></label>
            <button type="button" id="add-project-btn"
                class="flex items-center gap-1 bg-btn-add hover:bg-btn-add-hover text-white px-3 py-1 rounded-lg transition-colors duration-200 text-xs">
                <i class="fa-solid fa-plus w-3 h-3"></i> Tambah Proyek
            </button>
        </div>

        <div id="semen-project-list" class="space-y-4"></div>
    </div>

    {{-- Preview Total --}}
    <div class="mb-4 p-4 bg-gradient-to-r from-primary/10 to-primary/5 rounded-lg border-2 border-primary/20">
        <div class="flex justify-between items-center">
            <span class="text-text-primary font-semibold">Total Invoice:</span>
            <span id="invoice-total-preview" class="text-2xl font-bold text-primary">Rp 0</span>
        </div>
    </div>

    <input type="hidden" name="projects" id="semen-projects-json" value="[]">

    {{-- Template Proyek (disalin oleh JS) --}}
    <template id="semen-project-template">
        <div class="semen-project p-3 border rounded bg-surface-secondary">
            <div class="flex items-center justify-between mb-3">
                <label class="block text-text-primary font-medium">
                    Proyek <span class="semen-project-counter text-primary font-semibold">1</span>
                </label>
                <button type="button"
                    class="remove-project-btn text-error hover:text-red-700 px-2 py-1 rounded text-xs"
                    title="Hapus proyek">
                    <i class="fa-solid fa-trash w-3 h-3"></i> Hapus Proyek
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="block text-text-label text-sm mb-1">Nama Proyek <span class="text-error">*</span></label>
                    <input type="text" class="semen-nama-proyek w-full border rounded p-2"
                        placeholder="Nama proyek" required maxlength="255"
                        oninvalid="this.setCustomValidity('Nama proyek tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                </div>
                <div>
                    <label class="block text-text-label text-sm mb-1">Nama Pengurus Proyek</label>
                    <input type="text" class="semen-pengurus w-full border rounded p-2"
                        placeholder="Nama pengurus proyek" maxlength="255">
                    <p class="text-xs text-text-label mt-1">Otomatis terisi dari Data Semen yang dipilih.</p>
                </div>
                <div>
                    <label class="block text-text-label text-sm mb-1">Rekening Pembayaran <span
                            class="text-error">*</span></label>
                    <select class="semen-payment-account w-full border rounded p-2" required
                        oninvalid="this.setCustomValidity('Rekening pembayaran harus dipilih')"
                        oninput="this.setCustomValidity('')">
                        <option value="">-- Pilih Rekening --</option>
                        @foreach ($paymentAccounts as $account)
                            <option value="{{ $account->id }}">{{ $account->bank_name }} -
                                {{ $account->account_number }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="border rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr class="text-xs text-text-secondary">
                            <th class="p-2 text-center w-10 rounded-tl-lg">No</th>
                            <th class="p-2 text-left">Pilih Data Semen</th>
                            <th class="p-2 text-left">Tanggal</th>
                            <th class="p-2 text-left">Nama Barang</th>
                            <th class="p-2 text-center w-16">QTY</th>
                            <th class="p-2 text-right">Jumlah</th>
                            <th class="p-2 text-center w-8 rounded-tr-lg"></th>
                        </tr>
                    </thead>
                    <tbody class="semen-rows divide-y divide-gray-100"></tbody>
                </table>
            </div>

            <div class="flex flex-col sm:flex-row gap-2 mt-2">
                <button type="button"
                    class="add-row-btn flex items-center justify-center gap-1 bg-primary hover:bg-primary-hover text-white px-3 py-1.5 rounded transition-colors duration-200 text-xs">
                    <i class="fa-solid fa-plus w-3 h-3"></i> Tambah Baris
                </button>
            </div>

            <p class="text-xs text-text-secondary mt-1">
                Nomor urut per proyek dimulai dari 1 lagi. Qty, tanggal, dan nominal otomatis
                terisi dari Data Semen yang dipilih.
            </p>
        </div>
    </template>

    {{-- Template Baris Data Semen (disalin oleh JS) --}}
    <template id="semen-row-template">
        <tr class="semen-row">
            <td class="p-1 text-center semen-row-no">1</td>
            <td class="p-1">
                <div class="cement-search-wrap relative">
                    <input type="text"
                        class="cement-search-input w-full border rounded-lg p-2 pr-8 text-sm focus:border-primary focus:ring-2 focus:ring-primary-light"
                        placeholder="Cari data semen..." autocomplete="off" required
                        oninvalid="this.setCustomValidity('Pilih data semen terlebih dahulu')"
                        oninput="this.setCustomValidity('')">
                    <i
                        class="fa-solid fa-search absolute right-3 top-3 text-text-tertiary pointer-events-none"></i>
                    <div
                        class="cement-dropdown absolute z-50 w-full bg-white border border-border-strong rounded-lg shadow-lg mt-1 max-h-56 overflow-y-auto hidden">
                        <div class="cement-options">
                            <div class="p-2 text-sm text-text-secondary hover:bg-surface-secondary cursor-pointer border-b"
                                data-value="">
                                -- Pilih Data Semen --
                            </div>
                            {{-- Opsi data semen dimuat dinamis oleh JS dari tabel `cements` (AJAX). --}}
                        </div>
                        <div class="cement-no-results p-3 text-center text-sm text-text-secondary hidden">
                            Tidak ada data semen ditemukan
                        </div>
                    </div>
                </div>
            </td>
            <td class="p-1">
                <input type="date" class="semen-tanggal w-full border rounded p-2 text-sm bg-surface-hover"
                    readonly>
            </td>
            <td class="p-1">
                <input type="text" class="semen-nama-barang w-full border rounded p-2 text-sm bg-surface-hover"
                    value="SEMEN" readonly>
            </td>
            <td class="p-1">
                <input type="number" class="semen-qty w-full border rounded p-2 text-sm text-center bg-surface-hover"
                    min="0" readonly>
                <input type="hidden" class="semen-harga" value="0">
            </td>
            <td class="p-1">
                <input type="text" class="semen-jumlah w-full border rounded p-2 text-sm text-right bg-surface-hover"
                    readonly>
            </td>
            <td class="p-1 text-center">
                <button type="button"
                    class="remove-row-btn text-error hover:text-red-700 px-2 py-1 rounded"
                    title="Hapus baris">
                    <i class="fa-solid fa-trash w-3 h-3"></i>
                </button>
            </td>
        </tr>
    </template>
</x-modal>