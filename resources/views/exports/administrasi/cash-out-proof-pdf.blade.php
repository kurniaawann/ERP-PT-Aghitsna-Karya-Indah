<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Kas Keluar</title>
    <style>
        @page {
            margin: 10mm 10mm;
            size: A4 portrait;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
        }

        .page-break {
            page-break-after: always;
        }

        /* Container untuk 2 form dalam 1 halaman */
        .form-container {
            width: 100%;
            height: 48%;
            /* Setengah halaman dengan margin */
            margin-bottom: 15px;
            border: 2px solid #000;
            padding: 12px;
            position: relative;
        }

        /* Header dengan logo dan company info */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }

        .logo-section {
            display: table-cell;
            width: 80px;
            vertical-align: middle;
            padding-right: 10px;
        }

        .logo-section img {
            width: 70px;
            height: auto;
        }

        .company-info {
            display: table-cell;
            vertical-align: middle;
        }

        .company-name {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .company-tagline {
            font-size: 8pt;
            font-style: italic;
            margin-bottom: 2px;
        }

        .company-address {
            font-size: 7pt;
            line-height: 1.3;
        }

        /* Title area */
        .title-section {
            text-align: center;
            margin-bottom: 8px;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
        }

        .title-main {
            font-size: 13pt;
            font-weight: bold;
            margin-bottom: 3px;
        }

        /* Document info (BKK, Cek, Tanggal) - kanan atas */
        .doc-info {
            position: absolute;
            top: 12px;
            right: 12px;
            text-align: right;
            font-size: 8pt;
            line-height: 1.6;
        }

        .doc-info-row {
            margin-bottom: 1px;
        }

        .doc-info-label {
            display: inline-block;
            width: 55px;
            text-align: left;
        }

        .doc-info-colon {
            display: inline-block;
            width: 8px;
        }

        .doc-info-value {
            display: inline-block;
            min-width: 80px;
            border-bottom: 1px solid #000;
            text-align: left;
        }

        /* Main content */
        .content-section {
            margin-top: 50px;
            /* Beri ruang untuk doc-info */
        }

        /* Form row untuk Dibayarkan Kepada */
        .form-row {
            margin-bottom: 10px;
            display: table;
            width: 100%;
        }

        .form-label {
            display: table-cell;
            width: 140px;
            vertical-align: top;
            font-size: 9pt;
        }

        .form-colon {
            display: table-cell;
            width: 10px;
            vertical-align: top;
        }

        .form-value {
            display: table-cell;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
            font-size: 9pt;
        }

        /* Jumlah Dibayar dengan garis */
        .amount-lines {
            margin-bottom: 10px;
        }

        .amount-line {
            border-bottom: 1px solid #000;
            min-height: 18px;
            margin-bottom: 3px;
            padding-top: 2px;
            font-size: 9pt;
        }

        /* Keterangan */
        .description-section {
            margin-bottom: 10px;
        }

        .description-label {
            font-size: 9pt;
            margin-bottom: 3px;
        }

        .description-box {
            border: 1px solid #000;
            min-height: 50px;
            padding: 5px;
            font-size: 8pt;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        /* Bottom section - Box Nominal dan Signatures */
        .bottom-section {
            margin-top: 12px;
            display: table;
            width: 100%;
        }

        /* Box nominal besar kanan bawah */
        .nominal-box-container {
            display: table-cell;
            width: 35%;
            vertical-align: top;
            padding-right: 10px;
        }

        .nominal-box {
            border: 2px solid #000;
            padding: 8px;
            text-align: center;
        }

        .nominal-label {
            font-size: 9pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .nominal-value {
            font-size: 12pt;
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
            min-height: 25px;
        }

        /* Signature section */
        .signatures-container {
            display: table-cell;
            width: 65%;
            vertical-align: top;
        }

        .signatures {
            display: table;
            width: 100%;
        }

        .signature-col {
            display: table-cell;
            text-align: center;
            vertical-align: top;
            font-size: 8pt;
        }

        .signature-label {
            margin-bottom: 35px;
        }

        .signature-name {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 100px;
            padding-bottom: 2px;
            font-size: 8pt;
        }
    </style>
</head>

<body>
    @php
        $chunked = $cashOuts->chunk(2); // Bagi menjadi grup 2 form per halaman
    @endphp

    @foreach ($chunked as $pageIndex => $pageItems)
        @if ($pageIndex > 0)
            <div class="page-break"></div>
        @endif

        @foreach ($pageItems as $cashOut)
            <div class="form-container">
                {{-- Header --}}
                <div class="header">
                    <div class="logo-section">
                        <img src="{{ public_path('images/logo.jpeg') }}" alt="Logo">
                    </div>
                    <div class="company-info">
                        <div class="company-name">PT. AGHITSNA KARYA INDAH</div>
                        <div class="company-tagline">DESIGN AND BUILD</div>
                        <div class="company-address">
                            JL. PETITION NO.34, TAMAN SARI/ RAWA BUAYA/ CENGKARENG/ BARAT
                        </div>
                    </div>
                </div>

                {{-- Document Info (kanan atas) --}}
                <div class="doc-info">
                    <div class="doc-info-row">
                        <span class="doc-info-label">BKK No.</span>
                        <span class="doc-info-colon">:</span>
                        <span class="doc-info-value">{{ $cashOut->bkk_no }}</span>
                    </div>
                    <div class="doc-info-row">
                        <span class="doc-info-label">Cek No.</span>
                        <span class="doc-info-colon">:</span>
                        <span class="doc-info-value">{{ $cashOut->cek_no }}</span>
                    </div>
                    <div class="doc-info-row">
                        <span class="doc-info-label">Tanggal</span>
                        <span class="doc-info-colon">:</span>
                        <span class="doc-info-value">{{ \Carbon\Carbon::parse($cashOut->date)->format('d/m/Y') }}</span>
                    </div>
                </div>

                {{-- Title --}}
                <div class="title-section">
                    <div class="title-main">BUKTI KAS KELUAR</div>
                </div>

                {{-- Content Section --}}
                <div class="content-section">
                    {{-- Dibayarkan Kepada --}}
                    <div class="form-row">
                        <div class="form-label">Dibayarkan Kepada</div>
                        <div class="form-colon">:</div>
                        <div class="form-value">{{ $cashOut->paid_to }}</div>
                    </div>

                    {{-- Jumlah Dibayar --}}
                    <div class="form-row">
                        <div class="form-label">Jumlah Dibayar</div>
                        <div class="form-colon">:</div>
                        <div class="form-value"></div>
                    </div>

                    {{-- Additional lines for amount (garis kosong) --}}
                    <div class="amount-lines">
                        <div class="amount-line"></div>
                        <div class="amount-line"></div>
                    </div>

                    {{-- Keterangan --}}
                    <div class="description-section">
                        <div class="description-label">Keterangan :</div>
                        <div class="description-box">{{ $cashOut->description ?? '' }}</div>
                    </div>

                    {{-- Bottom: Nominal Box & Signatures --}}
                    <div class="bottom-section">
                        {{-- Box Nominal --}}
                        <div class="nominal-box-container">
                            <div class="nominal-box">
                                <div class="nominal-label">Rp.</div>
                                <div class="nominal-value">{{ number_format($cashOut->amount, 0, ',', '.') }}</div>
                            </div>
                        </div>

                        {{-- Signatures --}}
                        <div class="signatures-container">
                            <div class="signatures">
                                <div class="signature-col">
                                    <div class="signature-label">DIREKTUR,</div>
                                    <div class="signature-name">({{ $cashOut->director ?? 'Zulkarnain,ST.,MT' }})</div>
                                </div>

                                <div class="signature-col">
                                    <div class="signature-label">KABAG KEUANGAN,</div>
                                    <div class="signature-name">({{ $cashOut->finance_head ?? 'Kamila,AMK' }})</div>
                                </div>

                                <div class="signature-col">
                                    <div class="signature-label">DITERIMA OLEH,</div>
                                    <div class="signature-name">
                                        (&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @endforeach
</body>

</html>
