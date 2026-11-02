{{-- ═══════════════════════════════════════════════════════════════════════════
     KOMPONEN MODAL EDIT REIMBURSE (gaya Filament)
     Formulir untuk memperbarui data reimbursement (Super Admin only, draft only).
     Modal ini dirender per baris untuk setiap reimburse dengan status draft.
     Pengajuan otomatis dari Nota menampilkan info nota sumber (badge
     "Dari Nota" + link pratinjau PDF nota).
     JS: form di [id^="editModal-"] ditangani initEditFormSubmit()
     (resources/js/pages/finance/reimburse/index.js).
     ═══════════════════════════════════════════════════════════════════════════ --}}
@php($editId = 'editModal-' . $reimburse->reimburse_code)
<x-fi.modal :id="$editId" heading="Edit Reimburse" :description="$reimburse->reimburse_code" icon="pencil-square"
    width="xl" :action="route('reimburse.update', $reimburse->reimburse_code)" method="PUT"
    enctype="multipart/form-data" submitLabel="Update">

    {{-- Info Nota Sumber (pengajuan otomatis dari Nota Super Admin) --}}
    @if ($reimburse->is_from_nota)
        <div class="grid gap-y-1.5 rounded-lg bg-blue-50 p-4 ring-1 ring-inset ring-blue-600/10">
            <div class="flex flex-wrap items-center gap-2">
                <x-fi.badge color="primary">Dari Nota</x-fi.badge>
                @include('components.finance.reimburse.nota-link', ['reimburse' => $reimburse])
            </div>
            <p class="text-sm text-gray-600">
                Pengajuan ini dibuat otomatis dari nota. Selama masih Draft, perubahan nota akan
                memperbarui tanggal, nama proyek, keterangan belanja, dan total di sini.
            </p>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2">
        {{-- Field: Tanggal --}}
        <x-fi.field label="Tanggal" :for="$editId . '-date'" required>
            <x-fi.input type="date" :id="$editId . '-date'" name="date" required
                :value="$reimburse->date->format('Y-m-d')"
                oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')" />
        </x-fi.field>

        {{-- Field: Nama Proyek --}}
        <x-fi.field label="Nama Proyek" :for="$editId . '-project'" required>
            <x-fi.input :id="$editId . '-project'" name="project_name" placeholder="Masukkan nama proyek" required
                maxlength="255" :value="$reimburse->project_name"
                oninvalid="this.setCustomValidity('Nama proyek tidak boleh kosong')" oninput="this.setCustomValidity('')" />
        </x-fi.field>
    </div>

    {{-- Field: Keterangan Belanja --}}
    <x-fi.field label="Keterangan Belanja" :for="$editId . '-description'" required
        hint="Jelaskan detail pengeluaran yang akan direimbursement">
        <x-fi.textarea :id="$editId . '-description'" name="expense_description" rows="3" required
            placeholder="Contoh: Belanja Alumunium untuk Proyek A, Belanja Handle Paket Solid untuk Proyek B"
            oninvalid="this.setCustomValidity('Keterangan belanja tidak boleh kosong')"
            oninput="this.setCustomValidity('')">{{ $reimburse->expense_description }}</x-fi.textarea>
    </x-fi.field>

    {{-- Field: Total Amount --}}
    <x-fi.field label="Total Amount" :for="$editId . '-amount'" required
        hint="Total keseluruhan biaya yang akan direimbursement">
        <x-fi.input :id="$editId . '-amount'" inputmode="numeric" name="total_amount" prefix="Rp"
            class="reimburse-amount-input" placeholder="Masukkan total amount" required min="0"
            :value="number_format($reimburse->total_amount, 0, ',', '.')"
            oninvalid="this.setCustomValidity('Total amount tidak boleh kosong')" oninput="this.setCustomValidity('')" />
    </x-fi.field>

    {{-- Field: Catatan (Opsional) --}}
    <x-fi.field label="Catatan" :for="$editId . '-notes'">
        <x-fi.textarea :id="$editId . '-notes'" name="notes" rows="2"
            placeholder="Catatan tambahan (opsional)">{{ $reimburse->notes }}</x-fi.textarea>
    </x-fi.field>

    {{-- Field: Bukti (Opsional) --}}
    <x-fi.field label="Lampiran Bukti (Foto/File)" :for="$editId . '-proof'"
        hint="Biarkan kosong jika tidak ingin mengubah lampiran">
        @if ($reimburse->proof_file)
            {{-- Pratinjau lampiran di dalam halaman (tanpa tab baru) --}}
            <a href="{{ $reimburse->proof_url }}"
                onclick="event.preventDefault(); window.openFilePreview(this.href, { title: @js('Bukti Reimburse ' . $reimburse->reimburse_code), downloadName: @js($reimburse->proof_file_name ?: basename($reimburse->proof_file)) })"
                class="mb-1 inline-flex max-w-full items-center gap-1.5 text-sm font-medium text-primary hover:underline">
                <x-fi.icon name="paper-clip" class="h-4 w-4 shrink-0 text-gray-400" />
                <span class="truncate">{{ $reimburse->proof_file_name ?: 'Lihat Lampiran' }}</span>
            </a>
        @endif
        <input type="file" id="{{ $editId }}-proof" name="proof_file" accept=".jpg,.jpeg,.png,.webp,.gif,.bmp,.pdf,image/*"
            class="block w-full rounded-lg bg-white text-sm text-gray-500 shadow-sm ring-1 ring-gray-950/10 file:me-3 file:cursor-pointer file:rounded-s-lg file:border-0 file:border-e file:border-gray-200 file:bg-gray-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary">
    </x-fi.field>
</x-fi.modal>
