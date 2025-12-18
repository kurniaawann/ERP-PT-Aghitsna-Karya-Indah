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
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 10px;
        }

        .container {
            border: 2px solid #000;
            padding: 10px;
            /* width: 70%; */
            box-sizing: border-box;
            margin-bottom: 20px;
        }

        .header {
            display: table;
            width: 100%;
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .header-left {
            display: table-cell;
            width: 25%;
            vertical-align: middle;
        }

        .logo-container {
            display: inline-block;
            vertical-align: middle;
            margin-right: 10px;
        }

        .logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }


        .header-center {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: middle;
        }

        .header-right {
            display: table-cell;
            width: 25%;
            vertical-align: top;
            text-align: left;
            font-size: 9px;
        }

        .company-name {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .company-subtitle {
            font-size: 9px;
        }

        .title {
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 2px;
        }

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
            /* margin-bottom: 50px; */
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

        .signature-col {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            vertical-align: bottom;

        }

        .signature-title {
            font-weight: bold;
            margin-bottom: 70px;
            font-size: 10px;
            margin-top: 25px;
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
    @foreach ($cashOuts as $index => $cashOut)
        @if ($index > 0 && $index % 2 == 0)
            <div style="page-break-before: always;"></div>
        @endif

        <div class="container">
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

            <div class="amount-section">
                <div class="amount-box">
                    <strong>Rp.</strong> {{ number_format($cashOut->amount, 0, ',', '.') }}
                </div>
            </div>

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
        </div>
    @endforeach
</body>

</html>
