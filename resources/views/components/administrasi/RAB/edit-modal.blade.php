{{--
    Component Modal Edit RAB
    Form untuk memperbarui RAB yang sudah ada.
    Data existing di-load via JavaScript dari data attributes.

    Data:
    - $rab: Instance RAB yang akan diedit
    - $paymentAccounts: Collection dari PaymentAccount aktif
--}}
<x-modal :id="'editRABModal' . $rab->rab_number" :title="'Edit RAB ' . $rab->rab_number" :action="route('rab.update', $rab->rab_number)" method="POST" buttonText="Update" :formId="'editRABForm' . $rab->rab_number"
    onsubmit="return prepareEditRABSubmit('{{ $rab->rab_number }}')">

    @method('PUT')

    {{-- Tanggal --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            name="date" required value="{{ $rab->date->format('Y-m-d') }}"
            oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    {{-- Penerima --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Penerima <span class="text-error">*</span></label>
        <input type="text" class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            name="recipient" placeholder="Nama penerima RAB" value="{{ $rab->recipient }}" required maxlength="255"
            oninvalid="this.setCustomValidity('Nama penerima tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    {{-- Alamat Penerima --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Alamat Penerima</label>
        <textarea class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            name="recipient_address" rows="2" placeholder="Masukkan alamat lengkap penerima" maxlength="500">{{ $rab->recipient_address }}</textarea>
        <small class="text-text-secondary text-xs">Maksimal 500 karakter</small>
    </div>

    {{-- Teks Pengantar --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Teks Pengantar <span class="text-error">*</span></label>
        <textarea class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input" name="intro_text"
            rows="3"
            placeholder="Contoh: Bersama ini kami sampaikan perihal penawaran harga pekerjaan renovasi rumah tinggal 1 lantai, sebagai berikut:"
            required maxlength="1000" oninvalid="this.setCustomValidity('Teks pengantar tidak boleh kosong')"
            oninput="this.setCustomValidity('')">{{ $rab->intro_text }}</textarea>
        <small class="text-text-secondary text-xs">Maksimal 1000 karakter</small>
    </div>

    {{-- Ditandatangani Oleh --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Ditandatangani Oleh</label>
        <input type="text" class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            name="signed_by" placeholder="Nama pejabat" value="{{ $rab->signed_by ?? '' }}" maxlength="255">
    </div>

    {{-- Divisi --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Divisi/Bagian</label>
        <input type="text" class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            name="division" placeholder="Nama divisi" value="{{ $rab->division ?? '' }}" maxlength="255">
    </div>

    {{-- Rekening Pembayaran --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Rekening Pembayaran <span class="text-error">*</span></label>
        <div id="paymentAccountsList" class="space-y-2" required>
            <small class="text-text-secondary text-xs">Pilih minimal 1 rekening pembayaran</small>
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

    <hr class="my-4">

    {{-- Detail Pekerjaan --}}
    <div class="mb-3">
        <h6 class="text-text-primary font-semibold mb-3">Detail Pekerjaan (Struktur Hierarki)</h6>
        <div class="text-xs text-text-secondary mb-4 p-2 bg-surface-secondary rounded">
            <p class="mb-1"><strong>Struktur:</strong> Kategori (Romawi) → Sub-Kategori (Angka) → Item (Huruf)</p>
            <p><strong>Catatan:</strong> Volume, satuan, harga satuan, dan sub-harga diisi pada item huruf a, b, c...</p>
        </div>
    </div>

    {{-- Container Kategori Edit --}}
    <div id="editRabCategoriesContainer{{ $rab->rab_number }}" class="space-y-4 mb-3"
        data-existing-categories="{{ json_encode($rab->getRawRABData()) }}">
    </div>

    <button type="button" onclick="addCategoryBlock('editRabCategoriesContainer{{ $rab->rab_number }}')"
        class="btn btn-outline-primary w-full">
        <i class="fa-solid fa-plus"></i> Tambah Kategori
    </button>

    <hr class="my-4">

    {{-- Biaya Lain-Lain --}}
    <div class="mb-3">
        <h6 class="text-text-primary font-semibold mb-3">III. Biaya Lain-Lain (Optional)</h6>
        <div class="text-xs text-text-secondary mb-3 p-2 bg-surface-secondary rounded">
            <p><strong>Contoh:</strong> Iuran RT, Perizinan Air, Wifi/CCTV/AC, Pekerjaan Listrik, dll</p>
        </div>
    </div>

    <input type="hidden" id="editMiscCostsDataInput{{ $rab->rab_number }}" name="misc_costs_data" value="[]"
        data-existing-misc-costs="{{ json_encode($rab->miscellaneousCosts->map(function ($m) {return ['item_order' => $m->item_order, 'item_name' => $m->item_name, 'amount' => $m->amount];})->values()->toArray()) }}">

    <div id="editMiscCostsContainer{{ $rab->rab_number }}" class="space-y-2 mb-3"></div>

    <button type="button" class="btn btn-sm btn-outline-secondary w-full mb-4"
        onclick="addMiscCostItem('editMiscCostsContainer{{ $rab->rab_number }}')">
        <i class="fa-solid fa-plus"></i> Tambah Biaya Lain-Lain
    </button>

    <hr class="my-4">

    {{-- Total Keseluruhan --}}
    <div class="flex justify-end mb-3">
        <div class="bg-success-light border-2 border-success rounded p-4 w-full">
            <div class="space-y-2">
                <div class="flex justify-between text-sm text-success border-b border-success pb-2">
                    <span><strong>Total Kategori:</strong></span>
                    <span id="editTotalCategoriesPrice{{ $rab->rab_number }}" class="font-semibold">Rp 0</span>
                </div>
                <div class="flex justify-between text-sm text-success border-b border-success pb-2">
                    <span><strong>Total Biaya Lain-Lain:</strong></span>
                    <span id="editTotalMiscCostsPrice{{ $rab->rab_number }}" class="font-semibold">Rp 0</span>
                </div>
                <div class="flex justify-between text-lg text-success">
                    <span><strong>Total Keseluruhan:</strong></span>
                    <p class="font-bold text-2xl text-success"><span
                            id="editGrandTotalPrice{{ $rab->rab_number }}">Rp 0</span></p>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" id="editRabDataInput{{ $rab->rab_number }}" name="rab_data" required>

    {{-- Inisialisasi data existing via JavaScript --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const containerId = 'editRabCategoriesContainer{{ $rab->rab_number }}';
            const container = document.getElementById(containerId);
            if (!container) return;

            const existingCategories = JSON.parse(container.dataset.existingCategories || '[]');
            const prefix = 'edit-{{ $rab->rab_number }}';

            existingCategories.forEach(function(categoryData) {
                addCategoryBlock(prefix, categoryData);
            });

            const miscInputId = 'editMiscCostsDataInput{{ $rab->rab_number }}';
            const miscInput = document.getElementById(miscInputId);
            if (miscInput && miscInput.dataset.existingMiscCosts) {
                const existingMiscCosts = JSON.parse(miscInput.dataset.existingMiscCosts || '[]');
                const miscContainerId = 'editMiscCostsContainer{{ $rab->rab_number }}';

                existingMiscCosts.forEach(function(miscData) {
                    const miscContainer = document.getElementById(miscContainerId);
                    const item = document.createElement('div');
                    item.className = 'misc-cost-item bg-surface-base border border-border-strong rounded p-3 flex gap-2';
                    item.innerHTML = `
                        <div class="flex-1">
                            <input type="text" class="w-full border border-border-strong rounded p-2 mb-2 bg-surface-base text-text-input misc-item-name" 
                                placeholder="Nama biaya" value="${miscData.item_name}" required maxlength="255"
                                oninput="updateMiscCostsData('${miscContainerId}')">
                        </div>
                        <div class="w-32">
                            <input type="number" class="w-full border border-border-strong rounded p-2 mb-2 bg-surface-base text-text-input misc-item-amount" 
                                placeholder="Jumlah" value="${miscData.amount}" min="0" step="0.01" required
                                oninput="updateMiscCostsData('${miscContainerId}')">
                        </div>
                        <button type="button" class="btn btn-sm btn-danger h-full" onclick="removeMiscCostItem(this, '${miscContainerId}')">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    `;
                    miscContainer.appendChild(item);
                });
            }

            attachPriceListeners();
            updatePricesForEditModal('editGrandTotalPrice{{ $rab->rab_number }}');
        });
    </script>

</x-modal>
