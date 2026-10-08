{{-- ============================================================
     TEMPLATE PDF BUKTI KAS KELUAR
     ============================================================

     Template ini digunakan untuk export PDF bukti kas keluar.
     Mendukung jenis template:

     1. STANDARD (template_type = 'standard' / 'bkc')
        - Header dengan logo perusahaan
        - Judul "BUKTI KAS KELUAR" (atau "BUKTI CEK/GIRO KELUAR" untuk bkc)
        - Informasi BKK No, Cek No, Tanggal (ukuran kecil, pojok kanan atas)
        - Form fields: Dibayarkan Kepada, Jumlah Dibayar, Keterangan
        - Box jumlah dalam Rupiah ("Rp. 50.000"), rata kanan, lebar
          mengikuti nominal & sudut membulat
        - Tanda tangan: Direktur, Kabag Keuangan, Diterima Oleh

     2. HOLLOW (template_type = 'hollow')
        - Header dengan logo perusahaan
        - Label "HOLLOW" + Judul "BUKTI KAS KELUAR"
        - Informasi BKK No, Cek No, Tanggal
        - Form fields dalam box border: Dibayarkan Kepada, Jumlah Dibayar, Keterangan
        - Box jumlah dalam Rupiah
        - Tanda tangan: Manager, Kabag Keuangan, Diterima Oleh

     Layout:
     - 1 form per halaman (page break sebelum setiap record berikutnya)
     - Ukuran kertas: A4 Portrait
     - Margin: 8mm
     - Font: Times New Roman 12pt (kop/judul lebih besar)
     - Watermark logo perusahaan transparan di tengah setiap form

     Variabel yang digunakan:
     - $cashOuts: Koleksi model CashOutProof yang akan dicetak
============================================================ --}}
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Bukti Kas Keluar</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm 8mm 8mm 8mm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            margin: 0;
            padding: 0;
            font-size: 12pt;
            line-height: 1.3;
            color: #000;
        }

        .container {
            position: relative;
            border: 2px solid #000;
            padding: 14px 16px;
            box-sizing: border-box;
        }

        {{-- Watermark logo: transparan, di tengah form, berada di belakang konten --}}
        .watermark {
            position: absolute;
            top: 110px;
            left: 50%;
            width: 380px;
            margin-left: -190px;
            opacity: 0.12;
            z-index: -1;
        }

        {{-- ==========================================
             STYLE: Header (Kop) - Dipakai Kedua Template
             ========================================== --}}
        .header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .header td {
            padding: 0;
        }

        .header-left {
            width: 21%;
            vertical-align: middle;
        }

        .header-center {
            width: 47%;
            text-align: center;
            vertical-align: middle;
        }

        .header-right {
            width: 32%;
            vertical-align: top;
        }

        .logo {
            width: 135px;
            height: auto;
        }

        {{-- Info BKK/Cek/Tanggal dibuat kecil agar tidak mendominasi kop --}}
        .doc-info {
            border-collapse: collapse;
            float: right;
            font-size: 10pt;
            line-height: 1.2;
        }

        .doc-info td {
            padding: 0;
            vertical-align: top;
            white-space: nowrap;
        }

        .doc-info .doc-info-label {
            font-weight: bold;
            padding-right: 4px;
        }

        .doc-info .doc-info-colon {
            padding-right: 4px;
        }

        .standard .header {
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .standard .title {
            font-size: 17pt;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .hollow .hollow-title {
            font-size: 22pt;
            font-weight: bold;
            letter-spacing: 4px;
            margin-bottom: 8px;
        }

        .hollow .main-title {
            font-size: 16pt;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .hollow .form-box {
            border: 2px solid #000;
            padding: 10px 12px;
        }

        {{-- ==========================================
             STYLE: Komponen Form (Digunakan Kedua Template)
             ========================================== --}}
        .form-row {
            margin-bottom: 10px;
            width: 100%;
            border-collapse: collapse;
        }

        .form-label {
            width: 24%;
            padding: 4px 0;
            vertical-align: bottom;
            white-space: nowrap;
        }

        .form-separator {
            width: 2%;
            text-align: center;
            padding: 4px 6px;
            vertical-align: bottom;
        }

        .form-value {
            width: 74%;
            border-bottom: 1px solid #000;
            padding: 4px 2px;
            vertical-align: bottom;
        }

        .amount-section {
            text-align: right;
            margin-top: 24px;
        }

        {{-- Box jumlah: lebar mengikuti nominal (tanpa min-width), sudut membulat --}}
        .amount-box {
            display: inline-block;
            border: 2px solid #000;
            border-radius: 8px;
            padding: 6px 14px;
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            white-space: nowrap;
        }

        {{-- ==========================================
             STYLE: Tanda Tangan (Digunakan Kedua Template)
             ========================================== --}}
        .signature-section {
            width: 100%;
            border-collapse: collapse;
            margin-top: 28px;
        }

        .signature-section td {
            width: 33.33%;
            text-align: center;
            padding: 0 8px;
        }

        .signature-title {
            vertical-align: top;
            font-size: 12pt;
        }

        .standard .signature-title {
            font-weight: bold;
        }

        .signature-space {
            height: 80px;
            vertical-align: bottom;
        }

        .signature-space img {
            max-height: 70px;
            max-width: 150px;
        }

        .signature-name {
            vertical-align: top;
        }

        .signature-name span {
            display: inline-block;
            min-width: 190px;
            border-top: 1px solid #000;
            padding-top: 4px;
        }
    </style>
</head>

<body>
    {{-- Iterasi setiap data bukti kas keluar --}}
    @foreach ($cashOuts as $index => $cashOut)

        {{-- Page break sebelum setiap record berikutnya (1 form per halaman) --}}
        @if ($index > 0 && $index % 1 == 0)
            <div style="page-break-before: always;"></div>
        @endif

        @php
    // Data penandatangan dari snapshot Data Petinggi.
    // Nilai null, string "null", atau string kosong dianggap tidak ada.
    $sig = is_array($cashOut->signatures) ? $cashOut->signatures : [];
    $isHollow = ($cashOut->template_type ?? 'standard') === 'hollow';
    $isBkc = ($cashOut->template_type ?? 'standard') === 'bkc';
    $containerClass = $isHollow ? 'hollow' : 'standard';
    $docTitle = $isBkc ? 'BUKTI CEK/GIRO KELUAR' : ($isHollow ? 'BUKTI KAS KELUAR' : 'BUKTI KAS KELUAR');
    $docNoLabel = $isBkc ? 'BKC No.' : 'BKK No.';

    // Helper untuk memastikan null / "null" / kosong tidak ditampilkan
    $validValue = function ($value) {
        return $value !== null
            && trim((string) $value) !== ''
            && strtolower(trim((string) $value)) !== 'null';
    };

    // =========================
    // DIREKTUR / MANAGER
    // =========================
    $directorSig = $sig['direktur'] ?? [];
    $directorTitle = $isHollow
        ? 'MENYETUJUI,<br><strong>MANAGER</strong>'
        : 'DIREKTUR,';

    $directorName = $directorSig['name'] ?? null;

    if (!$validValue($directorName)) {
        $directorName = $cashOut->director;

        if (!$validValue($directorName)) {
            $directorName = $isHollow
                ? 'SISWORO SUBENO'
                : 'Zulkarnain,ST.,MT';
        }
    }

    $directorImage = $validValue($directorSig['signature_image'] ?? null)
        ? $directorSig['signature_image']
        : null;


    // =========================
    // KABAG KEUANGAN
    // =========================
    $financeSig = $sig['kabag_keuangan'] ?? [];
    $financeTitle = $isHollow
        ? 'MENGETAHUI,<br><strong>KABAG.KEUANGAN</strong>'
        : 'KABAG.KEUANGAN,';

    $financeName = $financeSig['name'] ?? null;

    if (!$validValue($financeName)) {
        $financeName = $cashOut->finance_head;

        if (!$validValue($financeName)) {
            $financeName = 'Kamila,AMK';
        }
    }

    $financeImage = $validValue($financeSig['signature_image'] ?? null)
        ? $financeSig['signature_image']
        : null;


    // =========================
    // DITERIMA OLEH
    // =========================
    $receivedSig = $sig['diterima_oleh'] ?? [];
    $receivedTitle = 'DITERIMA OLEH,';

    $receivedName = $receivedSig['name'] ?? null;

    if (!$validValue($receivedName)) {
        $receivedName = '_________________';
    }

    $receivedImage = $validValue($receivedSig['signature_image'] ?? null)
        ? $receivedSig['signature_image']
        : null;
@endphp

        @php
            // Susunan kolom tanda tangan (urutan: Direktur/Manager, Kabag Keuangan, Diterima Oleh)
            $signers = [
                ['title' => $directorTitle, 'name' => $directorName, 'image' => $directorImage],
                ['title' => $financeTitle, 'name' => $financeName, 'image' => $financeImage],
                ['title' => $receivedTitle, 'name' => $receivedName, 'image' => $receivedImage],
            ];
        @endphp

        <div class="container {{ $containerClass }}">

            {{-- Watermark logo perusahaan (transparan, di belakang konten) --}}
            <img src="{{ public_path('images/logo.jpeg') }}" alt="" class="watermark">

            {{-- Header: Logo + Judul + Info BKK --}}
            <table class="header">
                <tr>
                    <td class="header-left">
                        <img src="{{ public_path('images/logo.jpeg') }}" alt="Logo" class="logo">
                    </td>
                    <td class="header-center">
                        @if ($isHollow)
                            <div class="hollow-title">HOLLOW</div>
                            <div class="main-title">{{ $docTitle }}</div>
                        @else
                            <div class="title">{{ $docTitle }}</div>
                        @endif
                    </td>
                    <td class="header-right">
                        <table class="doc-info">
                            <tr>
                                <td class="doc-info-label">{{ $docNoLabel }}</td>
                                <td class="doc-info-colon">:</td>
                                <td>{{ $cashOut->bkk_no }}</td>
                            </tr>
                            <tr>
                                <td class="doc-info-label">Cek No.</td>
                                <td class="doc-info-colon">:</td>
                                <td>{{ $cashOut->cek_no }}</td>
                            </tr>
                            <tr>
                                <td class="doc-info-label">Tanggal</td>
                                <td class="doc-info-colon">:</td>
                                <td>{{ \Carbon\Carbon::parse($cashOut->date)->locale('id')->isoFormat('D MMMM Y') }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            {{-- Form Fields (template hollow dibungkus box border) --}}
            <div class="{{ $isHollow ? 'form-box' : '' }}">
                <table class="form-row">
                    <tr>
                        <td class="form-label">Dibayarkan Kepada</td>
                        <td class="form-separator">:</td>
                        <td class="form-value">{{ $cashOut->paid_to }}</td>
                    </tr>
                </table>

                <table class="form-row">
                    <tr>
                        <td class="form-label">Jumlah Dibayar</td>
                        <td class="form-separator">:</td>
                        <td class="form-value">{{ ucwords(trim(terbilang($cashOut->amount))) }} Rupiah</td>
                    </tr>
                </table>

                <table class="form-row">
                    <tr>
                        <td class="form-label" style="vertical-align: top;">Keterangan</td>
                        <td class="form-separator" style="vertical-align: top;">:</td>
                        <td class="form-value">{{ $cashOut->description ?? '-' }}</td>
                    </tr>
                </table>

                {{-- Box Jumlah dalam Rupiah --}}
                <div class="amount-section">
                    <div class="amount-box">
                        {{ format_rupiah($cashOut->amount) }}
                    </div>
                </div>
            </div>

            {{-- Tanda Tangan: Direktur/Manager, Kabag Keuangan, Diterima Oleh --}}
            <table class="signature-section">
                <tr>
                    @foreach ($signers as $signer)
                        <td class="signature-title">{!! $signer['title'] !!}</td>
                    @endforeach
                </tr>
                <tr>
                    @foreach ($signers as $signer)
                        <td class="signature-space">
                            @if ($signer['image'])
                                <img src="{{ storage_path('app/public/' . $signer['image']) }}"
                                    alt="Tanda tangan {{ $signer['name'] }}">
                            @endif
                        </td>
                    @endforeach
                </tr>
                <tr>
                    @foreach ($signers as $signer)
                        <td class="signature-name"><span>( {{ $signer['name'] }} )</span></td>
                    @endforeach
                </tr>
            </table>
        </div>
    @endforeach
</body>

</html>
