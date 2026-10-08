{{--
    Blok tanda tangan tunggal di kanan untuk laporan (Laporan Pengeluaran /
    Kas Kantor). Penandatangan dipilih saat cetak dari Data Penandatangan
    (ReportSignerService): jabatan → gambar tanda tangan (bila ada) → ( Nama ).

    Variabel:
    - $signer    : array{name, position, signature_image}|null
    - $uppercase : bool — jabatan huruf kapital (opsional, default false)
--}}
@php
    $signerTitle = trim($signer['position'] ?? '') ?: 'Dibuat / Diperiksa';
    $signerTitle = ($uppercase ?? false) ? mb_strtoupper($signerTitle) : $signerTitle;
    $signerName = trim($signer['name'] ?? '');
    $signerImage = !empty($signer['signature_image']) ? storage_path('app/public/' . $signer['signature_image']) : null;
@endphp
<table>
    <tr>
        <td style="width: 60%;"></td>
        <td style="width: 40%;">
            <div>{{ $signerTitle }}</div>
            <div style="height: 55px; padding-top: 3px;">
                @if ($signerImage && is_file($signerImage))
                    <img src="{{ $signerImage }}" alt="Tanda Tangan" style="max-height: 52px; max-width: 170px;">
                @endif
            </div>
            <div>( {{ $signerName !== '' ? $signerName : str_repeat('.', 30) }} )</div>
        </td>
    </tr>
</table>
