{{-- Modal Tambah Return Barang --}}
<x-modal id="addModal" title="Tambah Return Barang" action="{{ route('item-return.store') }}" method="POST"
    buttonText="Simpan">
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Barang <span class="text-error">*</span></label>
        <select name="id_item" class="w-full border rounded p-2" required>
            <option value="">-- Pilih Barang --</option>
            @foreach ($items as $item)
                <option value="{{ $item->id_item }}">{{ $item->id_item }} - {{ $item->name_item }}</option>
            @endforeach
        </select>
    </div>

    <div class="grid grid-cols-2 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Jumlah <span class="text-error">*</span></label>
            <input type="number" name="quantity" value="0" class="w-full border rounded p-2" min="1"
                required>
        </div>
        <div>
            <label class="block text-text-primary mb-1">Barang Keluar (Opsional)</label>
            <select name="id_stock_out" class="w-full border rounded p-2">
                <option value="">-- Pilih Referensi --</option>
                @foreach ($stockOuts as $out)
                    <option value="{{ $out->id_stock_out }}">{{ $out->id_stock_out }} - {{ $out->item->name_item }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Alasan Return</label>
        <input type="text" name="alasan" class="w-full border rounded p-2" placeholder="Rusak, Tidak sesuai, dll">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" class="w-full border rounded p-2" required>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Keterangan</label>
        <textarea name="keterangan" class="w-full border rounded p-2" rows="3" placeholder="Masukkan keterangan..."></textarea>
    </div>
</x-modal>
