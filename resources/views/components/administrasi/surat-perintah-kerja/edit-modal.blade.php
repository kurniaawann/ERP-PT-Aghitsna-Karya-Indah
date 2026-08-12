{{-- =====================================================================
     Komponen Modal Edit Surat Perintah Kerja (SPK)

     Form edit SPK yang sudah ada. Satu modal per data.
     Field sama dengan form tambah, namun sudah terisi data existing.
     Nomor SPK tidak dapat diubah.
     ===================================================================== --}}

<x-modal id="editModal-{{ $spk->nomor }}" title="Edit Surat Perintah Kerja"
    action="{{ route('surat-perintah-kerja.administrasi.update', $spk->nomor) }}" method="PUT" buttonText="Perbarui"
    size="4xl">

    {{-- Nomor SPK & Tanggal --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Nomor SPK <span class="text-error">*</span></label>
            <input type="text"
                class="w-full border border-border-strong rounded p-2 bg-surface-hover text-text-input"
                value="{{ $spk->nomor }}" readonly>
        </div>

        <div>
            <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
            <input type="date" name="tanggal"
                class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input" required
                value="{{ $spk->tanggal->format('Y-m-d') }}">
        </div>
    </div>

    {{-- Proyek & Lokasi --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Proyek <span class="text-error">*</span></label>
            <input type="text" name="proyek"
                class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
                placeholder="Masukkan nama proyek" value="{{ $spk->proyek }}" required maxlength="255">
        </div>

        <div>
            <label class="block text-text-primary mb-1">Lokasi <span class="text-error">*</span></label>
            <input type="text" name="lokasi"
                class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
                placeholder="Masukkan lokasi proyek" value="{{ $spk->lokasi }}" required maxlength="255">
        </div>
    </div>

    {{-- Yang Bertanda Tangan Di Bawah Ini --}}
    <fieldset class="border border-border-strong rounded p-3 mb-3">
        <legend class="text-sm font-semibold text-text-primary px-2">Yang Bertanda Tangan Di Bawah Ini</legend>

        <div class="mb-2">
            <label class="block text-text-primary text-sm mb-1">Nama <span class="text-error">*</span></label>
            <input type="text" name="signer_nama"
                class="w-full border border-border-strong rounded p-2 text-sm bg-surface-base text-text-input"
                placeholder="Nama penandatangan" value="{{ $spk->signer_nama }}" required maxlength="255">
        </div>

        <div>
            <label class="block text-text-primary text-sm mb-1">Jabatan <span class="text-error">*</span></label>
            <input type="text" name="signer_jabatan"
                class="w-full border border-border-strong rounded p-2 text-sm bg-surface-base text-text-input"
                placeholder="Jabatan penandatangan" value="{{ $spk->signer_jabatan }}" required maxlength="255">
        </div>
    </fieldset>

    {{-- Pemberi Tugas --}}
    <fieldset class="border border-border-strong rounded p-3 mb-3">
        <legend class="text-sm font-semibold text-text-primary px-2">Pemberi Tugas</legend>

        <div class="mb-2">
            <label class="block text-text-primary text-sm mb-1">Nama <span class="text-error">*</span></label>
            <input type="text" name="pemberi_tugas_nama"
                class="w-full border border-border-strong rounded p-2 text-sm bg-surface-base text-text-input"
                placeholder="Nama pemberi tugas" value="{{ $spk->pemberi_tugas_nama }}" required maxlength="255">
        </div>

        <div>
            <label class="block text-text-primary text-sm mb-1">Alamat <span class="text-error">*</span></label>
            <textarea name="pemberi_tugas_alamat" rows="2"
                class="w-full border border-border-strong rounded p-2 text-sm bg-surface-base text-text-input"
                placeholder="Alamat pemberi tugas" required>{{ $spk->pemberi_tugas_alamat }}</textarea>
        </div>
    </fieldset>

    {{-- Section Daftar Item Pekerjaan (No/Kode > banyak Keterangan) --}}
    <div class="mb-4">
        <div class="flex items-center justify-between mb-3">
            <label class="block text-text-primary font-semibold text-base">Item Pekerjaan <span
                    class="text-error">*</span></label>
            <button type="button" onclick="addGroupRow('editModal-{{ $spk->nomor }}')"
                class="bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded text-sm font-medium shadow-sm transition-all duration-200">
                <i class="fa-solid fa-plus mr-1"></i> Tambah No/Kode
            </button>
        </div>

        <div id="groupsContainer-editModal-{{ $spk->nomor }}" class="space-y-4">
            @forelse($spk->items as $key => $item)
                <div class="group-row bg-surface-base border-2 border-border-strong rounded p-4 shadow-sm hover:shadow-md transition-shadow">
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-xs font-semibold text-text-label mb-1.5">No</label>
                            <input type="number" name="no[{{ $key }}]"
                                class="group-no w-full border border-border-strong rounded px-3 py-2.5 text-sm text-center text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                value="{{ data_get($item, 'no', $key + 1) }}" min="1" required readonly>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-text-label mb-1.5">Kode</label>
                            <input type="text" name="kode[{{ $key }}]"
                                class="group-kode w-full border border-border-strong rounded px-3 py-2.5 text-sm text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                placeholder="Kode pekerjaan" maxlength="100" value="{{ data_get($item, 'kode', '') }}">
                        </div>
                    </div>

                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-text-label">Keterangan</span>
                        <button type="button" onclick="addDetailRow(this)"
                            class="bg-primary hover:bg-primary-hover text-white px-3 py-2 rounded text-xs font-medium shadow-sm transition-all duration-200">
                            <i class="fa-solid fa-plus mr-1"></i> Tambah Keterangan
                        </button>
                    </div>

                    <div class="details-container space-y-2">
                        @forelse(data_get($item, 'details', []) as $dk => $detail)
                            <div class="detail-row border border-border-strong rounded-lg p-3">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-xs font-semibold text-text-label">Keterangan <span
                                            class="text-error">*</span></span>
                                    <button type="button" onclick="removeDetailRow(this)"
                                        class="delete-detail-btn bg-btn-delete hover:bg-btn-delete-hover text-white px-2.5 py-1.5 rounded-lg transition-all duration-200"
                                        title="Hapus Keterangan">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                                <textarea name="detail_keterangan[{{ $key }}][]" rows="2"
                                    class="w-full border border-border-strong rounded px-3 py-2 text-sm text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                    placeholder="Uraian pekerjaan..." required>{{ data_get($detail, 'keterangan', '') }}</textarea>

                                <div class="mt-2">
                                    <label class="block text-xs font-semibold text-text-label mb-1">Volume <span
                                            class="text-error">*</span></label>
                                    <input type="number" name="detail_volume[{{ $key }}][]" step="any" min="0"
                                        class="form-qty w-full border border-border-strong rounded px-2 py-2 text-sm text-center text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                        value="{{ data_get($detail, 'volume', 0) }}" required>
                                </div>
                                <div class="mt-2">
                                    <label class="block text-xs font-semibold text-text-label mb-1">Satuan</label>
                                    <input type="text" name="detail_satuan[{{ $key }}][]"
                                        class="w-full border border-border-strong rounded px-2 py-2 text-sm text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                        value="{{ data_get($detail, 'satuan', '') }}">
                                </div>
                                <div class="mt-2">
                                    <label class="block text-xs font-semibold text-text-label mb-1">Harga <span
                                            class="text-error">*</span></label>
                                    <input type="text" inputmode="numeric" name="detail_harga[{{ $key }}][]"
                                        class="form-price w-full border border-border-strong rounded px-2 py-2 text-sm text-right text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                        value="{{ number_format(data_get($detail, 'harga', 0), 0, ',', '.') }}" required
                                        oninput="formatCurrencyInput(this)">
                                </div>
                                <div class="mt-2">
                                    <label class="block text-xs font-semibold text-text-label mb-1">Jumlah</label>
                                    <input type="text" name="detail_jumlah[{{ $key }}][]" readonly
                                        class="form-amount w-full border border-border-strong rounded px-2 py-2 text-sm text-right text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all bg-surface-hover"
                                        value="{{ number_format(data_get($detail, 'jumlah', 0), 0, ',', '.') }}">
                                </div>
                            </div>
                        @empty
                            <div class="detail-row border border-border-strong rounded-lg p-3">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-xs font-semibold text-text-label">Keterangan <span
                                            class="text-error">*</span></span>
                                    <button type="button" onclick="removeDetailRow(this)" style="display: none;"
                                        class="delete-detail-btn bg-btn-delete hover:bg-btn-delete-hover text-white px-2.5 py-1.5 rounded-lg transition-all duration-200"
                                        title="Hapus Keterangan">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                                <textarea name="detail_keterangan[{{ $key }}][]" rows="2"
                                    class="w-full border border-border-strong rounded px-3 py-2 text-sm text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                    placeholder="Uraian pekerjaan..." required></textarea>

                                <div class="mt-2">
                                    <label class="block text-xs font-semibold text-text-label mb-1">Volume <span
                                            class="text-error">*</span></label>
                                    <input type="number" name="detail_volume[{{ $key }}][]" step="any" min="0"
                                        class="form-qty w-full border border-border-strong rounded px-2 py-2 text-sm text-center text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                        placeholder="0" required>
                                </div>
                                <div class="mt-2">
                                    <label class="block text-xs font-semibold text-text-label mb-1">Satuan</label>
                                    <input type="text" name="detail_satuan[{{ $key }}][]"
                                        class="w-full border border-border-strong rounded px-2 py-2 text-sm text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                        placeholder="bh / m2 / lsn">
                                </div>
                                <div class="mt-2">
                                    <label class="block text-xs font-semibold text-text-label mb-1">Harga <span
                                            class="text-error">*</span></label>
                                    <input type="text" inputmode="numeric" name="detail_harga[{{ $key }}][]"
                                        class="form-price w-full border border-border-strong rounded px-2 py-2 text-sm text-right text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                        placeholder="Rp 0" required oninput="formatCurrencyInput(this)">
                                </div>
                                <div class="mt-2">
                                    <label class="block text-xs font-semibold text-text-label mb-1">Jumlah</label>
                                    <input type="text" name="detail_jumlah[{{ $key }}][]" readonly
                                        class="form-amount w-full border border-border-strong rounded px-2 py-2 text-sm text-right text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all bg-surface-hover"
                                        placeholder="Rp 0">
                                </div>
                            </div>
                        @endforelse
                    </div>

                    <button type="button" onclick="removeGroupRow(this)"
                        class="delete-group-btn w-full bg-btn-delete hover:bg-btn-delete-hover text-white px-3 py-2.5 rounded text-sm font-medium shadow-sm transition-all duration-200 flex items-center justify-center gap-2 mt-3">
                        <i class="fa-solid fa-trash"></i>
                        <span>Hapus No/Kode</span>
                    </button>
                </div>
            @empty
                <div class="group-row bg-surface-base border-2 border-border-strong rounded p-4 shadow-sm hover:shadow-md transition-shadow">
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-xs font-semibold text-text-label mb-1.5">No</label>
                            <input type="number" name="no[0]"
                                class="group-no w-full border border-border-strong rounded px-3 py-2.5 text-sm text-center text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                value="1" min="1" required readonly>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-text-label mb-1.5">Kode</label>
                            <input type="text" name="kode[0]"
                                class="group-kode w-full border border-border-strong rounded px-3 py-2.5 text-sm text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                placeholder="Kode pekerjaan" maxlength="100">
                        </div>
                    </div>

                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-text-label">Keterangan</span>
                        <button type="button" onclick="addDetailRow(this)"
                            class="bg-primary hover:bg-primary-hover text-white px-3 py-2 rounded text-xs font-medium shadow-sm transition-all duration-200">
                            <i class="fa-solid fa-plus mr-1"></i> Tambah Keterangan
                        </button>
                    </div>

                    <div class="details-container space-y-2">
                        <div class="detail-row border border-border-strong rounded-lg p-3">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-semibold text-text-label">Keterangan <span
                                        class="text-error">*</span></span>
                                <button type="button" onclick="removeDetailRow(this)" style="display: none;"
                                    class="delete-detail-btn bg-btn-delete hover:bg-btn-delete-hover text-white px-2.5 py-1.5 rounded-lg transition-all duration-200"
                                    title="Hapus Keterangan">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                            <textarea name="detail_keterangan[0][]" rows="2"
                                class="w-full border border-border-strong rounded px-3 py-2 text-sm text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                placeholder="Uraian pekerjaan..." required></textarea>

                            <div class="mt-2">
                                <label class="block text-xs font-semibold text-text-label mb-1">Volume <span
                                        class="text-error">*</span></label>
                                <input type="number" name="detail_volume[0][]" step="any" min="0"
                                    class="form-qty w-full border border-border-strong rounded px-2 py-2 text-sm text-center text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                    placeholder="0" required>
                            </div>
                            <div class="mt-2">
                                <label class="block text-xs font-semibold text-text-label mb-1">Satuan</label>
                                <input type="text" name="detail_satuan[0][]"
                                    class="w-full border border-border-strong rounded px-2 py-2 text-sm text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                    placeholder="bh / m2 / lsn">
                            </div>
                            <div class="mt-2">
                                <label class="block text-xs font-semibold text-text-label mb-1">Harga <span
                                        class="text-error">*</span></label>
                                <input type="text" inputmode="numeric" name="detail_harga[0][]"
                                    class="form-price w-full border border-border-strong rounded px-2 py-2 text-sm text-right text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                    placeholder="Rp 0" required oninput="formatCurrencyInput(this)">
                            </div>
                            <div class="mt-2">
                                <label class="block text-xs font-semibold text-text-label mb-1">Jumlah</label>
                                <input type="text" name="detail_jumlah[0][]" readonly
                                    class="form-amount w-full border border-border-strong rounded px-2 py-2 text-sm text-right text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all bg-surface-hover"
                                    placeholder="Rp 0">
                            </div>
                        </div>
                    </div>

                    <button type="button" onclick="removeGroupRow(this)" style="display: none;"
                        class="delete-group-btn w-full bg-btn-delete hover:bg-btn-delete-hover text-white px-3 py-2.5 rounded text-sm font-medium shadow-sm transition-all duration-200 flex items-center justify-center gap-2 mt-3">
                        <i class="fa-solid fa-trash"></i>
                        <span>Hapus No/Kode</span>
                    </button>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Total --}}
    <div class="flex items-center justify-end gap-2 mb-3">
        <span class="text-sm font-semibold text-text-label">Total Keseluruhan:</span>
        <span class="total-display text-lg font-bold text-primary">Rp {{ number_format($spk->total_amount, 0, ',', '.') }}</span>
    </div>

</x-modal>
