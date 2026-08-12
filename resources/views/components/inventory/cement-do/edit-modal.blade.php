{{-- Modal Edit DO Semen --}}
<x-modal id="editModal-{{ $cementDeliveryOrder->no }}" title="Edit DO Semen"
    action="{{ route('cement-do.update', $cementDeliveryOrder->no) }}" method="PUT" buttonText="Update">

    {{-- Field: No (Readonly) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">No</label>
        <input type="text" value="{{ $cementDeliveryOrder->no }}"
            class="w-full border rounded p-2 bg-surface-hover cursor-not-allowed" readonly>
        <p class="text-xs text-text-secondary mt-1">No tidak dapat diubah</p>
    </div>

    {{-- Field: Tanggal --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="tanggal" value="{{ $cementDeliveryOrder->tanggal?->format('Y-m-d') }}"
            class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    {{-- Field: Proyek --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Proyek <span class="text-error">*</span></label>
        <input type="text" name="proyek" value="{{ $cementDeliveryOrder->proyek }}" class="w-full border rounded p-2"
            required maxlength="255" oninvalid="this.setCustomValidity('Proyek tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    {{-- Field: Volume --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Volume <span class="text-error">*</span></label>
        <input type="number" name="volume" value="{{ $cementDeliveryOrder->volume }}" class="w-full border rounded p-2"
            required min="0" oninvalid="this.setCustomValidity('Volume tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    {{-- Field: Satuan --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Satuan</label>
        <input type="text" name="satuan" value="{{ $cementDeliveryOrder->satuan }}" class="w-full border rounded p-2"
            placeholder="cth: sak / zak" maxlength="50">
    </div>

    {{-- Field: Harga --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Harga <span class="text-error">*</span></label>
        <input type="text" name="harga"
            value="Rp {{ number_format($cementDeliveryOrder->harga ?? 0, 0, ',', '.') }}" class="w-full border rounded p-2"
            required inputmode="numeric" id="edit-harga-{{ $cementDeliveryOrder->no }}"
            oninvalid="this.setCustomValidity('Harga tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    {{-- Field: Tanggal Lunas --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal Lunas</label>
        <input type="date" name="tanggal_lunas" value="{{ $cementDeliveryOrder->tanggal_lunas?->format('Y-m-d') }}"
            class="w-full border rounded p-2">
    </div>

    {{-- Field: Harga Modal --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Harga Modal <span class="text-error">*</span></label>
        <input type="text" name="harga_modal"
            value="Rp {{ number_format($cementDeliveryOrder->harga_modal ?? 0, 0, ',', '.') }}"
            class="w-full border rounded p-2" required inputmode="numeric"
            id="edit-harga-modal-{{ $cementDeliveryOrder->no }}"
            oninvalid="this.setCustomValidity('Harga modal tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>
</x-modal>
