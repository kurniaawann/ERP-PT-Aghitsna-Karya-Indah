{{-- Modal Tambah Penawaran Proyek --}}
<x-modal id="addModal" title="Tambah Penawaran Proyek" action="{{ route('aluminium-quotation.store') }}" method="POST"
    buttonText="Simpan" formId="addQuotationForm" onsubmit="return prepareAddSubmit()">

    {{-- Hidden JSON input --}}
    <input type="hidden" name="groups_json" id="addGroupsJson">

    {{-- Error Message Area --}}
    <div id="addModalError" class="hidden mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span id="addModalErrorText"></span>
        </div>
    </div>

    <div class="space-y-5">

        {{-- Nomor Penawaran (readonly, auto-generated) --}}
        <div class="bg-blue-50 rounded-lg p-3 flex items-center gap-3">
            <i class="fa-solid fa-hashtag text-blue-500"></i>
            <div>
                <p class="text-xs text-gray-500">Nomor Penawaran (auto)</p>
                <p class="text-sm font-semibold text-primary" id="addQuotationNumberDisplay">
                    Akan digenerate otomatis
                </p>
            </div>
        </div>

        {{-- Tanggal --}}
        <div>
            <label class="block text-text-primary mb-1 text-sm font-medium">Tanggal <span
                    class="text-error">*</span></label>
            <input type="date" name="date" class="w-full border rounded-lg p-2 text-sm" required
                value="{{ date('Y-m-d') }}" oninvalid="this.setCustomValidity('Tanggal penawaran harus diisi')"
                oninput="this.setCustomValidity('')">
        </div>

        {{-- Perihal --}}
        <div>
            <label class="block text-text-primary mb-1 text-sm font-medium">Perihal (Hal)</label>
            <input type="text" name="subject" class="w-full border rounded-lg p-2 text-sm" value="Penawaran Harga"
                maxlength="255" oninvalid="this.setCustomValidity('Perihal maksimal 255 karakter')"
                oninput="this.setCustomValidity('')">
        </div>

        {{-- Kepada --}}
        <div>
            <label class="block text-text-primary mb-1 text-sm font-medium">Kepada Yth <span
                    class="text-error">*</span></label>
            <input type="text" name="recipient" class="w-full border rounded-lg p-2 text-sm"
                placeholder="Nama penerima / perusahaan" required maxlength="255"
                oninvalid="this.setCustomValidity('Nama penerima harus diisi')" oninput="this.setCustomValidity('')">
        </div>

        {{-- Alamat --}}
        <div>
            <label class="block text-text-primary mb-1 text-sm font-medium">Alamat</label>
            <input type="text" name="recipient_address" class="w-full border rounded-lg p-2 text-sm" value="Ditempat"
                maxlength="255" oninvalid="this.setCustomValidity('Alamat maksimal 255 karakter')"
                oninput="this.setCustomValidity('')">
        </div>

        {{-- ═══ GROUPS SECTION ══════════════════════════════════════════════════════ --}}
        <div>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-text-primary uppercase tracking-wide">
                    <i class="fa-solid fa-layer-group text-primary mr-1"></i>
                    Kelompok Item
                </h3>
                <button type="button" onclick="addGroup('add')"
                    class="flex items-center gap-2 bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition-all duration-200">
                    <i class="fa-solid fa-plus"></i> Tambah Kelompok
                </button>
            </div>

            <div id="addGroupsContainer" class="space-y-4">
                {{-- Groups rendered by JS --}}
            </div>

            {{-- Grand Total --}}
            <div class="mt-4 flex justify-end">
                <div class="bg-yellow-50 border border-yellow-300 rounded-lg px-5 py-3 text-right min-w-[220px]">
                    <p class="text-xs text-gray-500 mb-1">Grand Total</p>
                    <p class="text-lg font-bold text-gray-800" id="addGrandTotal">Rp 0</p>
                </div>
            </div>
        </div>

        {{-- Rekening Pembayaran --}}
        <div>
            <label class="block text-text-primary mb-2 text-sm font-medium">
                Rekening Pembayaran <span class="text-error">*</span>
            </label>
            <div class="space-y-2">
                @foreach ($paymentAccounts as $account)
                    <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" name="selected_payment_accounts[]" value="{{ $account->id }}"
                            class="w-4 h-4 accent-primary payment-account-checkbox" {{ $loop->first ? 'required' : '' }}
                            oninvalid="this.setCustomValidity('Minimal 1 rekening pembayaran harus dipilih')"
                            oninput="this.setCustomValidity('')"
                            onchange="document.querySelectorAll('.payment-account-checkbox').forEach(cb => cb.required = !document.querySelector('.payment-account-checkbox:checked'))">
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
            <input type="text" name="signed_by" class="w-full border rounded-lg p-2 text-sm"
                placeholder="Nama penandatangan" maxlength="255"
                oninvalid="this.setCustomValidity('Nama penandatangan maksimal 255 karakter')"
                oninput="this.setCustomValidity('')">
        </div>

        {{-- Divisi --}}
        <div>
            <label class="block text-text-primary mb-1 text-sm font-medium">Divisi</label>
            <input type="text" name="division" class="w-full border rounded-lg p-2 text-sm"
                placeholder="Contoh: Divisi Alumunium" maxlength="255"
                oninvalid="this.setCustomValidity('Nama divisi maksimal 255 karakter')"
                oninput="this.setCustomValidity('')">
        </div>

    </div>{{-- end space-y-5 --}}
</x-modal>
