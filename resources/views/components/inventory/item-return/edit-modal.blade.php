{{-- Modal Edit Return Barang --}}
@foreach (isset($record) ? [$record] : [] as $item)
    <x-modal id="editModal-{{ $item->id_return }}" title="Edit Return Barang"
        action="{{ route('item-return.update', $item->id_return) }}" method="POST" buttonText="Update">
        @method('PUT')

        {{-- Error Alert - Show Laravel Validation Errors --}}
        @if ($errors->any())
            <div id="editErrorAlert-{{ $item->id_return }}" class="mb-4 p-3 bg-red-50 border border-red-300 rounded">
                <div class="font-semibold mb-2 text-red-700"><i class="fa-solid fa-circle-exclamation"></i> Validasi Gagal
                </div>
                @foreach ($errors->all() as $error)
                    <p class="text-sm text-red-600 mb-1">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="mb-3">
            <label class="block text-text-primary mb-1">Tipe Return</label>
            <input type="text" value="{{ ucfirst($item->return_type) }}"
                class="w-full border rounded p-2 bg-gray-100" disabled>
        </div>

        <div class="mb-3">
            <label class="block text-text-primary mb-1">Barang</label>
            <input type="text" value="{{ $item->id_item }} - {{ $item->item->name_item }}"
                class="w-full border rounded p-2 bg-gray-100" disabled>
        </div>

        <div class="mb-3">
            <label class="block text-text-primary mb-1">Jumlah <span class="text-error">*</span></label>
            <input type="number" name="quantity" value="{{ $item->quantity }}" class="w-full border rounded p-2"
                min="1" required>
        </div>

        <div class="mb-3">
            <label class="block text-text-primary mb-1">Alasan Return</label>
            <input type="text" name="alasan" value="{{ $item->alasan }}" class="w-full border rounded p-2"
                placeholder="Rusak, Tidak sesuai, dll">
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
