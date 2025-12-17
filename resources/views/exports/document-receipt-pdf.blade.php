<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tanda Terima Dokumen</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            font-size: 12px;
            line-height: 1.6;
        }

        .document-page {
            page-break-after: always;
            margin-bottom: 40px;
        }

        .document-page:last-child {
            page-break-after: auto;
        }

        .header {
            display: table;
            width: 100%;
            border: 2px solid #000;
            padding: 15px;
            margin-bottom: 30px;
        }

        .header-left {
            display: table-cell;
            width: 100px;
            vertical-align: middle;
            text-align: center;
        }

        .logo {
            max-width: 80px;
            height: auto;
        }

        .header-right {
            display: table-cell;
            vertical-align: middle;
            padding-left: 15px;
        }

        .company-name {
            font-weight: bold;
            font-size: 16px;
            color: #c00;
            margin-bottom: 5px;
        }

        .company-address {
            font-size: 9px;
            line-height: 1.4;
            color: #333;
        }

        .title {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            text-decoration: underline;
            margin-bottom: 40px;
        }

        .form-content {
            margin-bottom: 40px;
        }

        .form-row {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .form-label {
            display: table-cell;
            width: 150px;
            vertical-align: top;
            padding-right: 10px;
        }

        .form-colon {
            display: table-cell;
            width: 10px;
            vertical-align: top;
        }

        .form-value {
            display: table-cell;
            vertical-align: top;
            border-bottom: 1px solid #000;
            min-height: 20px;
            padding-bottom: 2px;
        }

        .date-time-row {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .date-section {
            display: table-cell;
            width: 50%;
        }

        .date-label {
            display: inline-block;
            width: 100px;
        }

        .date-value {
            display: inline-block;
            border-bottom: 1px solid #000;
            min-width: 150px;
            padding-bottom: 2px;
        }

        .jam-label {
            display: inline-block;
            margin-left: 20px;
        }

        .jam-value {
            display: inline-block;
            border-bottom: 1px solid #000;
            min-width: 100px;
            padding-bottom: 2px;
        }

        .signature-section {
            margin-top: 80px;
            display: table;
            width: 100%;
        }

        .signature-left {
            display: table-cell;
            width: 45%;
            text-align: center;
        }

        .signature-right {
            display: table-cell;
            width: 45%;
            text-align: center;
            padding-left: 10%;
        }

        .signature-location {
            margin-bottom: 5px;
        }

        .signature-label {
            margin-bottom: 80px;
        }

        .signature-name {
            border-bottom: 1px solid #000;
            padding: 0 20px;
            display: inline-block;
            min-width: 150px;
        }

        .signature-image {
            position: absolute;
            margin-top: -70px;
            margin-left: -75px;
            max-width: 150px;
            max-height: 60px;
        }
    </style>
</head>

<body>
    @foreach ($documents as $document)
        <div class="document-page">
            {{-- Header dengan Logo dan Info Perusahaan --}}
            <div class="header">
                <div class="header-left">
                    <img src="{{ public_path('images/logo.jpeg') }}" alt="Logo" class="logo">
                </div>
                <div class="header-right">
                    <div class="company-name">PT. AGHITSNA KARYA INDAH</div>
                    <div class="company-address">
                        JL. PERWIRA NO.16, TAMAN RAWA RAYA BLOK 01/08, PONDOK JAYA BARAT<br>
                        TELP: 021-29069099 - 0812.9701.023, Email: damayanti@linuxmail.org / aghtitsnakarya@yahoo.com
                    </div>
                </div>
            </div>

            {{-- Title --}}
            <div class="title">TANDA TERIMA DOKUMEN</div>

            {{-- Form Content --}}
            <div class="form-content">
                {{-- Telah Terima Dari --}}
                <div class="form-row">
                    <div class="form-label">Telah Terima Dari</div>
                    <div class="form-colon">:</div>
                    <div class="form-value">{{ $document->received_from }}</div>
                </div>

                {{-- Perihal --}}
                <div class="form-row">
                    <div class="form-label">Perihal</div>
                    <div class="form-colon">:</div>
                    <div class="form-value">{{ $document->regarding }}</div>
                </div>

                {{-- Berupa --}}
                <div class="form-row">
                    <div class="form-label">Berupa</div>
                    <div class="form-colon">:</div>
                    <div class="form-value">{{ $document->form_of }}</div>
                </div>

                {{-- Hari/Tanggal dan Jam --}}
                <div class="date-time-row">
                    <div class="date-section">
                        <span class="date-label">Hari / Tanggal</span>
                        <span>:</span>
                        <span class="date-value">
                            {{ \Carbon\Carbon::parse($document->receipt_date)->locale('id')->isoFormat('dddd, D MMMM Y') }}
                        </span>
                    </div>
                    <div>
                        <span class="jam-label">Jam</span>
                        <span>:</span>
                        <span
                            class="jam-value">{{ \Carbon\Carbon::parse($document->receipt_time)->format('H:i') }}</span>
                    </div>
                </div>
            </div>

            {{-- Signature Section --}}
            <div class="signature-section">
                <div class="signature-left">
                    <div class="signature-location">
                        {{ $document->location }}, {{ \Carbon\Carbon::parse($document->receipt_date)->format('d') }}
                        {{ \Carbon\Carbon::parse($document->receipt_date)->locale('id')->isoFormat('MMMM') }}
                        {{ \Carbon\Carbon::parse($document->receipt_date)->format('Y') }}
                    </div>
                    <div class="signature-label">Yang Menyerahkan,</div>

                    @if ($document->submitter_signature)
                        <img src="{{ $document->submitter_signature }}" alt="Signature" class="signature-image">
                    @endif

                    <div class="signature-name">
                        <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u>
                    </div>
                </div>

                <div class="signature-right">
                    <div class="signature-location">&nbsp;</div>
                    <div class="signature-label">Yang Menerima,</div>

                    @if ($document->receiver_signature)
                        <img src="{{ $document->receiver_signature }}" alt="Signature" class="signature-image">
                    @endif

                    <div class="signature-name">
                        <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</body>

</html>
