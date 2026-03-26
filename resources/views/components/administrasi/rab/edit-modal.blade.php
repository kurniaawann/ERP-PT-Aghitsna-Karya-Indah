<x-modal :id="'editRABModal' . $rab->rab_number" :title="'Edit RAB ' . $rab->rab_number" :action="route('rab.update', $rab->rab_number)" method="POST" buttonText="Update" :formId="'editRABForm' . $rab->rab_number"
    onsubmit="return prepareEditRABSubmit('{{ $rab->rab_number }}')"></x-modal>

@method('PUT')

{{-- Tanggal --}}
<div class="mb-3">
    <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
    <input type="date" class="w-full border rounded p-2" name="date" required
        value="{{ $rab->date->format('Y-m-d') }}">
</div>

{{-- Penerima --}}
<div class="mb-3">
    <label class="block text-text-primary mb-1">Penerima <span class="text-error">*</span></label>
    <input type="text" class="w-full border rounded p-2" name="recipient" placeholder="Nama penerima RAB"
        value="{{ $rab->recipient }}" required>
</div>

{{-- Alamat Penerima --}}
<div class="mb-3">
    <label class="block text-text-primary mb-1">Alamat Penerima</label>
    <textarea class="w-full border rounded p-2" name="recipient_address" rows="2"
        placeholder="Masukkan alamat lengkap penerima">{{ $rab->recipient_address }}</textarea>
</div>

{{-- Teks Pengantar --}}
<div class="mb-3">
    <label class="block text-text-primary mb-1">Teks Pengantar <span class="text-error">*</span></label>
    <textarea class="w-full border rounded p-2" name="intro_text" rows="3"
        placeholder="Contoh: Bersama ini kami sampaikan perihal penawaran harga pekerjaan renovasi rumah tinggal 1 lantai, sebagai berikut:"
        required>{{ $rab->intro_text }}</textarea>
</div>

{{-- Ditandatangani Oleh --}}
<div class="mb-3">
    <label class="block text-text-primary mb-1">Ditandatangani Oleh</label>
    <input type="text" class="w-full border rounded p-2" name="signed_by" placeholder="Nama pejabat"
        value="{{ $rab->signed_by ?? '' }}">
</div>

{{-- Divisi --}}
<div class="mb-3">
    <label class="block text-text-primary mb-1">Divisi/Bagian</label>
    <input type="text" class="w-full border rounded p-2" name="division" placeholder="Nama divisi"
        value="{{ $rab->division ?? '' }}">
</div>

{{-- Rekening Pembayaran --}}
<div class="mb-3">
    <label class="block text-text-primary mb-1">Rekening Pembayaran <span class="text-error">*</span></label>
    <div id="paymentAccountsList" class="space-y-2">
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
    <div class="text-xs text-gray-600 mb-4 p-2 bg-gray-50 rounded">
        <p class="mb-1"><strong>Struktur:</strong> Kategori (Romawi) → Sub-Kategori (Angka) → Item (Huruf)</p>
        <p><strong>Contoh:</strong> I. Pekerjaan Persiapan → 1. Pembongkaran → a. Pembongkaran atap</p>
    </div>
</div>

<div id="editRabCategoriesContainer{{ $rab->rab_number }}" class="space-y-4 mb-3"
    data-existing-categories="{{ json_encode($rab->getRawRABData()) }}">
    {{-- Categories will be populated via JavaScript --}}
</div>

<button type="button" onclick="addCategoryBlock('editRabCategoriesContainer{{ $rab->rab_number }}')"
    class="btn btn-outline-primary w-full">
    <i class="fa-solid fa-plus"></i> Tambah Kategori
</button>

{{-- Total Keseluruhan --}}
<div class="flex justify-end mb-3">
    <div class="bg-green-50 border-2 border-green-300 rounded p-4 w-full md:w-80">
        <p class="text-sm text-green-900"><strong>Total Keseluruhan:</strong></p>
        <p class="font-bold text-2xl text-green-600"><span id="editGrandTotalPrice{{ $rab->rab_number }}">Rp
                0</span></p>
    </div>
</div>

<input type="hidden" id="editRabDataInput{{ $rab->rab_number }}" name="rab_data" required>

{{-- Initialize edit modal --}}
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

        attachPriceListeners();
        updatePricesForEditModal('editGrandTotalPrice{{ $rab->rab_number }}');
    });
</script>

</x-modal>
