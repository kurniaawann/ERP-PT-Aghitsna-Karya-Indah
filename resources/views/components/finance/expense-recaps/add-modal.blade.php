{{-- Modal Tambah Rekap Pengeluaran (admin: "Kas Kantor"), gaya Filament.
     Hook JS (expense-recaps/index.js): #addModal form,
     select[name="transaction_category_id"] (option data-type INCOME/EXPENSE),
     .amount-label (teks label diganti sesuai jenis kategori), .invoice-section
     (hanya tampil untuk uang masuk), .expense-amount-input (format ribuan). --}}
@php
    $recapLabel = $recapLabel ?? 'Rekap Pengeluaran';
    $isAdmin = auth()->user()?->isAdmin();
    // Nomor faktur hanya dipakai super admin (admin/Kas Kantor tanpa kolom faktur)
    $usesInvoiceNumber = ! $isAdmin;
@endphp

<x-fi.modal id="addModal" heading="Tambah {{ $recapLabel }}" icon="banknotes" width="xl"
    :action="route('recap-expense.store')" method="POST" submitLabel="Simpan">

    <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2">
        {{-- Field: Kategori --}}
        <x-fi.field label="Kategori" for="add-category" required>
            <x-fi.select id="add-category" name="transaction_category_id" prefix-icon="tag" placeholder="-- Pilih Kategori --"
                required oninvalid="this.setCustomValidity('Kategori tidak boleh kosong')"
                oninput="this.setCustomValidity('')" data-fi-select>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" data-type="{{ $cat->type }}">{{ $cat->name }}</option>
                @endforeach
            </x-fi.select>
        </x-fi.field>

        {{-- Field: Tanggal --}}
        <x-fi.field label="Tanggal" for="add-transaction-date" required>
            <x-fi.input type="date" id="add-transaction-date" name="transaction_date" required
                oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')" />
        </x-fi.field>
    </div>

    {{-- Field: Keterangan --}}
    <x-fi.field label="Keterangan" for="add-description" required>
        <x-fi.textarea id="add-description" name="description" rows="3" placeholder="Contoh: Belanja ATK, Sampah Cemara, dll"
            required maxlength="1000" oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')"
            oninput="this.setCustomValidity('')"></x-fi.textarea>
    </x-fi.field>

    {{-- Field: Jumlah (label "Jumlah Pemasukan"/"Jumlah Pengeluaran" diganti JS sesuai kategori) --}}
    <div class="grid content-start gap-y-2">
        <label for="add-expense-amount" class="amount-label text-sm font-medium leading-6 text-gray-950">
            Jumlah Pengeluaran <sup class="ms-0.5 font-medium text-red-600">*</sup>
        </label>
        <x-fi.input id="add-expense-amount" inputmode="numeric" name="expense_amount" class="expense-amount-input"
            prefix="Rp" placeholder="Contoh: 50000" required min="0"
            oninvalid="this.setCustomValidity('Jumlah tidak boleh kosong')" oninput="this.setCustomValidity('')" />
    </div>

    {{-- No. Faktur: hanya untuk kategori uang masuk (di-generate otomatis, read-only).
         Uang keluar tidak memiliki nomor faktur — bagian ini disembunyikan oleh JS. --}}
    @if ($usesInvoiceNumber)
        <x-fi.field label="No. Faktur" class="invoice-section hidden"
            hint="Khusus uang masuk — nomor faktur di-generate otomatis saat disimpan.">
            <x-fi.input :value="$nextIncomeInvoiceNumber ?? ''" placeholder="Di-generate otomatis saat disimpan"
                prefix-icon="lock-closed" aria-label="No. Faktur" readonly tabindex="-1" />
        </x-fi.field>
    @endif

    {{-- Field: Sumber Uang (role admin: label "Keterangan" — hanya teks UI, kolom DB tetap money_source) --}}
    <x-fi.field :label="($isAdmin ? 'Keterangan' : 'Sumber Uang') . ' (Opsional)'" for="add-money-source">
        <x-fi.input id="add-money-source" name="money_source" placeholder="Contoh: Kas Perusahaan" maxlength="255" />
    </x-fi.field>

    {{-- Field: Catatan --}}
    <x-fi.field label="Catatan (Opsional)" for="add-notes">
        <x-fi.textarea id="add-notes" name="notes" rows="2" placeholder="Catatan tambahan..."></x-fi.textarea>
    </x-fi.field>
</x-fi.modal>
