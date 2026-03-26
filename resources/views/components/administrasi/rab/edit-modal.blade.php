<x-modal :id="'editRABModal' . $rab->rab_number" :title="'Edit RAB ' . $rab->rab_number" :action="route('rab.update', $rab->rab_number)" method="POST" buttonText="Update" :formId="'editRABForm' . $rab->rab_number">

    @method('PUT')

    {{-- Penerima --}}
    <div class="mb-3">
        <label for="editRabRecipient{{ $rab->rab_number }}" class="block text-text-primary mb-1">Penerima <span
                class="text-error">*</span></label>
        <input type="text" class="w-full border rounded p-2" id="editRabRecipient{{ $rab->rab_number }}" name="recipient"
            value="{{ $rab->recipient }}" required>
    </div>

    {{-- Tanggal --}}
    <div class="mb-3">
        <label for="editRabDate{{ $rab->rab_number }}" class="block text-text-primary mb-1">Tanggal <span
                class="text-error">*</span></label>
        <input type="date" class="w-full border rounded p-2" id="editRabDate{{ $rab->rab_number }}" name="date"
            value="{{ $rab->date->format('Y-m-d') }}" required>
    </div>

    {{-- Alamat Penerima --}}
    <div class="mb-3">
        <label for="editRabRecipientAddress{{ $rab->rab_number }}" class="block text-text-primary mb-1">Alamat
            Penerima</label>
        <textarea class="w-full border rounded p-2" id="editRabRecipientAddress{{ $rab->rab_number }}" name="recipient_address"
            rows="2" placeholder="Masukkan alamat lengkap penerima">{{ $rab->recipient_address }}</textarea>
    </div>

    {{-- Teks Pengantar --}}
    <div class="mb-3">
        <label for="editRabIntroText{{ $rab->rab_number }}" class="block text-text-primary mb-1">Teks Pengantar <span
                class="text-error">*</span></label>
        <textarea class="w-full border rounded p-2" id="editRabIntroText{{ $rab->rab_number }}" name="intro_text"
            rows="3" required>{{ $rab->intro_text }}</textarea>
    </div>

    <hr class="my-4">

    {{-- Rekening Pembayaran --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Rekening Pembayaran <span class="text-error">*</span></label>
        <div id="editPaymentAccountsList{{ $rab->rab_number }}" class="space-y-2">
            @foreach ($paymentAccounts as $account)
                <div class="flex items-center">
                    <input class="form-check-input" type="checkbox" name="selected_payment_accounts[]"
                        value="{{ $account->id }}" id="editPaymentAccount{{ $rab->rab_number }}_{{ $account->id }}"
                        {{ in_array($account->id, (array) ($rab->selected_payment_accounts ?? [])) ? 'checked' : '' }}>
                    <label class="form-check-label ml-2 cursor-pointer text-sm"
                        for="editPaymentAccount{{ $rab->rab_number }}_{{ $account->id }}">
                        <span class="font-medium">{{ $account->bank_name }}</span> - {{ $account->account_number }}
                    </label>
                </div>
            @endforeach
        </div>
    </div>

    <input type="hidden" id="editRabDataInput{{ $rab->rab_number }}" name="rab_data" required>

</x-modal>
