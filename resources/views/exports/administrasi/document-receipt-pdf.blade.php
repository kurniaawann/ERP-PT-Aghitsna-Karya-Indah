<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tanda Terima Dokumen</title>
    <style>
        @page {
            margin: 20mm 15mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.6;
            padding: 0 30px;
        }

        .page-break {
            page-break-after: always;
        }

        /* Header dengan border */
        .header-box {
            border: 2px solid #000;
            padding: 10px;
            margin-bottom: 20px;
            margin-top: 30px;
        }

        .header-content {
            display: table;
            width: 100%;
        }

        .logo-section {
            display: table-cell;
            width: 100px;
            vertical-align: middle;
            padding-right: 15px;
        }

        .logo-section img {
            width: 90px;
            height: auto;
        }

        .company-info {
            display: table-cell;
            vertical-align: middle;
        }

        .company-name {
            font-weight: bold;
            font-size: 14pt;
            color: #c00000;
            margin-bottom: 2px;
        }

        .company-address {
            font-size: 8pt;
            line-height: 1.3;
        }

        /* Title */
        .title {
            text-align: center;
            font-weight: bold;
            font-size: 14pt;
            text-decoration: underline;
            margin-bottom: 30px;
            margin-top: 30px;
        }

        /* Form fields */
        .form-row {
            margin-bottom: 40px;
            display: table;
            width: 100%;
        }

        .form-label {
            display: table-cell;
            width: 130px;
            vertical-align: top;
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
            padding-top: 4px;
            min-height: 20px;
        }

        /* Date time row */
        .date-time-row {
            margin-bottom: 20px;
            display: table;
            width: 100%;
        }

        .date-section {
            display: table-cell;
            width: 55%;
            vertical-align: middle;
        }

        .jam-section {
            display: table-cell;
            width: 45%;
            text-align: left;
            padding-left: 40px;
            vertical-align: middle;
        }

        .date-label {
            display: inline-block;
            width: 120px;
        }

        .jam-label {
            display: inline-block;
            width: 30px;
        }

        .date-value,
        .jam-value {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 120px;
        }

        /* Signature sec5ion */
        .signature-area {
            margin-top: 40px;
            display: table;
            width: 100%;
        }

        .signature-location {
            margin-top: 70px;
        }

        .signature-left,
        .signature-right {
            display: table-cell;
            width: 50%;
            text-align: center;
        }

        .signature-label {
            margin-bottom: 60px;
        }

        .signature-name {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 150px;
            padding-bottom: 2px;
        }
    </style>
</head>

<body>
    @foreach ($documents as $index => $document)
        @if ($index > 0)
            <div class="page-break"></div>
        @endif

        {{-- Header dengan border --}}
        <div class="header-box">
            <div class="header-content">
                <div class="logo-section">
                    <img src="{{ public_path('images/logo.jpeg') }}" alt="Logo">
                </div>
                <div class="company-info">
                    <div class="company-name">PT. AGHITSNA KARYA INDAH</div>
                    <div class="company-address">
                    PT AGHITSNA KARYA INDAH<br>
                    JL. TANAH BARU RAYA PERTIWI RT/01/05
                    BEJI, DEPOK, JAWA BARAT
                    TELP. 021-29034923 - 0812 9596 522 <br>
                    Email : Design@aghitsna.id
                    </div>
                </div>
            </div>
        </div>

        {{-- Title --}}
        <div class="title">TANDA TERIMA DOKUMEN</div>

        {{-- Form fields --}}
        <div class="form-row">
            <div class="form-label">Telah Terima Dari</div>
            <div class="form-colon">:</div>
            <div class="form-value">{{ $document->received_from }}</div>
        </div>

        <div class="form-row">
            <div class="form-label">Perihal</div>
            <div class="form-colon">:</div>
            <div class="form-value">{{ $document->regarding }}</div>
        </div>

        <div class="form-row">
            <div class="form-label">Berupa</div>
            <div class="form-colon">:</div>
            <div class="form-value">{{ $document->form_of }}</div>
        </div>

        {{-- Date and Time --}}
        <div class="date-time-row">
            <div class="date-section">
                <span class="date-label">Hari / Tanggal</span>
                <span> :</span>
                <span
                    class="date-value">{{ \Carbon\Carbon::parse($document->receipt_date)->locale('id')->isoFormat('dddd, D MMMM Y') }}</span>
            </div>
            <div class="jam-section">
                <span class="jam-label">Jam</span>
                <span>:</span>
                <span class="jam-value">{{ \Carbon\Carbon::parse($document->receipt_time)->format('H:i') }}</span>
            </div>
        </div>

        {{-- Signature section --}}
        <div class="signature-area">
            <div class="signature-left">
                <div class="signature-label">Yang Menyerahkan,</div>
                <div class="signature-name">&nbsp;</div>
            </div>

            <div class="signature-right">
                <div class="signature-label">Yang Menerima,</div>
                <div class="signature-name">&nbsp;</div>
                <div class="signature-location">Depok,
                    {{ \Carbon\Carbon::parse($document->receipt_date)->locale('id')->isoFormat('D MMMM Y') }}</div>
            </div>
        </div>
    @endforeach
</body>

</html>
