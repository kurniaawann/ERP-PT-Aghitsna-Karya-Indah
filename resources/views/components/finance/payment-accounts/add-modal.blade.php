{{-- ==================== Modal Tambah Rekening Pembayaran ==================== --}}
<x-modal id="addModal" title="Tambah Rekening Pembayaran" action="{{ route('payment-accounts.store') }}" method="POST"
    buttonText="Simpan">

    {{-- Nama Bank --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Bank <span class="text-error">*</span></label>
        <input type="text" name="bank_name" class="w-full border rounded p-2" placeholder="Contoh: Bank BCA" required
            maxlength="255" oninvalid="this.setCustomValidity('Nama bank wajib diisi')"
            oninput="this.setCustomValidity('')">
    </div>

    {{-- Nomor Rekening --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nomor Rekening <span class="text-error">*</span></label>
        <input type="text" name="account_number" class="w-full border rounded p-2" placeholder="Contoh: 1234567890"
            required maxlength="255" oninvalid="this.setCustomValidity('Nomor rekening wajib diisi')"
            oninput="this.setCustomValidity('')">
    </div>

    {{-- Nama Pemilik Rekening --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Pemilik Rekening <span class="text-error">*</span></label>
        <input type="text" name="account_holder" class="w-full border rounded p-2"
            placeholder="Contoh: PT Aghitsna Karya Indah" required maxlength="255"
            oninvalid="this.setCustomValidity('Nama pemilik rekening wajib diisi')"
            oninput="this.setCustomValidity('')">
    </div>
</x-modal>
