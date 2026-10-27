{{-- Satu baris Data Semen pada repeater modal tambah/edit DO Semen.
     Variabel: $index (indeks array cements[..]), $cement (Cement|null).
     Gaya input diatur CSS .fi-repeater (resources/css/app.css) agar sama
     dengan baris yang ditambahkan lewat JS (buildCementRow). --}}
@php
    $cement = $cement ?? null;
@endphp

<tr class="cement-row">
    <td>
        <input type="date" name="cements[{{ $index }}][tanggal]" value="{{ $cement?->tanggal?->format('Y-m-d') }}">
    </td>
    <td>
        <input type="text" name="cements[{{ $index }}][nama_proyek]" value="{{ $cement?->nama_proyek }}"
            placeholder="Nama proyek" required maxlength="255"
            oninvalid="this.setCustomValidity('Nama proyek tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </td>
    <td>
        <input type="text" name="cements[{{ $index }}][name]" value="{{ $cement?->name }}"
            placeholder="Nama pemesan" maxlength="255">
    </td>
    <td>
        <input type="number" name="cements[{{ $index }}][jumlah]" value="{{ $cement?->jumlah ?? 0 }}" min="0"
            class="text-center" placeholder="0" required>
    </td>
    <td>
        <input type="text" name="cements[{{ $index }}][harga]"
            value="Rp {{ number_format($cement?->harga ?? 0, 0, ',', '.') }}"
            class="cement-harga text-right" placeholder="Rp 0" required inputmode="numeric">
    </td>
    <td>
        <input type="date" name="cements[{{ $index }}][tanggal_lunas]"
            value="{{ $cement?->tanggal_lunas?->format('Y-m-d') }}">
    </td>
    <td class="text-center">
        <button type="button" class="remove-row-btn" title="Hapus baris">
            <x-fi.icon name="trash" class="h-5 w-5" />
        </button>
    </td>
</tr>
