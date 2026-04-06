{{-- Modal Tambah Barang Masuk --}}
<x-modal id="addModal" title="Tambah Barang Masuk" action="{{ route('stock-in.store') }}" method="POST"
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
            <label class="block text-text-primary mb-1">Harga Modal <span class="text-error">*</span></label>
            <input type="number" name="capital_price" value="0" class="w-full border rounded p-2" min="0"
                required>
        </div>
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
