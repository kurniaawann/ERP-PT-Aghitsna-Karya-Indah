{{-- Modal Tambah Surat Jalan --}}
<x-modal id="addModal" title="Tambah Surat Jalan" action="{{ route('delivery-note.administrasi.store') }}" method="POST"
    buttonText="Simpan">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Nomor Dokumen <span class="text-error">*</span></label>
            <input type="text" id="documentNumber" name="document_number" class="w-full border rounded p-2 bg-gray-100"
                placeholder="Otomatis di-generate" readonly required maxlength="100">
        </div>

        <div>
            <label class="block text-text-primary mb-1">Tanggal Pengiriman <span class="text-error">*</span></label>
            <input type="date" name="delivery_date" class="w-full border rounded p-2" required
                value="{{ date('Y-m-d') }}">
        </div>
    </div>

    {{-- Shipper Info --}}
    <fieldset class="border border-gray-300 rounded-lg p-3 mb-3">
        <legend class="text-sm font-semibold text-text-primary px-2">Informasi Pengirim</legend>

        <div class="mb-2">
            <label class="block text-text-primary text-sm mb-1">Nama Pengirim <span class="text-error">*</span></label>
            <input type="text" name="shipper_name" class="w-full border rounded p-2 text-sm"
                placeholder="Masukkan nama pengirim" required maxlength="255">
        </div>

        <div>
            <label class="block text-text-primary text-sm mb-1">Alamat Pengirim <span
                    class="text-error">*</span></label>
            <textarea name="shipper_address" class="w-full border rounded p-2 text-sm" placeholder="Masukkan alamat pengirim"
                rows="2" required></textarea>
        </div>
    </fieldset>

    {{-- Receiver Info --}}
    <fieldset class="border border-gray-300 rounded-lg p-3 mb-3">
        <legend class="text-sm font-semibold text-text-primary px-2">Informasi Penerima</legend>

        <div class="mb-2">
            <label class="block text-text-primary text-sm mb-1">Nama Penerima <span class="text-error">*</span></label>
            <input type="text" name="receiver_name" class="w-full border rounded p-2 text-sm"
                placeholder="Masukkan nama penerima" required maxlength="255">
        </div>

        <div>
            <label class="block text-text-primary text-sm mb-1">Alamat Penerima <span
                    class="text-error">*</span></label>
            <textarea name="receiver_address" class="w-full border rounded p-2 text-sm" placeholder="Masukkan alamat penerima"
                rows="2" required></textarea>
        </div>
    </fieldset>

    {{-- Description --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Deskripsi</label>
        <textarea name="description" class="w-full border rounded p-2" placeholder="Masukkan deskripsi" rows="2"></textarea>
    </div>

    {{-- Items Section --}}
    <div class="mb-4">
        <div class="flex items-center justify-between mb-3">
            <label class="block text-text-primary font-semibold text-base">Barang <span
                    class="text-error">*</span></label>
            <button type="button" onclick="addItemRow('addModal')"
                class="bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition-all duration-200">
                <i class="fa-solid fa-plus mr-1"></i> Tambah Barang
            </button>
        </div>

        <div id="itemsContainer-addModal" class="space-y-3">
            {{-- Initial item row --}}
            <div
                class="item-row bg-white border-2 border-gray-300 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">No</label>
                        <input type="number" name="item_no[]"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-center focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                            placeholder="1" min="1" value="1" required readonly>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Nama Barang <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="item_name[]"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                            placeholder="Masukkan nama barang..." required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Jumlah <span
                                class="text-red-500">*</span></label>
                        <input type="number" name="quantity[]"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-center focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                            placeholder="0" min="1" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Satuan</label>
                        <input type="text" name="unit[]"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                            placeholder="pcs" value="pcs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Catatan</label>
                        <input type="text" name="item_notes[]"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                            placeholder="Masukkan catatan...">
                    </div>
                    <button type="button" onclick="removeItemRow(this)" style="display: none;"
                        class="delete-btn w-full bg-red-500 hover:bg-red-600 text-white px-3 py-2.5 rounded-lg text-sm font-medium shadow-sm transition-all duration-200 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-trash"></i>
                        <span>Hapus</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Driver & Vehicle Info --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Nama Sopir</label>
            <input type="text" name="driver_name" class="w-full border rounded p-2"
                placeholder="Masukkan nama sopir" maxlength="255">
        </div>

        <div>
            <label class="block text-text-primary mb-1">Nomor Kendaraan</label>
            <input type="text" name="vehicle_number" class="w-full border rounded p-2"
                placeholder="Masukkan nomor kendaraan" maxlength="100">
        </div>
    </div>

    {{-- Status (Hidden - always Draft) --}}
    <input type="hidden" name="status" value="draft">

    {{-- Additional Notes --}}
    <div>
        <label class="block text-text-primary mb-1">Catatan Tambahan</label>
        <textarea name="notes" class="w-full border rounded p-2" placeholder="Masukkan catatan tambahan" rows="2"></textarea>
    </div>

</x-modal>
