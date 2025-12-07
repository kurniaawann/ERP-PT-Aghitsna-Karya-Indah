{{-- Modal Edit Rekening Pembayaran --}}
<x-modal id="editModal-{{ $account->id }}" title="Edit Rekening Pembayaran"
    action="{{ route('payment-accounts.update', $account->id) }}" method="PUT" buttonText="Update">

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">ID Rekening</label>
        <input type="text" value="{{ $account->id }}" class="w-full border rounded p-2 bg-gray-100 cursor-not-allowed"
            readonly>
        <p class="text-xs text-gray-500 mt-1">ID Rekening tidak dapat diubah</p>
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Nama Bank <span class="text-error">*</span></label>
        <input type="text" name="bank_name" value="{{ $account->bank_name }}" class="w-full border rounded p-2"
            required maxlength="255" oninvalid="this.setCustomValidity('Nama bank wajib diisi')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Nomor Rekening <span class="text-error">*</span></label>
        <input type="text" name="account_number" value="{{ $account->account_number }}"
            class="w-full border rounded p-2" required maxlength="255"
            oninvalid="this.setCustomValidity('Nomor rekening wajib diisi')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Nama Pemilik Rekening <span class="text-error">*</span></label>
        <input type="text" name="account_holder" value="{{ $account->account_holder }}"
            class="w-full border rounded p-2" required maxlength="255"
            oninvalid="this.setCustomValidity('Nama pemilik rekening wajib diisi')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Urutan (Order) <span class="text-error">*</span></label>
        <input type="number" name="order" value="{{ $account->order }}" class="w-full border rounded p-2"
            min="1" required oninvalid="this.setCustomValidity('Urutan wajib diisi (minimal 1)')"
            oninput="this.setCustomValidity('')">
        <p class="text-xs text-gray-500 mt-1">Ubah urutan untuk mengatur tampilan rekening di invoice</p>
    </div>
</x-modal>
