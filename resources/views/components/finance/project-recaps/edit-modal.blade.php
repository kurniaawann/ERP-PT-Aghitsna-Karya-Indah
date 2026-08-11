{{-- Modal Edit Rekap Proyek --}}
<x-modal id="editModal-{{ $recap->id }}" title="Edit Rekap Proyek"
    action="{{ route('recap-proyek.update', $recap->id) }}" method="PUT" buttonText="Update"
    enctype="multipart/form-data">

    {{-- Nama Proyek --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Proyek <span class="text-error">*</span></label>
        <input type="text" name="project_name" class="w-full border rounded p-2" value="{{ $recap->project_name }}"
            required maxlength="255" oninvalid="this.setCustomValidity('Nama proyek tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    {{-- Lokasi --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Lokasi</label>
        <input type="text" name="location" class="w-full border rounded p-2" value="{{ $recap->location }}"
            maxlength="255">
    </div>

    {{-- Total RAB --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Total RAB <span class="text-error">*</span></label>

        @if ($recap->rab_number)
            <input type="text" class="w-full border rounded p-2 bg-surface-secondary text-text-secondary"
                value="{{ number_format($recap->rab?->total_amount ?? $recap->total_rab ?? 0, 0, ',', '.') }}"
                readonly disabled>
            <input type="hidden" name="total_rab"
                value="{{ $recap->rab?->total_amount ?? $recap->total_rab ?? 0 }}">
            <p class="text-xs text-text-secondary mt-1">
                <i class="fa-solid fa-lock"></i> Total RAB mengikuti RAB sumber
                ({{ $recap->rab_number }}). Ubah di modul RAB.
            </p>
        @else
            <input type="text" inputmode="numeric" name="total_rab" class="w-full border rounded p-2 total-rab-input"
                value="{{ number_format($recap->total_rab ?? 0, 0, ',', '.') }}" required min="0"
                oninvalid="this.setCustomValidity('Total RAB tidak boleh kosong')"
                oninput="this.setCustomValidity('')">
        @endif
    </div>

    {{-- File Design Saat Ini --}}
    @if ($recap->hasDesignFile())
        <div class="mb-3 p-3 border rounded bg-blue-50">
            <label class="block text-text-primary mb-1">File Saat Ini</label>
            <a href="{{ asset('storage/' . $recap->design_file) }}" target="_blank"
                class="text-blue-600 hover:underline text-sm">{{ $recap->design_file_name }}</a>
        </div>
    @endif

    {{-- Ganti File Design (opsional) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Ganti File Design</label>
        <input type="file" name="design_file" accept="image/jpeg,image/png,image/gif,image/webp,image/bmp"
            class="w-full border rounded p-2">
        <p class="text-xs text-text-secondary mt-1">Format: JPG, PNG, GIF, WEBP, BMP. Maksimal 5 MB.
            Kosongkan jika tidak ingin mengganti file.</p>
    </div>
</x-modal>
