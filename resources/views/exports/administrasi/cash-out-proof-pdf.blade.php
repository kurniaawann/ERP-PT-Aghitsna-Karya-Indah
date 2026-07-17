{{-- ============================================================
     TEMPLATE PDF BUKTI KAS KELUAR
     ============================================================

     Template ini digunakan untuk export PDF bukti kas keluar.
     Mendukung dua jenis template:

     1. STANDARD (template_type = 'standard')
        - Header dengan logo perusahaan
        - Judul "BUKTI KAS KELUAR"
        - Informasi BKK No, Cek No, Tanggal
        - Form fields: Dibayarkan Kepada, Jumlah Dibayar, Keterangan
        - Box jumlah dalam Rupiah
        - Tanda tangan: Direktur, Kabag Keuangan, Diterima Oleh

     2. HOLLOW (template_type = 'hollow')
        - Header dengan logo + info perusahaan "DESIGN AND BUILD PT. AGHITSNA KARYA INDAH"
        - Label "HOLLOW" + Judul "BUKTI KAS KELUAR"
        - Informasi BKK No, Cek No, Tanggal
        - Form fields dalam box border: Dibayarkan Kepada, Jumlah Dibayar, Keterangan
        - Box jumlah dalam Rupiah
        - Tanda tangan: Manager, Kabag Keuangan, Diterima Oleh

     Layout:
     - 2 form per halaman (page break setiap 2 record)
     - Ukuran kertas: A4 Portrait
     - Margin: 5mm
     - Font: Times New Roman

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
            margin: 5mm 5mm 5mm 5mm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            margin: 0;
            padding: 0;
            font-size: 10px;
        }

        .container {
            border: 2px solid #000;
            padding: 10px;
            box-sizing: border-box;
            margin-bottom: 20px;
        }

        {{-- ==========================================
             STYLE: Template Standard
             ========================================== --}}
        .standard .header {
            display: table;
            width: 100%;
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .standard .header-left {
            display: table-cell;
            width: 25%;
            vertical-align: middle;
        }

        .standard .logo-container {
            display: inline-block;
            vertical-align: middle;
            margin-right: 10px;
        }

        .standard .logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }

        .standard .header-center {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: middle;
        }

        .standard .header-right {
            display: table-cell;
            width: 25%;
            vertical-align: top;
            text-align: left;
            font-size: 9px;
        }

        .standard .title {
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 2px;
        }

        .standard .signature-col {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            vertical-align: bottom;
        }

        .standard .signature-title {
            font-weight: bold;
            margin-bottom: 70px;
            font-size: 10px;
            margin-top: 25px;
        }

        {{-- ==========================================
             STYLE: Template Hollow
             ========================================== --}}
        .hollow .header {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }

        .hollow .header-left {
            display: table-cell;
            width: 40%;
            vertical-align: top;
        }

        .hollow .logo-container {
            display: inline-block;
            vertical-align: top;
            margin-right: 8px;
        }

        .hollow .logo {
            width: 55px;
            height: 55px;
            object-fit: contain;
        }

        .hollow .company-info {
            display: inline-block;
            vertical-align: top;
        }

        .hollow .company-name {
            font-size: 9px;
            font-weight: bold;
            line-height: 1.2;
            margin-bottom: 2px;
        }

        .hollow .company-subtitle {
            font-size: 7px;
            line-height: 1.2;
        }

        .hollow .header-center {
            display: table-cell;
            width: 30%;
            text-align: center;
            vertical-align: middle;
            padding-top: 10px;
        }

        .hollow .hollow-title {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 3px;
            margin-bottom: 15px;
        }

        .hollow .main-title {
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .hollow .header-right {
            display: table-cell;
            width: 30%;
            vertical-align: top;
            text-align: left;
            font-size: 9px;
        }

        .hollow .signature-col {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            vertical-align: bottom;
        }

        .hollow .signature-title {
            font-size: 9px;
            margin-bottom: 60px;
            margin-top: 20px;
        }

        {{-- ==========================================
             STYLE: Komponen Form (Digunakan Kedua Template)
             ========================================== --}}
        .form-row {
            margin-bottom: 8px;
            display: table;
            width: 100%;
        }

        .form-label {
            display: table-cell;
            width: 20%;
            padding: 3px 0;
        }

        .form-separator {
            display: table-cell;
            width: 1%;
            text-align: center;
        }

        .form-value {
            display: table-cell;
            width: 79%;
            border-bottom: 1px solid #000;
            padding: 3px 0;
            min-height: 15px;
        }

        .amount-section {
            text-align: right;
            margin-top: 50px;
        }

        .amount-box {
            display: inline-block;
            border: 2px solid #000;
            padding: 8px 15px;
            min-width: 200px;
            text-align: left;
            font-size: 11px;
        }

        .signature-section {
            display: table;
            width: 100%;
            margin-top: 40px;
        }

        .signature-name {
            border-top: 1px solid #000;
            display: inline-block;
            padding-top: 25px;
            min-width: 150px;
            font-size: 9px;
        }

        .keterangan-box {
            min-height: 40px;
            padding: 5px;
        }
    </style>
</head>

<body>
    {{-- Iterasi setiap data bukti kas keluar --}}
    @foreach ($cashOuts as $index => $cashOut)

        {{-- Page break setiap 2 record (2 form per halaman) --}}
        @if ($index > 0 && $index % 2 == 0)
            <div style="page-break-before: always;"></div>
        @endif

        <div class="container {{ $cashOut->template_type ?? 'standard' }}">

            {{-- ==========================================
                 TEMPLATE: HOLLOW
                 ========================================== --}}
            @if (($cashOut->template_type ?? 'standard') == 'hollow')

                {{-- Header: Logo + Info Perusahaan + Judul + Info BKK --}}
                <div class="header">
                    <div class="header-left">
                        <div class="logo-container">
                            <img src="{{ public_path('images/logo.jpeg') }}" alt="Logo" class="logo">
                        </div>
                        <div class="company-info">
                            <div class="company-name">
                                DESIGN AND BUILD<br>
                                PT. AGHITSNA KARYA INDAH
                            </div>
                        </div>
                    </div>
                    <div class="header-center">
                        <div class="hollow-title">HOLLOW</div>
                        <div class="main-title">BUKTI KAS KELUAR</div>
                    </div>
                    <div class="header-right">
                        <div><strong>BKK No.</strong> : {{ $cashOut->bkk_no }}</div>
                        <div><strong>Cek No.</strong> : {{ $cashOut->cek_no }}</div>
                        <div><strong>Tanggal</strong> :
                            {{ \Carbon\Carbon::parse($cashOut->date)->locale('id')->isoFormat('D MMMM Y') }}</div>
                    </div>
                </div>

                {{-- Form Fields --}}
                <div style="border: 2px solid #000; padding: 8px; margin-top: 10px;">
                    <div class="form-row">
                        <div class="form-label">Dibayarkan Kepada</div>
                        <div class="form-separator">:</div>
                        <div class="form-value">{{ $cashOut->paid_to }}</div>
                    </div>

                    <div class="form-row">
                        <div class="form-label">Jumlah Dibayar</div>
                        <div class="form-separator">:</div>
                        <div class="form-value">{{ ucwords(trim(terbilang($cashOut->amount))) }} Rupiah</div>
                    </div>

                    <div class="form-row">
                        <div class="form-label" style="vertical-align: top;">Keterangan</div>
                        <div class="form-separator" style="vertical-align: top;">:</div>
                        <div class="form-value keterangan-box">{{ $cashOut->description ?? '-' }}</div>
                    </div>

                    {{-- Box Jumlah dalam Rupiah --}}
                    <div class="amount-section">
                        <div class="amount-box">
                            <strong>Rp.</strong> {{ number_format($cashOut->amount, 0, ',', '.') }}
                        </div>
                    </div>
                </div>

                {{-- Tanda Tangan: Manager, Kabag Keuangan, Diterima Oleh --}}
                <div class="signature-section">
                    <div class="signature-col">
                        <div class="signature-title">
                            <strong>MENYETUJUI,</strong><br>
                            <strong>MANAGER</strong>
                        </div>
                        <div class="signature-name">( {{ $cashOut->director ?? 'SISWORO SUBENO' }} )</div>
                    </div>
                    <div class="signature-col">
                        <div class="signature-title">
                            <strong>MENGETAHUI,</strong><br>
                            <strong>KABAG.KEUANGAN</strong>
                        </div>
                        <div class="signature-name">( {{ $cashOut->finance_head ?? 'KAMILA' }} )</div>
                    </div>
                    <div class="signature-col">
                        <div class="signature-title">
                            <strong>DITERIMA OLEH,</strong>
                        </div>
                        <div class="signature-name">( _________________ )</div>
                    </div>
                </div>

            {{-- ==========================================
                 TEMPLATE: STANDARD
                 ========================================== --}}
            @else

                {{-- Header: Logo + Judul + Info BKK --}}
                <div class="header">
                    <div class="header-left">
                        <div class="logo-container">
                            <img src="{{ public_path('images/logo.jpeg') }}" alt="Logo" class="logo">
                        </div>
                    </div>
                    <div class="header-center">
                        <div class="title">BUKTI KAS KELUAR</div>
                    </div>
                    <div class="header-right">
                        <div><strong>BKK No.</strong> : {{ $cashOut->bkk_no }}</div>
                        <div><strong>Cek No.</strong> : {{ $cashOut->cek_no }}</div>
                        <div><strong>Tanggal</strong> :
                            {{ \Carbon\Carbon::parse($cashOut->date)->locale('id')->isoFormat('D MMMM Y') }}</div>
                    </div>
                </div>

                {{-- Form Fields --}}
                <div class="form-row">
                    <div class="form-label">Dibayarkan Kepada</div>
                    <div class="form-separator">:</div>
                    <div class="form-value">{{ $cashOut->paid_to }}</div>
                </div>

                <div class="form-row">
                    <div class="form-label">Jumlah Dibayar</div>
                    <div class="form-separator">:</div>
                    <div class="form-value">{{ ucwords(trim(terbilang($cashOut->amount))) }} Rupiah</div>
                </div>

                <div class="form-row">
                    <div class="form-label" style="vertical-align: top;">Keterangan</div>
                    <div class="form-separator" style="vertical-align: top;">:</div>
                    <div class="form-value keterangan-box">{{ $cashOut->description ?? '-' }}</div>
                </div>

                {{-- Box Jumlah dalam Rupiah --}}
                <div class="amount-section">
                    <div class="amount-box">
                        <strong>Rp.</strong> {{ number_format($cashOut->amount, 0, ',', '.') }}
                    </div>
                </div>

                {{-- Tanda Tangan: Direktur, Kabag Keuangan, Diterima Oleh --}}
                <div class="signature-section">
                    <div class="signature-col">
                        <div class="signature-title">DIREKTUR,</div>
                        <div class="signature-name">( {{ $cashOut->director ?? 'Zulkarnain,ST.,MT' }} )</div>
                    </div>
                    <div class="signature-col">
                        <div class="signature-title">KABAG.KEUANGAN,</div>
                        <div class="signature-name">( {{ $cashOut->finance_head ?? 'Kamila,AMK' }} )</div>
                    </div>
                    <div class="signature-col">
                        <div class="signature-title">DITERIMA OLEH,</div>
                        <div class="signature-name">( _________________ )</div>
                    </div>
                </div>

            @endif
        </div>
    @endforeach
</body>

</html>
