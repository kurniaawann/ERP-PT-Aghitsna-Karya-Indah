{{-- Modal Edit Rekap Pengeluaran (admin: "Kas Kantor"), gaya Filament — hanya
     untuk data manual. Hook JS sama dengan modal tambah ([id^="editModal-"] form). --}}
@php
    $safeId = str_replace('/', '-', $expense->id);
    $recapLabel = $recapLabel ?? 'Rekap Pengeluaran';
    $isAdmin = auth()->user()?->isAdmin();
    // Nomor faktur hanya dipakai super admin (admin/Kas Kantor tanpa kolom faktur)
    $usesInvoiceNumber = ! $isAdmin;
    // Faktur yang ada hanya relevan untuk record uang masuk
    $currentInvoiceNumber = (int) $expense->income_amount > 0 ? $expense->invoice_number : null;
@endphp

<x-fi.modal id="editModal-{{ $safeId }}" heading="Edit {{ $recapLabel }}" :description="(string) $expense->id"
    icon="pencil-square" width="xl" :action="route('recap-expense.update', $expense->id)" method="PUT" submitLabel="Update">

    <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2">
        {{-- Field: Kategori --}}
        <x-fi.field label="Kategori" for="edit-category-{{ $safeId }}" required>
            <x-fi.select id="edit-category-{{ $safeId }}" name="transaction_category_id" prefix-icon="tag"
                placeholder="-- Pilih Kategori --" required oninvalid="this.setCustomValidity('Kategori tidak boleh kosong')"
                oninput="this.setCustomValidity('')" data-fi-select>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" data-type="{{ $cat->type }}" @selected($expense->transaction_category_id == $cat->id)>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </x-fi.select>
        </x-fi.field>

        {{-- Field: Tanggal --}}
        <x-fi.field label="Tanggal" for="edit-transaction-date-{{ $safeId }}" required>
            <x-fi.input type="date" id="edit-transaction-date-{{ $safeId }}" name="transaction_date" required
                :value="$expense->transaction_date ? \Carbon\Carbon::parse($expense->transaction_date)->format('Y-m-d') : ''"
                oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')" />
        </x-fi.field>
    </div>

    {{-- Field: Keterangan --}}
    <x-fi.field label="Keterangan" for="edit-description-{{ $safeId }}" required>
        <x-fi.textarea id="edit-description-{{ $safeId }}" name="description" rows="3" required maxlength="1000"
            oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')"
            oninput="this.setCustomValidity('')">{{ $expense->description }}</x-fi.textarea>
    </x-fi.field>

    {{-- Field: Jumlah (label "Jumlah Pemasukan"/"Jumlah Pengeluaran" diganti JS sesuai kategori) --}}
    <div class="grid content-start gap-y-2">
        <label for="edit-expense-amount-{{ $safeId }}" class="amount-label text-sm font-medium leading-6 text-gray-950">
            Jumlah Pengeluaran <sup class="ms-0.5 font-medium text-red-600">*</sup>
        </label>
        <x-fi.input id="edit-expense-amount-{{ $safeId }}" inputmode="numeric" name="expense_amount"
            class="expense-amount-input" prefix="Rp" :value="$expense->income_amount ?: $expense->expense_amount"
            required min="0" oninvalid="this.setCustomValidity('Jumlah tidak boleh kosong')"
            oninput="this.setCustomValidity('')" />
    </div>

    {{-- No. Faktur: hanya untuk kategori uang masuk (read-only). Uang keluar tidak
         memiliki nomor faktur — bagian ini disembunyikan oleh JS. --}}
    @if ($usesInvoiceNumber)
        <x-fi.field label="No. Faktur" class="invoice-section hidden"
            hint="Khusus uang masuk — nomor faktur di-generate otomatis dan tidak dapat diubah.">
            <x-fi.input :value="$currentInvoiceNumber" placeholder="Di-generate otomatis saat disimpan"
                prefix-icon="lock-closed" aria-label="No. Faktur" readonly tabindex="-1" />
        </x-fi.field>
    @endif

    {{-- Field: Sumber Uang (role admin: label "Keterangan" — hanya teks UI, kolom DB tetap money_source) --}}
    <x-fi.field :label="($isAdmin ? 'Keterangan' : 'Sumber Uang') . ' (Opsional)'" for="edit-money-source-{{ $safeId }}">
        <x-fi.input id="edit-money-source-{{ $safeId }}" name="money_source" :value="$expense->money_source" maxlength="255" />
    </x-fi.field>

    {{-- Field: Catatan --}}
    <x-fi.field label="Catatan (Opsional)" for="edit-notes-{{ $safeId }}">
        <x-fi.textarea id="edit-notes-{{ $safeId }}" name="notes" rows="2">{{ $expense->notes }}</x-fi.textarea>
    </x-fi.field>
</x-fi.modal>
