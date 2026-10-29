{{-- Modal Edit Rekap Proyek (gaya Filament, satu per rekap).
     Hook JS (project-recaps/index.js): [id^="editModal-"] form, .total-rab-input.
     Bukti pembayaran dikelola lewat x-finance.payment-proofs.manager (upload/hapus
     lewat modal terpisah di luar form). --}}
@php
    $fileInputClass = 'block w-full rounded-lg bg-white text-sm text-gray-500 shadow-sm ring-1 ring-gray-950/10 file:me-3 file:cursor-pointer file:rounded-s-lg file:border-0 file:border-e file:border-gray-200 file:bg-gray-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary';
@endphp

<x-fi.modal id="editModal-{{ $recap->id }}" heading="Edit Rekap Proyek" :description="$recap->id . ' · ' . $recap->project_name"
    icon="pencil-square" width="2xl" :action="route('recap-proyek.update', $recap->id)" method="PUT"
    enctype="multipart/form-data" submitLabel="Update">

    {{-- Field: Nama Proyek --}}
    <x-fi.field label="Nama Proyek" for="edit-project-name-{{ $recap->id }}" required>
        <x-fi.input id="edit-project-name-{{ $recap->id }}" name="project_name" :value="$recap->project_name"
            required maxlength="255" oninvalid="this.setCustomValidity('Nama proyek tidak boleh kosong')"
            oninput="this.setCustomValidity('')" />
    </x-fi.field>

    {{-- Field: Lokasi --}}
    <x-fi.field label="Lokasi" for="edit-location-{{ $recap->id }}">
        <x-fi.input id="edit-location-{{ $recap->id }}" name="location" :value="$recap->location" maxlength="255" />
    </x-fi.field>

    {{-- Field: Total RAB (terkunci bila rekap dibuat dari RAB) --}}
    @if ($recap->rab_number)
        <x-fi.field label="Total RAB" required>
            <x-fi.input :value="number_format($recap->rab?->total_amount ?? $recap->total_rab ?? 0, 0, ',', '.')"
                prefix="Rp" aria-label="Total RAB" readonly disabled />
            <input type="hidden" name="total_rab" value="{{ $recap->rab?->total_amount ?? $recap->total_rab ?? 0 }}">
            <p class="flex items-center gap-x-1.5 text-sm text-gray-500">
                <x-fi.icon name="lock-closed" class="h-4 w-4 shrink-0 text-gray-400" />
                Total RAB mengikuti RAB sumber ({{ $recap->rab_number }}). Ubah di modul RAB.
            </p>
        </x-fi.field>
    @else
        <x-fi.field label="Total RAB" for="edit-total-rab-{{ $recap->id }}" required>
            <x-fi.input id="edit-total-rab-{{ $recap->id }}" inputmode="numeric" name="total_rab" class="total-rab-input"
                prefix="Rp" :value="number_format($recap->total_rab ?? 0, 0, ',', '.')" required min="0"
                oninvalid="this.setCustomValidity('Total RAB tidak boleh kosong')" oninput="this.setCustomValidity('')" />
        </x-fi.field>
    @endif

    {{-- File Design Saat Ini (dibuka di modal pratinjau, tanpa tab baru) --}}
    @if ($recap->hasDesignFile())
        <x-fi.field label="File Saat Ini">
            <a href="{{ asset('storage/' . $recap->design_file) }}"
                onclick="if (window.openFilePreview) { event.preventDefault(); window.openFilePreview(this.href, { title: 'File Design', downloadName: @js($recap->design_file_name ?: basename($recap->design_file)) }); }"
                class="flex items-center gap-x-3 rounded-lg p-3 text-sm ring-1 ring-gray-950/10 transition duration-75 hover:bg-gray-50">
                <x-fi.icon name="photo" class="h-5 w-5 shrink-0 text-primary" />
                <span class="min-w-0 truncate font-medium text-primary">{{ $recap->design_file_name }}</span>
            </a>
        </x-fi.field>
    @endif

    {{-- Field: Ganti File Design (opsional) --}}
    <x-fi.field label="Ganti File Design" for="edit-design-file-{{ $recap->id }}"
        hint="Format: JPG, PNG, GIF, WEBP, BMP. Maksimal 5 MB. Kosongkan jika tidak ingin mengganti file.">
        <input type="file" id="edit-design-file-{{ $recap->id }}" name="design_file"
            accept="image/jpeg,image/png,image/gif,image/webp,image/bmp" class="{{ $fileInputClass }}">
    </x-fi.field>

    {{-- Bukti Pembayaran --}}
    <x-finance.payment-proofs.manager :proofs="$recap->paymentProofs" invoiceType="recap"
        :invoiceKey="$recap->id" :manualAmount="true" />
</x-fi.modal>
