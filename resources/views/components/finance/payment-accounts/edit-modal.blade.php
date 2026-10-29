{{-- Modal Edit Rekening Pembayaran (gaya Filament, satu per rekening) --}}
<x-fi.modal id="editModal-{{ $account->id }}" heading="Edit Rekening Pembayaran"
    :description="$account->bank_name . ' · ' . $account->account_number" icon="pencil-square"
    :action="route('payment-accounts.update', $account->id)" method="PUT" submitLabel="Update">

    {{-- Field: ID Rekening (Read-only) --}}
    <x-fi.field label="ID Rekening" hint="ID Rekening tidak dapat diubah">
        <x-fi.input :value="$account->id" prefix-icon="lock-closed" readonly />
    </x-fi.field>

    {{-- Field: Nama Bank --}}
    <x-fi.field label="Nama Bank" for="edit-bank-name-{{ $account->id }}" required>
        <x-fi.input id="edit-bank-name-{{ $account->id }}" name="bank_name" :value="$account->bank_name"
            prefix-icon="building-library" required maxlength="255"
            oninvalid="this.setCustomValidity('Nama bank wajib diisi')" oninput="this.setCustomValidity('')" />
    </x-fi.field>

    {{-- Field: Nomor Rekening --}}
    <x-fi.field label="Nomor Rekening" for="edit-account-number-{{ $account->id }}" required>
        <x-fi.input id="edit-account-number-{{ $account->id }}" name="account_number" :value="$account->account_number"
            prefix-icon="hashtag" required maxlength="255"
            oninvalid="this.setCustomValidity('Nomor rekening wajib diisi')" oninput="this.setCustomValidity('')" />
    </x-fi.field>

    {{-- Field: Nama Pemilik Rekening --}}
    <x-fi.field label="Nama Pemilik Rekening" for="edit-account-holder-{{ $account->id }}" required>
        <x-fi.input id="edit-account-holder-{{ $account->id }}" name="account_holder" :value="$account->account_holder"
            prefix-icon="user" required maxlength="255"
            oninvalid="this.setCustomValidity('Nama pemilik rekening wajib diisi')"
            oninput="this.setCustomValidity('')" />
    </x-fi.field>
</x-fi.modal>
