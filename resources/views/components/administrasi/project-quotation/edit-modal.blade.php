{{-- Modal Edit Penawaran Proyek --}}
@php
    $quotNum = $quotation->quotation_number;
@endphp

<x-modal id="editModal-{{ $quotNum }}" :title="auth()->user()?->isSuperAdmin() ? 'Edit Penawaran' : 'Edit Penawaran Proyek'"
    action="{{ route('project-quotation.update', $quotNum) }}" method="PUT" buttonText="Update"
    formId="editQuotationForm-{{ $quotNum }}" onsubmit="return prepareEditSubmit('{{ $quotNum }}')">

    {{-- Hidden JSON input --}}
    <input type="hidden" name="items_json" id="editItemsJson-{{ $quotNum }}">

    {{-- Error Message Area --}}
    <div id="edit-{{ $quotNum }}ModalError"
        class="hidden mb-4 p-3 bg-error-light border border-error text-error rounded-lg">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span id="edit-{{ $quotNum }}ModalErrorText"></span>
        </div>
    </div>

    <div class="space-y-5">

        {{-- Nomor Penawaran (readonly) --}}
        <div class="bg-primary-light rounded-lg p-3 flex items-center gap-3">
            <i class="fa-solid fa-hashtag text-primary"></i>
            <div>
                <p class="text-xs text-text-secondary">Nomor Penawaran</p>
                <p class="text-sm font-semibold text-primary">{{ $quotation->quotation_number }}</p>
            </div>
        </div>

        {{-- Tanggal --}}
        <div>
            <label class="block text-text-primary mb-1 text-sm font-medium">Tanggal <span
                    class="text-error">*</span></label>
            <input type="date" name="date"
                class="w-full border border-border-strong rounded-lg p-2 text-sm bg-surface-base text-text-input"
                required value="{{ $quotation->date }}"
                oninvalid="this.setCustomValidity('Tanggal penawaran harus diisi')"
                oninput="this.setCustomValidity('')">
        </div>

        {{-- Perihal --}}
        <div>
            <label class="block text-text-primary mb-1 text-sm font-medium">Perihal (Hal)</label>
            <input type="text" name="subject"
                class="w-full border border-border-strong rounded-lg p-2 text-sm bg-surface-base text-text-input"
                value="{{ $quotation->subject }}" maxlength="255"
                oninvalid="this.setCustomValidity('Perihal maksimal 255 karakter')"
                oninput="this.setCustomValidity('')">
        </div>

        {{-- Kepada --}}
        <div>
            <label class="block text-text-primary mb-1 text-sm font-medium">Kepada Yth <span
                    class="text-error">*</span></label>
            <input type="text" name="recipient"
                class="w-full border border-border-strong rounded-lg p-2 text-sm bg-surface-base text-text-input"
                placeholder="Nama penerima / perusahaan" required maxlength="255" value="{{ $quotation->recipient }}"
                oninvalid="this.setCustomValidity('Nama penerima harus diisi')" oninput="this.setCustomValidity('')">
        </div>

        {{-- Alamat --}}
        <div>
            <label class="block text-text-primary mb-1 text-sm font-medium">Alamat</label>
            <input type="text" name="recipient_address"
                class="w-full border border-border-strong rounded-lg p-2 text-sm bg-surface-base text-text-input"
                value="{{ $quotation->recipient_address }}" maxlength="255"
                oninvalid="this.setCustomValidity('Alamat maksimal 255 karakter')" oninput="this.setCustomValidity('')">
        </div>

        {{-- ═══ ITEMS SECTION (FLAT - NO GROUPING) ═══════════════════════════════ --}}
        <div>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-text-primary uppercase tracking-wide">
                    <i class="fa-solid fa-list text-primary mr-1"></i>
                    Daftar Item
                </h3>
                <button type="button" onclick="addItem('edit-{{ $quotNum }}')"
                    class="flex items-center gap-2 bg-btn-add hover:bg-btn-add-hover text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition-all duration-200">
                    <i class="fa-solid fa-plus"></i> Tambah Item
                </button>
            </div>

            <div id="editItemsContainer-{{ $quotNum }}" class="space-y-3">
                {{-- Items rendered by JS --}}
            </div>

            {{-- Grand Total --}}
            <div class="mt-4 flex justify-end">
                <div class="bg-warning-light border border-warning-light rounded-lg px-5 py-3 text-right min-w-[220px]">
                    <p class="text-xs text-text-secondary mb-1">Grand Total</p>
                    <p class="text-lg font-bold text-text-heading" id="editGrandTotal-{{ $quotNum }}">Rp 0</p>
                </div>
            </div>
        </div>

        {{-- Rekening Pembayaran --}}
        <div>
            <label class="block text-text-primary mb-2 text-sm font-medium">
                Rekening Pembayaran <span class="text-error">*</span>
            </label>
            <div class="space-y-2">
                @php
                    $selectedAccounts = is_string($quotation->selected_payment_accounts)
                        ? json_decode($quotation->selected_payment_accounts, true)
                        : $quotation->selected_payment_accounts ?? [];
                @endphp
                @foreach ($paymentAccounts as $account)
                    <label
                        class="flex items-center gap-3 p-3 border border-border-strong rounded-lg cursor-pointer hover:bg-surface-hover">
                        <input type="checkbox" name="selected_payment_accounts[]" value="{{ $account->id }}"
                            class="w-4 h-4 accent-primary payment-account-checkbox-{{ $quotNum }}"
                            {{ in_array($account->id, $selectedAccounts) ? 'checked' : '' }}
                            {{ $loop->first ? 'required' : '' }}
                            oninvalid="this.setCustomValidity('Minimal 1 rekening pembayaran harus dipilih')"
                            oninput="this.setCustomValidity('')"
                            onchange="document.querySelectorAll('.payment-account-checkbox-{{ $quotNum }}').forEach(cb => cb.required = !document.querySelector('.payment-account-checkbox-{{ $quotNum }}:checked'))">
                        <span class="text-sm">
                            <strong>{{ $account->bank_name }}</strong> /
                            {{ $account->account_number }} a/n {{ $account->account_holder }}
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Ditandatangani Oleh --}}
        <div>
            <label class="block text-text-primary mb-1 text-sm font-medium">Ditandatangani Oleh</label>
            <input type="text" name="signed_by"
                class="w-full border border-border-strong rounded-lg p-2 text-sm bg-surface-base text-text-input"
                placeholder="Nama penandatangan" maxlength="255" value="{{ $quotation->signed_by }}"
                oninvalid="this.setCustomValidity('Nama penandatangan maksimal 255 karakter')"
                oninput="this.setCustomValidity('')">
        </div>

        {{-- Divisi --}}
        <div>
            <label class="block text-text-primary mb-1 text-sm font-medium">Divisi</label>
            <input type="text" name="division"
                class="w-full border border-border-strong rounded-lg p-2 text-sm bg-surface-base text-text-input"
                placeholder="Contoh: Divisi Alumunium" maxlength="255" value="{{ $quotation->division }}"
                oninvalid="this.setCustomValidity('Nama divisi maksimal 255 karakter')"
                oninput="this.setCustomValidity('')">
        </div>

    </div>{{-- end space-y-5 --}}
</x-modal>

<script>
    // Initialize edit modal data when document ready
    (function() {
        const quotNum = '{{ $quotNum }}';
        const quotationData = @json($quotation);

        // Store items for this quotation
        if (!window.editItemsStore) {
            window.editItemsStore = {};
        }

        window.editItemsStore[quotNum] = quotationData.items.map((item, idx) => ({
            id: idx + 1,
            order_number: item.order_number,
            description: item.description,
            volume: item.volume || '',
            unit: item.unit || '',
            unit_price: item.unit_price,
            total_price: item.total_price
        }));

        // Initialize next item ID
        if (!window.editNextItemId) {
            window.editNextItemId = {};
        }
        window.editNextItemId[quotNum] = Math.max(...window.editItemsStore[quotNum].map(i => i.id), 0) + 1;
    })();
</script>
