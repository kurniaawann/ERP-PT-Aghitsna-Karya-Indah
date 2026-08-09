{{-- Modal Edit Kwintansi --}}
<x-modal id="editModal-{{ $kwintansi->id_kwintansi }}" title="Edit Kwintansi"
    action="{{ route('kwintansi.update', $kwintansi->id_kwintansi) }}" method="PUT" buttonText="Update">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">No. Kwintansi</label>
        <input type="text" class="w-full border rounded p-2 bg-gray-100" value="{{ $kwintansi->id_kwintansi }}"
            disabled>
    </div>

    @if ($kwintansi->invoice_number)
        <div class="mb-3">
            <label class="block text-text-primary mb-1">Kwitansi No.</label>
            <input type="text" class="w-full border rounded p-2 bg-gray-100"
                value="{{ $kwintansi->invoice_number }}" disabled>
        </div>
    @endif

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Sudah Terima Dari <span class="text-error">*</span></label>
        <input type="text" name="received_from" class="w-full border rounded p-2"
            value="{{ $kwintansi->received_from }}" required maxlength="255"
            oninvalid="this.setCustomValidity('Sudah terima dari tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Uang Pembayaran <span class="text-error">*</span></label>
        <textarea name="payment_for" class="w-full border rounded p-2" rows="3" required
            oninvalid="this.setCustomValidity('Uang pembayaran tidak boleh kosong')" oninput="this.setCustomValidity('')">{{ $kwintansi->payment_for }}</textarea>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Jumlah (Rp) <span class="text-error">*</span></label>
            <input type="text" inputmode="numeric" name="amount" class="w-full border rounded p-2" placeholder="Rp 0"
                value="Rp {{ number_format($kwintansi->amount, 0, ',', '.') }}" required
                oninvalid="this.setCustomValidity('Jumlah tidak boleh kosong')"
                oninput="formatCurrencyInput(this); this.setCustomValidity('')">
        </div>

        <div>
            <label class="block text-text-primary mb-1">Sisa (Rp)</label>
            <input type="text" inputmode="numeric" name="remaining" class="w-full border rounded p-2" placeholder="Rp 0"
                value="Rp {{ number_format($kwintansi->remaining, 0, ',', '.') }}"
                oninput="formatCurrencyInput(this)">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
            <input type="date" name="kwintansi_date" class="w-full border rounded p-2"
                value="{{ $kwintansi->kwintansi_date->format('Y-m-d') }}" required
                oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')">
        </div>

        <div>
            <label class="block text-text-primary mb-1">Lokasi</label>
            <input type="text" name="location" class="w-full border rounded p-2" value="{{ $kwintansi->location }}"
                maxlength="100">
        </div>
    </div>
</x-modal>
