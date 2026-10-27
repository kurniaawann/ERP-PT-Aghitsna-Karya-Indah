{{--
    Catatan "kolom kuning" PDF kwitansi (di bawah Keterangan, sebelah kiri
    kotak nominal). Revisi klien: catatan ini opsional & bisa ditulis sendiri.

    Parameter:
    - $idPrefix : prefix id field (mis. "addModal", "editModal-01/AKI/26")
    - $mode     : auto | custom | none (default auto)
    - $note     : teks catatan untuk pilihan "Tulis sendiri"
--}}
@php
    $mode = $mode ?? \App\Models\Administrasi\Kwintansi::HIGHLIGHT_AUTO;
    $note = $note ?? '';
    $options = [
        \App\Models\Administrasi\Kwintansi::HIGHLIGHT_AUTO => 'Otomatis — "*Total Uang Masuk Per (tanggal) = Rp ..." (khusus kwitansi dari invoice)',
        \App\Models\Administrasi\Kwintansi::HIGHLIGHT_CUSTOM => 'Tulis sendiri',
        \App\Models\Administrasi\Kwintansi::HIGHLIGHT_NONE => 'Tidak ditampilkan',
    ];
@endphp

<div class="mb-3 p-3 border rounded bg-yellow-50" data-kwitansi-highlight>
    <label class="block text-text-primary font-semibold mb-2">
        Catatan Kolom Kuning <span class="text-xs font-normal text-text-label">(Opsional)</span>
    </label>

    <div class="space-y-1.5">
        @foreach ($options as $value => $label)
            <label class="flex items-start gap-2 text-sm text-text-primary cursor-pointer">
                <input type="radio" name="highlight_mode" value="{{ $value }}" class="mt-1 accent-primary"
                    @checked($mode === $value)
                    onchange="var box = this.closest('[data-kwitansi-highlight]'); var input = box.querySelector('input[name=highlight_note]'); var custom = this.value === 'custom'; input.disabled = !custom; input.required = custom; box.querySelector('[data-highlight-note]').classList.toggle('hidden', !custom);">
                <span>{{ $label }}</span>
            </label>
        @endforeach
    </div>

    <div class="mt-2 {{ $mode === \App\Models\Administrasi\Kwintansi::HIGHLIGHT_CUSTOM ? '' : 'hidden' }}" data-highlight-note>
        <input type="text" name="highlight_note" id="{{ $idPrefix }}-highlight_note" value="{{ $note }}"
            class="w-full border rounded p-2" maxlength="255"
            placeholder="Contoh: *Total Uang Masuk Per 24 Oktober 2026 = Rp. 500.000.000"
            @disabled($mode !== \App\Models\Administrasi\Kwintansi::HIGHLIGHT_CUSTOM)
            @required($mode === \App\Models\Administrasi\Kwintansi::HIGHLIGHT_CUSTOM)
            oninvalid="this.setCustomValidity('Catatan kolom kuning tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>
</div>
