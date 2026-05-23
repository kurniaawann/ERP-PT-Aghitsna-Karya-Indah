{{-- Modal Edit Nota --}}
@foreach ($notas as $nota)
    <x-modal id="editModal-{{ $nota->id_nota }}" title="Edit Nota"
        action="{{ route('nota.administrasi.update', $nota->id_nota) }}" method="PUT" buttonText="Perbarui">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
            <div>
                <label class="block text-text-primary mb-1">Lokasi</label>
                <input type="text" name="location" class="w-full border rounded p-2" placeholder="Jakarta (default)"
                    maxlength="100" value="{{ $nota->location ?? 'Jakarta' }}">
                <small class="text-gray-500 text-xs">Contoh: Jakarta, Depok, Bogor</small>
            </div>

            <div>
                <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
                <input type="date" name="nota_date" class="w-full border rounded p-2" required
                    value="{{ $nota->nota_date->format('Y-m-d') }}"
                    oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')"
                    oninput="this.setCustomValidity('')">
            </div>
        </div>

        <div class="mb-3">
            <label class="block text-text-primary mb-1">Kepada Yang Terhormat <span class="text-error">*</span></label>
            <input type="text" name="kepada" class="w-full border rounded p-2" placeholder="Nama penerima" required
                maxlength="255" value="{{ $nota->kepada }}"
                oninvalid="this.setCustomValidity('Kepada tidak boleh kosong')" oninput="this.setCustomValidity('')">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
            <div>
                <label class="block text-text-primary mb-1">Faktur No <span class="text-error">*</span></label>
                <input type="text" name="faktur_no" class="w-full border rounded p-2"
                    placeholder="Masukkan faktur no" required maxlength="100" value="{{ $nota->faktur_no }}"
                    oninvalid="this.setCustomValidity('Faktur No tidak boleh kosong')"
                    oninput="this.setCustomValidity('')">
            </div>

            <div>
                <label class="block text-text-primary mb-1">SJ.NO <span class="text-error">*</span></label>
                <input type="text" name="sj_no" class="w-full border rounded p-2" placeholder="Masukkan SJ No"
                    required maxlength="100" value="{{ $nota->sj_no }}"
                    oninvalid="this.setCustomValidity('SJ No tidak boleh kosong')" oninput="this.setCustomValidity('')">
            </div>
        </div>

        {{-- Items Section --}}
        <div class="mb-4">
            <div class="flex items-center justify-between mb-3">
                <label class="block text-text-primary font-semibold text-base">Daftar Barang <span
                        class="text-error">*</span></label>
                <button type="button" onclick="addItemRow('editModal-{{ $nota->id_nota }}')"
                    class="bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition-all duration-200">
                    <i class="fa-solid fa-plus mr-1"></i> Tambah Item
                </button>
            </div>

            <div id="itemsContainer-editModal-{{ $nota->id_nota }}" class="space-y-3">
                @forelse($nota->items as $item)
                    <div
                        class="item-row bg-white border-2 border-gray-300 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
                        <div class="space-y-3">
                            <div class="grid grid-cols-12 gap-3">
                                <div class="col-span-2">
                                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Qty <span
                                            class="text-red-500">*</span></label>
                                    <input type="number" name="item_banyaknya[]"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-center focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                        placeholder="0" min="1" value="{{ $item['banyaknya'] }}" required>
                                </div>
                                <div class="col-span-10">
                                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Nama Barang <span
                                            class="text-red-500">*</span></label>
                                    <input type="text" name="item_nama_barang[]"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                        placeholder="Masukkan nama barang..." value="{{ $item['nama_barang'] }}"
                                        required>
                                </div>
                            </div>

                            <div class="grid grid-cols-12 gap-3">
                                <div class="col-span-9">
                                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Harga Satuan
                                        <span class="text-red-500">*</span></label>
                                    <input type="text" name="item_harga_satuan[]"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-right price-input focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                        placeholder="0" value="{{ $item['harga_satuan'] }}" required>
                                </div>
                                <div class="col-span-3 flex items-end">
                                    <button type="button" onclick="removeItemRow(this)"
                                        class="delete-btn w-full bg-red-500 hover:bg-red-600 text-white px-3 py-2.5 rounded-lg text-sm font-medium shadow-sm transition-all duration-200 flex items-center justify-center gap-2">
                                        <i class="fa-solid fa-trash"></i>
                                        <span>Hapus</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div
                        class="item-row bg-white border-2 border-gray-300 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
                        <div class="space-y-3">
                            <div class="grid grid-cols-12 gap-3">
                                <div class="col-span-2">
                                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Qty <span
                                            class="text-red-500">*</span></label>
                                    <input type="number" name="item_banyaknya[]"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-center focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                        placeholder="0" min="1" required>
                                </div>
                                <div class="col-span-10">
                                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Nama Barang <span
                                            class="text-red-500">*</span></label>
                                    <input type="text" name="item_nama_barang[]"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                        placeholder="Masukkan nama barang..." required>
                                </div>
                            </div>

                            <div class="grid grid-cols-12 gap-3">
                                <div class="col-span-9">
                                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Harga Satuan
                                        <span class="text-red-500">*</span></label>
                                    <input type="text" name="item_harga_satuan[]"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-right price-input focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                        placeholder="0" required>
                                </div>
                                <div class="col-span-3 flex items-end">
                                    <button type="button" onclick="removeItemRow(this)" style="display: none;"
                                        class="delete-btn w-full bg-red-500 hover:bg-red-600 text-white px-3 py-2.5 rounded-lg text-sm font-medium shadow-sm transition-all duration-200 flex items-center justify-center gap-2">
                                        <i class="fa-solid fa-trash"></i>
                                        <span>Hapus</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="mb-3">
            <label class="block text-text-primary mb-1">Penerima s/d</label>
            <input type="text" name="penerima" class="w-full border rounded p-2" placeholder="Nama penerima"
                maxlength="255" value="{{ $nota->penerima }}">
        </div>

        {{-- Optional Fields --}}
        <div class="border-t pt-3 mt-3">
            <label class="block text-text-primary font-semibold mb-2">Biaya Tambahan (Opsional)</label>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-text-primary mb-1 text-sm">Sewa / Jual</label>
                    <input type="text" name="sewa_jual" class="w-full border rounded p-2 price-input"
                        placeholder="0"
                        value="{{ $nota->sewa_jual ? number_format($nota->sewa_jual, 0, ',', '.') : '0' }}">
                </div>

                <div>
                    <label class="block text-text-primary mb-1 text-sm">Ongkos Kirim PP / 1x</label>
                    <input type="text" name="ongkos_kirim" class="w-full border rounded p-2 price-input"
                        placeholder="0"
                        value="{{ $nota->ongkos_kirim ? number_format($nota->ongkos_kirim, 0, ',', '.') : '0' }}">
                </div>

                <div>
                    <label class="block text-text-primary mb-1 text-sm">Bongkar / Pasang</label>
                    <input type="text" name="bongkar_pasang" class="w-full border rounded p-2 price-input"
                        placeholder="0"
                        value="{{ $nota->bongkar_pasang ? number_format($nota->bongkar_pasang, 0, ',', '.') : '0' }}">
                </div>

                <div>
                    <label class="block text-text-primary mb-1 text-sm">Lembur Antar / Ambil</label>
                    <input type="text" name="lembur" class="w-full border rounded p-2 price-input"
                        placeholder="0"
                        value="{{ $nota->lembur ? number_format($nota->lembur, 0, ',', '.') : '0' }}">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-text-primary mb-1 text-sm">Uang Jaminan</label>
                    <input type="text" name="uang_jaminan" class="w-full border rounded p-2 price-input"
                        placeholder="0"
                        value="{{ $nota->uang_jaminan ? number_format($nota->uang_jaminan, 0, ',', '.') : '0' }}">
                </div>
            </div>
        </div>

        {{-- PPN Section --}}
        <div class="border-t pt-3 mt-3">
            <label class="block text-text-primary font-semibold mb-2">PPN</label>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-text-primary mb-1 text-sm">Persentase PPN (%)</label>
                    <input type="text" inputmode="decimal" name="ppn_percentage"
                        class="w-full border rounded p-2" placeholder="12,5" min="0" max="100"
                        value="{{ $nota->ppn_percentage ?? 12 }}" oninput="this.setCustomValidity('')">
                </div>
            </div>
        </div>

    </x-modal>
@endforeach
