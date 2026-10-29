{{-- Modal Tambah Rekening Pembayaran (gaya Filament) --}}
<x-fi.modal id="addModal" heading="Tambah Rekening Pembayaran" icon="building-library"
    :action="route('payment-accounts.store')" method="POST" submitLabel="Simpan">

    {{-- Field: Nama Bank --}}
    <x-fi.field label="Nama Bank" for="add-bank-name" required>
        <x-fi.input id="add-bank-name" name="bank_name" placeholder="Contoh: Bank BCA" prefix-icon="building-library"
            required maxlength="255" oninvalid="this.setCustomValidity('Nama bank wajib diisi')"
            oninput="this.setCustomValidity('')" />
    </x-fi.field>

    {{-- Field: Nomor Rekening --}}
    <x-fi.field label="Nomor Rekening" for="add-account-number" required>
        <x-fi.input id="add-account-number" name="account_number" placeholder="Contoh: 1234567890" prefix-icon="hashtag"
            required maxlength="255" oninvalid="this.setCustomValidity('Nomor rekening wajib diisi')"
            oninput="this.setCustomValidity('')" />
    </x-fi.field>

    {{-- Field: Nama Pemilik Rekening --}}
    <x-fi.field label="Nama Pemilik Rekening" for="add-account-holder" required>
        <x-fi.input id="add-account-holder" name="account_holder" placeholder="Contoh: PT Aghitsna Karya Indah"
            prefix-icon="user" required maxlength="255"
            oninvalid="this.setCustomValidity('Nama pemilik rekening wajib diisi')"
            oninput="this.setCustomValidity('')" />
    </x-fi.field>
</x-fi.modal>
