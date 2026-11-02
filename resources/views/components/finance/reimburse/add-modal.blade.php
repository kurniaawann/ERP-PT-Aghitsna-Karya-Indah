{{-- ═══════════════════════════════════════════════════════════════════════════
     KOMPONEN MODAL TAMBAH REIMBURSE (gaya Filament)
     Formulir untuk membuat pengajuan reimbursement baru (Super Admin only).

     "Ambil dari Nota" (opsional): pilih nota yang belum punya pengajuan →
     tanggal, nama proyek, keterangan belanja, total, dan catatan terisi
     otomatis (masih bisa diubah) dan pengajuan tertaut ke nota (id_nota).
     Data opsi: $notaOptions (ReimburseService::getNotaOptionsForReimburse),
     JS: initNotaPicker() di resources/js/pages/finance/reimburse/index.js
     (select#addModal-id_nota, #reimburse-nota-options, #reimburse-nota-picked,
     #reimburse-nota-clear).
     ═══════════════════════════════════════════════════════════════════════════ --}}
<x-fi.modal id="addModal" heading="Tambah Reimburse" icon="receipt-refund" width="xl"
    :action="route('reimburse.store')" enctype="multipart/form-data">

    {{-- Field: Ambil dari Nota (opsional) --}}
    <div class="grid gap-y-2 rounded-lg bg-gray-50 p-4 ring-1 ring-inset ring-gray-950/5">
        @if (!empty($notaOptions))
            <x-fi.field label="Ambil dari Nota (Opsional)" for="addModal-id_nota">
                <x-fi.select id="addModal-id_nota" name="id_nota" prefix-icon="document-text"
                    placeholder="-- Pilih nota (cari nomor nota / proyek) --" data-fi-select="search">
                    @foreach ($notaOptions as $notaOption)
                        <option value="{{ $notaOption['value'] }}">{{ $notaOption['label'] }}</option>
                    @endforeach
                </x-fi.select>
            </x-fi.field>
            <script type="application/json" id="reimburse-nota-options">@json($notaOptions)</script>
            <div id="reimburse-nota-picked" class="hidden flex-wrap items-center justify-between gap-2 text-sm text-emerald-700 [&:not(.hidden)]:flex">
                <span class="flex items-center gap-1.5">
                    <x-fi.icon name="check-circle" class="h-5 w-5 shrink-0" />
                    <span class="reimburse-nota-picked-text"></span>
                </span>
                <button type="button" id="reimburse-nota-clear" class="font-medium text-red-600 hover:underline">Batal ambil dari nota</button>
            </div>
            <p class="text-sm text-gray-500">Pilih nota → tanggal, nama proyek, keterangan belanja, total, dan catatan
                terisi otomatis (masih bisa diubah). Nota dari "Tambah Nota" tidak otomatis masuk reimbursement — pilih di
                sini. Hanya nota yang belum punya pengajuan reimbursement.</p>
        @else
            <p class="flex items-center gap-1.5 text-sm font-medium text-gray-950">
                <x-fi.icon name="document-text" class="h-5 w-5 text-primary" /> Ambil dari Nota
            </p>
            <p class="text-sm text-gray-500">Belum ada nota yang bisa diambil — semua nota sudah memiliki pengajuan
                reimbursement. Isi form di bawah secara manual.</p>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2">
        {{-- Field: Tanggal --}}
        <x-fi.field label="Tanggal" for="add-reimburse-date" required>
            <x-fi.input type="date" id="add-reimburse-date" name="date" required :value="date('Y-m-d')"
                oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')" />
        </x-fi.field>

        {{-- Field: Nama Proyek --}}
        <x-fi.field label="Nama Proyek" for="add-reimburse-project" required>
            <x-fi.input id="add-reimburse-project" name="project_name" placeholder="Masukkan nama proyek" required maxlength="255"
                oninvalid="this.setCustomValidity('Nama proyek tidak boleh kosong')" oninput="this.setCustomValidity('')" />
        </x-fi.field>
    </div>

    {{-- Field: Keterangan Belanja --}}
    <x-fi.field label="Keterangan Belanja" for="add-reimburse-description" required
        hint="Jelaskan detail pengeluaran yang akan direimbursement">
        <x-fi.textarea id="add-reimburse-description" name="expense_description" rows="3" required
            placeholder="Contoh: Belanja Alumunium untuk Proyek A, Belanja Handle Paket Solid untuk Proyek B"
            oninvalid="this.setCustomValidity('Keterangan belanja tidak boleh kosong')"
            oninput="this.setCustomValidity('')"></x-fi.textarea>
    </x-fi.field>

    {{-- Field: Total Amount --}}
    <x-fi.field label="Total Amount" for="add-reimburse-amount" required
        hint="Total keseluruhan biaya yang akan direimbursement">
        <x-fi.input id="add-reimburse-amount" inputmode="numeric" name="total_amount" value="0" prefix="Rp"
            class="reimburse-amount-input" placeholder="Masukkan total amount" required min="0"
            oninvalid="this.setCustomValidity('Total amount tidak boleh kosong')" oninput="this.setCustomValidity('')" />
    </x-fi.field>

    {{-- Field: Catatan (Opsional) --}}
    <x-fi.field label="Catatan" for="add-reimburse-notes">
        <x-fi.textarea id="add-reimburse-notes" name="notes" rows="2" placeholder="Catatan tambahan (opsional)"></x-fi.textarea>
    </x-fi.field>

    {{-- Field: Bukti (Opsional) --}}
    <x-fi.field label="Lampiran Bukti (Foto/File)" for="add-reimburse-proof"
        hint="Lampiran bukti (struk, kwitansi, foto, dsb.) — opsional">
        <input type="file" id="add-reimburse-proof" name="proof_file" accept=".jpg,.jpeg,.png,.webp,.gif,.bmp,.pdf,image/*"
            class="block w-full rounded-lg bg-white text-sm text-gray-500 shadow-sm ring-1 ring-gray-950/10 file:me-3 file:cursor-pointer file:rounded-s-lg file:border-0 file:border-e file:border-gray-200 file:bg-gray-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary">
    </x-fi.field>
</x-fi.modal>
