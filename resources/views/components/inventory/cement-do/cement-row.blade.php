{{-- Satu baris Data Semen pada repeater halaman form DO Semen.
     Variabel:
     - $index  : indeks array cements[..] ('IDX' untuk <template>, diganti JS)
     - $cement : Cement|null (data tersimpan, halaman edit)
     - $old    : array|null (input lama dari old('cements') saat validasi gagal;
                 diutamakan di atas $cement)
     Gaya input diatur CSS .fi-repeater (resources/css/app.css); total baris
     (.cement-total) dihitung ulang oleh resources/js/pages/inventory/cement-do/form.js. --}}
@php
    $cement = $cement ?? null;
    $old = $old ?? null;

    if (is_array($old)) {
        $row = [
            'tanggal' => $old['tanggal'] ?? null,
            'nama_proyek' => $old['nama_proyek'] ?? null,
            'name' => $old['name'] ?? null,
            'jumlah' => $old['jumlah'] ?? 0,
            'harga' => $old['harga'] ?? 'Rp 0',
            'tanggal_lunas' => $old['tanggal_lunas'] ?? null,
        ];
    } else {
        $row = [
            'tanggal' => $cement?->tanggal?->format('Y-m-d'),
            'nama_proyek' => $cement?->nama_proyek,
            'name' => $cement?->name,
            'jumlah' => $cement?->jumlah ?? 0,
            'harga' => 'Rp ' . number_format($cement?->harga ?? 0, 0, ',', '.'),
            'tanggal_lunas' => $cement?->tanggal_lunas?->format('Y-m-d'),
        ];
    }

    $total = (int) $row['jumlah'] * (int) preg_replace('/[^0-9]/', '', (string) $row['harga']);
@endphp

<tr class="cement-row">
    <td>
        <input type="date" name="cements[{{ $index }}][tanggal]" value="{{ $row['tanggal'] }}" aria-label="Tanggal">
    </td>
    <td>
        <input type="text" name="cements[{{ $index }}][nama_proyek]" value="{{ $row['nama_proyek'] }}"
            placeholder="Nama proyek" required maxlength="255" aria-label="Nama proyek"
            oninvalid="this.setCustomValidity('Nama proyek tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </td>
    <td>
        <input type="text" name="cements[{{ $index }}][name]" value="{{ $row['name'] }}"
            placeholder="Nama pemesan" maxlength="255" aria-label="Nama pemesan">
    </td>
    <td>
        <input type="number" name="cements[{{ $index }}][jumlah]" value="{{ $row['jumlah'] }}" min="0"
            class="cement-jumlah text-center" placeholder="0" required aria-label="Jumlah (zak)">
    </td>
    <td>
        <input type="text" name="cements[{{ $index }}][harga]" value="{{ $row['harga'] }}"
            class="cement-harga text-right" placeholder="Rp 0" required inputmode="numeric" aria-label="Harga per zak">
    </td>
    <td>
        <input type="date" name="cements[{{ $index }}][tanggal_lunas]" value="{{ $row['tanggal_lunas'] }}"
            aria-label="Tanggal lunas">
    </td>
    <td class="cement-total whitespace-nowrap !ps-4 text-end text-sm font-medium tabular-nums text-gray-950">
        Rp {{ number_format($total, 0, ',', '.') }}
    </td>
    <td class="text-center">
        <button type="button" class="remove-row-btn" title="Hapus baris">
            <x-fi.icon name="trash" class="h-5 w-5" />
        </button>
    </td>
</tr>
