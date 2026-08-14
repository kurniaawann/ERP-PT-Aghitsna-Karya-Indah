{{-- =====================================================================
     Modal Edit Nota Proyek
     PT Aghitsna Karya Indah

     Komponen form untuk mengedit nota proyek (tipe_nota = proyek).
     Field sesuai tipe proyek: nama proyek, tanggal, kepada, items
     (quantity, satuan, nama barang, harga, jumlah), tanda terima.
     ===================================================================== --}}

{{-- Modal Edit Nota Proyek --}}
<x-modal id="editModal-{{ $nota->id_nota }}" title="Edit Nota Proyek"
        action="{{ route('nota.administrasi.update', $nota->id_nota) }}" method="PUT" buttonText="Perbarui">

        {{-- Tipe nota tersembunyi --}}
        <input type="hidden" name="tipe_nota" value="proyek">

        {{-- ═══════════════════════════════════════════════════════
             FIELD: Nama Proyek & Tanggal
             ═══════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
            <div>
                <label class="block text-text-primary mb-1">Nama Proyek <span class="text-error">*</span></label>
                <input type="text" name="nama_proyek"
                    class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
                    placeholder="Nama proyek" required maxlength="255" value="{{ $nota->nama_proyek }}"
                    oninvalid="this.setCustomValidity('Nama proyek tidak boleh kosong')" oninput="this.setCustomValidity('')">
            </div>

            <div>
                <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
                <input type="date" name="nota_date"
                    class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input" required
                    value="{{ $nota->nota_date->format('Y-m-d') }}"
                    oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')"
                    oninput="this.setCustomValidity('')">
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════
             FIELD: Kepada (Penerima)
             ═══════════════════════════════════════════════════════ --}}
        <div class="mb-3">
            <label class="block text-text-primary mb-1">Kepada Yang Terhormat <span class="text-error">*</span></label>
            <input type="text" name="kepada"
                class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
                placeholder="Nama penerima" required maxlength="255" value="{{ $nota->kepada }}"
                oninvalid="this.setCustomValidity('Kepada tidak boleh kosong')" oninput="this.setCustomValidity('')">
        </div>

        {{-- ═══════════════════════════════════════════════════════
             SECTION: Daftar Barang (Item Rows)
             Data existing di-loop dan ditampilkan sebagai item row
             ═══════════════════════════════════════════════════════ --}}
        <div class="mb-4">
            <div class="flex items-center justify-between mb-3">
                <label class="block text-text-primary font-semibold text-base">Daftar Barang <span
                        class="text-error">*</span></label>
                <button type="button"
                    class="addItemBtn bg-btn-add hover:bg-btn-add-hover text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition-all duration-200 flex items-center gap-1.5">
                    <i class="fa-solid fa-plus"></i> Tambah Item
                </button>
            </div>

            <div id="itemsContainer-editModal-{{ $nota->id_nota }}" data-tipe="proyek" class="space-y-3">
                @forelse($nota->items as $item)
                    {{-- Item Row Existing --}}
                    <div
                        class="item-row bg-surface-base border-2 border-border-strong rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
                        <div class="space-y-3">
                            <div class="grid grid-cols-12 gap-3">
                                <div class="col-span-3">
                                    <label class="block text-xs font-semibold text-text-label mb-1.5">Quantity <span
                                            class="text-error">*</span></label>
                                    <input type="number" name="item_quantity[]"
                                        class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-center text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                        placeholder="0" min="1" value="{{ $item['quantity'] }}" required>
                                </div>
                                <div class="col-span-3">
                                    <label class="block text-xs font-semibold text-text-label mb-1.5">Satuan</label>
                                    <input type="text" name="item_satuan[]"
                                        class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-center text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                        placeholder="unit" maxlength="50" value="{{ $item['satuan'] }}">
                                </div>
                                <div class="col-span-6">
                                    <label class="block text-xs font-semibold text-text-label mb-1.5">Nama Barang <span
                                            class="text-error">*</span></label>
                                    <input type="text" name="item_nama_barang[]"
                                        class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                        placeholder="Masukkan nama barang..." value="{{ $item['nama_barang'] }}"
                                        required>
                                </div>
                            </div>

                            <div class="grid grid-cols-12 gap-3">
                                <div class="col-span-4">
                                    <label class="block text-xs font-semibold text-text-label mb-1.5">Harga
                                        <span class="text-error">*</span></label>
                                    <input type="text" name="item_harga[]"
                                        class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-right text-text-input price-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                        placeholder="0"
                                        value="{{ $item['harga'] ? number_format($item['harga'], 0, ',', '.') : '0' }}"
                                        required>
                                </div>
                                <div class="col-span-3">
                                    <label class="block text-xs font-semibold text-text-label mb-1.5">Jumlah</label>
                                    <div
                                        class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-right bg-surface-secondary text-text-input item-total">
                                        {{ $item['jumlah'] ? number_format($item['jumlah'], 0, ',', '.') : '0' }}
                                    </div>
                                </div>
                                <div class="col-span-5 flex items-end">
                                    <button type="button"
                                        class="delete-btn w-full bg-btn-delete hover:bg-btn-delete-hover text-white px-3 py-2.5 rounded-lg text-sm font-medium shadow-sm transition-all duration-200 flex items-center justify-center gap-2">
                                        <i class="fa-solid fa-trash"></i>
                                        <span>Hapus</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    {{-- Item Row Default (jika tidak ada item existing) --}}
                    <div
                        class="item-row bg-surface-base border-2 border-border-strong rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
                        <div class="space-y-3">
                            <div class="grid grid-cols-12 gap-3">
                                <div class="col-span-3">
                                    <label class="block text-xs font-semibold text-text-label mb-1.5">Quantity <span
                                            class="text-error">*</span></label>
                                    <input type="number" name="item_quantity[]"
                                        class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-center text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                        placeholder="0" min="1" required>
                                </div>
                                <div class="col-span-3">
                                    <label class="block text-xs font-semibold text-text-label mb-1.5">Satuan</label>
                                    <input type="text" name="item_satuan[]"
                                        class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-center text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                        placeholder="unit" maxlength="50">
                                </div>
                                <div class="col-span-6">
                                    <label class="block text-xs font-semibold text-text-label mb-1.5">Nama Barang <span
                                            class="text-error">*</span></label>
                                    <input type="text" name="item_nama_barang[]"
                                        class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                        placeholder="Masukkan nama barang..." required>
                                </div>
                            </div>

                            <div class="grid grid-cols-12 gap-3">
                                <div class="col-span-4">
                                    <label class="block text-xs font-semibold text-text-label mb-1.5">Harga
                                        <span class="text-error">*</span></label>
                                    <input type="text" name="item_harga[]"
                                        class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-right text-text-input price-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                        placeholder="0" required>
                                </div>
                                <div class="col-span-3">
                                    <label class="block text-xs font-semibold text-text-label mb-1.5">Jumlah</label>
                                    <div
                                        class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-right bg-surface-secondary text-text-input item-total">
                                        0
                                    </div>
                                </div>
                                <div class="col-span-5 flex items-end">
                                    <button type="button" style="display: none;"
                                        class="delete-btn w-full bg-btn-delete hover:bg-btn-delete-hover text-white px-3 py-2.5 rounded-lg text-sm font-medium shadow-sm transition-all duration-200 flex items-center justify-center gap-2">
                                        <i class="fa-solid fa-trash"></i>
                                        <span>Hapus</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>

            {{-- ═══════════════════════════════════════════════════
                 GRAND TOTAL: Total seluruh items
                 ═══════════════════════════════════════════════════ --}}
            <div class="flex justify-end mt-3">
                <div class="bg-surface-secondary border border-border-strong rounded-lg px-4 py-2">
                    <span class="text-sm font-semibold text-text-primary">Total Barang: Rp </span>
                    <span id="grandTotal-editModal-{{ $nota->id_nota }}"
                        class="text-sm font-bold text-text-heading">0</span>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════
             FIELD: Tanda Terima (Penerima)
             ═══════════════════════════════════════════════════════ --}}
        <div class="mb-3">
            <label class="block text-text-primary mb-1">Tanda Terima</label>
            <input type="text" name="penerima"
                class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
                placeholder="Nama yang menerima" maxlength="255" value="{{ $nota->penerima }}">
        </div>

    {{-- ═══════════════════════════════════════════════════════
         FIELD: Penanda Tangan (Petinggi) & Divisi
         ═══════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
        <x-forms.searchable-select
            name="petinggi_id"
            id="editModal-{{ $nota->id_nota }}-petinggi_id"
            label="Penanda Tangan (Petinggi)"
            placeholder="Cari petinggi..."
            :options="$executives->map(fn($e) => ['value' => (string) $e->id, 'label' => $e->name . ($e->position ? ' — ' . $e->position : '')])->values()"
            selected="{{ $nota->penandatangan['id'] ?? '' }}" />

        <x-forms.searchable-select
            name="divisi"
            id="editModal-{{ $nota->id_nota }}-divisi"
            label="Divisi"
            placeholder="Cari divisi..."
            :options="$divisions->map(fn($d) => ['value' => $d->name, 'label' => $d->name])->values()"
            selected="{{ $nota->penandatangan['divisi'] ?? '' }}" />
    </div>

    </x-modal>