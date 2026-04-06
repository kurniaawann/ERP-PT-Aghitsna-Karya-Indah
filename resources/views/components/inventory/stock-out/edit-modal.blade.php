{{-- Modal Edit Barang Keluar --}}
@foreach (isset($record) ? [$record] : [] as $item)
    <x-modal id="editModal-{{ $item->id_stock_out }}" title="Edit Barang Keluar"
        action="{{ route('stock-out.update', $item->id_stock_out) }}" method="POST" buttonText="Update">
        @method('PUT')

        <div class="mb-3">
            <label class="block text-text-primary mb-1">Barang</label>
            <input type="text" value="{{ $item->id_item }} - {{ $item->item->name_item }}"
                class="w-full border rounded p-2 bg-gray-100" disabled>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-3">
            <div>
                <label class="block text-text-primary mb-1">Jumlah <span class="text-error">*</span></label>
                <input type="number" name="quantity" value="{{ $item->quantity }}" class="w-full border rounded p-2"
                    min="1" required>
            </div>
            <div>
                <label class="block text-text-primary mb-1">Kategori <span class="text-error">*</span></label>
                <select name="kategori" class="w-full border rounded p-2" required>
                    <option value="Penjualan" {{ $item->kategori === 'Penjualan' ? 'selected' : '' }}>Penjualan</option>
                    <option value="Proyek" {{ $item->kategori === 'Proyek' ? 'selected' : '' }}>Proyek</option>
                    <option value="Transfer" {{ $item->kategori === 'Transfer' ? 'selected' : '' }}>Transfer</option>
                    <option value="Lainnya" {{ $item->kategori === 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
            <input type="date" name="tanggal" value="{{ $item->tanggal->format('Y-m-d') }}"
                class="w-full border rounded p-2" required>
        </div>

        <div class="mb-3">
            <label class="block text-text-primary mb-1">Keterangan</label>
            <textarea name="keterangan" class="w-full border rounded p-2" rows="3">{{ $item->keterangan }}</textarea>
        </div>
    </x-modal>
@endforeach
