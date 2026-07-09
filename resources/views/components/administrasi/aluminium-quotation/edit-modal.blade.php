{{-- Modal Edit Penawaran Proyek --}}
<x-modal id="editModal-{{ $quotation->quotation_number }}" title="Edit Penawaran — {{ $quotation->quotation_number }}"
    action="{{ route('aluminium-quotation.update', $quotation->quotation_number) }}" method="PUT" buttonText="Update"
    formId="editQuotationForm-{{ $quotation->quotation_number }}"
    onsubmit="return prepareEditSubmit('{{ $quotation->quotation_number }}')">

    {{-- Hidden JSON input --}}
    <input type="hidden" name="groups_json" id="editGroupsJson-{{ $quotation->quotation_number }}">

    {{-- Error Message Area --}}
    <div id="editModal-{{ $quotation->quotation_number }}ModalError"
        class="hidden mb-4 p-3 bg-error-light border border-error text-error rounded">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span id="editModal-{{ $quotation->quotation_number }}ModalErrorText"></span>
        </div>
    </div>

    <div class="space-y-5">

        {{-- Tanggal --}}
        <div>
            <label class="block text-text-primary mb-1 text-sm font-medium">Tanggal <span
                    class="text-error">*</span></label>
            <input type="date" name="date"
                class="w-full border border-border-strong rounded p-2 text-sm bg-surface-base text-text-input"
                required value="{{ $quotation->date->format('Y-m-d') }}"
                oninvalid="this.setCustomValidity('Tanggal penawaran harus diisi')"
                oninput="this.setCustomValidity('')">
        </div>

        {{-- Perihal --}}
        <div>
            <label class="block text-text-primary mb-1 text-sm font-medium">Perihal (Hal)</label>
            <input type="text" name="subject"
                class="w-full border border-border-strong rounded p-2 text-sm bg-surface-base text-text-input"
                value="{{ $quotation->subject }}" maxlength="255"
                oninvalid="this.setCustomValidity('Perihal maksimal 255 karakter')"
                oninput="this.setCustomValidity('')">
        </div>

        {{-- Kepada --}}
        <div>
            <label class="block text-text-primary mb-1 text-sm font-medium">Kepada Yth <span
                    class="text-error">*</span></label>
            <input type="text" name="recipient"
                class="w-full border border-border-strong rounded p-2 text-sm bg-surface-base text-text-input"
                value="{{ $quotation->recipient }}" required maxlength="255"
                oninvalid="this.setCustomValidity('Nama penerima harus diisi')" oninput="this.setCustomValidity('')">
        </div>

        {{-- Alamat --}}
        <div>
            <label class="block text-text-primary mb-1 text-sm font-medium">Alamat</label>
            <input type="text" name="recipient_address"
                class="w-full border border-border-strong rounded p-2 text-sm bg-surface-base text-text-input"
                value="{{ $quotation->recipient_address }}" maxlength="255"
                oninvalid="this.setCustomValidity('Alamat maksimal 255 karakter')" oninput="this.setCustomValidity('')">
        </div>

        {{-- ═══ GROUPS SECTION ═════════════════════════════════════════════════════ --}}
        <div>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-text-primary uppercase tracking-wide">
                    <i class="fa-solid fa-layer-group text-primary mr-1"></i>
                    Kelompok Item
                </h3>
                <button type="button" onclick="addGroup('edit-{{ $quotation->quotation_number }}')"
                    class="flex items-center gap-2 bg-btn-add hover:bg-btn-add-hover text-white px-4 py-2 rounded text-sm font-medium shadow-sm transition-all duration-200">
                    <i class="fa-solid fa-plus"></i> Tambah Kelompok
                </button>
            </div>

            <div id="editGroupsContainer-{{ $quotation->quotation_number }}" class="space-y-4"
                data-existing-groups="{{ json_encode(
                    $quotation->groups->map(function ($g) {
                            return [
                                'name' => $g->name,
                                'items' => $g->items->map(function ($i) {
                                        return [
                                            'description' => $i->description,
                                            'volume' => $i->volume,
                                            'unit' => $i->unit,
                                            'unit_price' => $i->unit_price,
                                            'total_price' => $i->total_price,
                                        ];
                                    })->toArray(),
                            ];
                        })->toArray(),
                ) }}">
            </div>

            <div class="mt-4 flex justify-end">
                <div class="bg-warning-light border border-warning-light rounded px-5 py-3 text-right min-w-[220px]">
                    <p class="text-xs text-text-secondary mb-1">Grand Total</p>
                    <p class="text-lg font-bold text-text-heading"
                        id="editGrandTotal-{{ $quotation->quotation_number }}">
                        Rp {{ number_format($quotation->total_amount, 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Rekening Pembayaran --}}
        <div>
            <label class="block text-text-primary mb-2 text-sm font-medium">
                Rekening Pembayaran <span class="text-error">*</span>
            </label>
            @php $selectedIds = $quotation->selected_payment_accounts ?? []; @endphp
            <div id="payment-accounts-{{ $quotation->quotation_number }}"
                class="space-y-2"
                data-selected-ids="{{ json_encode($selectedIds) }}">
                @foreach ($paymentAccounts as $account)
                    <label
                        class="flex items-center gap-3 p-3 border border-border-strong rounded cursor-pointer hover:bg-surface-hover">
                        <input type="checkbox" name="selected_payment_accounts[]" value="{{ $account->id }}"
                            class="w-4 h-4 accent-primary payment-account-checkbox"
                            {{ $loop->first ? 'required' : '' }}
                            {{ in_array($account->id, $selectedIds) ? 'checked' : '' }}
                            oninvalid="this.setCustomValidity('Minimal 1 rekening pembayaran harus dipilih')"
                            oninput="this.setCustomValidity('')"
                            onchange="document.querySelectorAll('.payment-account-checkbox').forEach(cb => cb.required = !document.querySelector('.payment-account-checkbox:checked')); validatePaymentSelectionEdit('{{ $quotation->quotation_number }}')">
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
                class="w-full border border-border-strong rounded p-2 text-sm bg-surface-base text-text-input"
                value="{{ $quotation->signed_by }}" maxlength="255"
                oninvalid="this.setCustomValidity('Nama penandatangan maksimal 255 karakter')"
                oninput="this.setCustomValidity('')">
        </div>

        {{-- Divisi --}}
        <div>
            <label class="block text-text-primary mb-1 text-sm font-medium">Divisi</label>
            <input type="text" name="division"
                class="w-full border border-border-strong rounded p-2 text-sm bg-surface-base text-text-input"
                value="{{ $quotation->division }}" maxlength="255"
                oninvalid="this.setCustomValidity('Nama divisi maksimal 255 karakter')"
                oninput="this.setCustomValidity('')">
        </div>

    </div>{{-- end space-y-5 --}}

</x-modal>

{{-- Pre-populate existing groups via inline script --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const containerId = 'editGroupsContainer-{{ $quotation->quotation_number }}';
        const container = document.getElementById(containerId);
        if (!container) return;
        const existingGroups = JSON.parse(container.dataset.existingGroups || '[]');
        const prefix = 'edit-{{ $quotation->quotation_number }}';
        existingGroups.forEach(function(group) {
            addGroup(prefix, group);
        });
        updateGrandTotal(prefix);
    });
</script>
