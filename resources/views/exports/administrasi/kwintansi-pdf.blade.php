<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kwitansi - PT. Aghitsna Karya Indah</title>
    <style>
        @page {
            size: A4;
            margin: 0.3cm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            /* width: 210mm;
            height: 297mm;
            margin: 0 auto; */
            padding: 3mm;
            background: white;
        }

        .container {
            border: 2px solid #4a90a4;
            padding: 6px;
            height: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 4px;
            padding-bottom: 3px;
        }

        .logo {
            width: 50px;
            margin-right: 6px;
        }

        .company-info {
            flex: 1;
            text-align: left;
        }

        .company-name {
            color: #4a90a4;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 1px;
        }

        .company-details {
            font-size: 6.5px;
            line-height: 1.2;
            color: #333;
        }

        .receipt-meta {
            text-align: right;
            font-size: 7px;
            line-height: 1.1;
        }

        .title {
            text-align: center;
            color: #4a90a4;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 3px;
            margin: 6px 0;
        }

        .content-box {
            border: 2px solid #333;
            padding: 6px;
            margin-bottom: 6px;
            min-height: 110px;
        }

        .field-row {
            display: flex;
            margin-bottom: 5px;
            font-size: 9px;
        }

        .field-label {
            width: 100px;
            font-weight: normal;
        }

        .field-separator {
            width: 10px;
            text-align: center;
        }

        .field-value {
            flex: 1;
            border-bottom: 1px dotted #999;
            min-height: 12px;
            padding-left: 3px;
            margin-top: 10px;
        }

        .payment-section {
            margin-top: 8px;
        }

        .payment-lines {
            margin-left: 110px;
        }

        .payment-line {
            border-bottom: 1px dotted #999;
            min-height: 13px;
            margin-bottom: 4px;
        }

        .amount-box {
            border: 2px solid #4a90a4;
            padding: 8px 15px;
            margin: 6px 0;
            position: relative;
            min-height: 50px;
            background: linear-gradient(135deg, transparent 45%, #e8f4f8 45%, #e8f4f8 55%, transparent 55%);
        }

        .amount-section {
            display: inline-block;
            vertical-align: top;
            width: 50%;
        }

        .amount-label {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 2px;
            display: block;
        }

        .amount-value {
            font-size: 28px;
            font-weight: bold;
            line-height: 1.1;
        }

        .remainder-section {
            display: inline-block;
            vertical-align: top;
            width: 45%;
            float: right;
            text-align: left;
            padding-left: 10px;
        }

        .remainder-label {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 3px;
            display: block;
        }

        .remainder-value {
            border-bottom: 1px dotted #999;
            min-width: 140px;
            display: block;
            padding: 2px 5px;
            font-size: 11px;
            min-height: 18px;
        }

        .signature-section {
            border: 2px solid #333;
            padding: 6px;
            margin-bottom: 6px;
        }

        .payment-methods {
            margin-bottom: 6px;
            font-size: 8px;
        }

        .payment-methods>div {
            display: inline-block;
            margin-right: 15px;
        }

        .checkbox {
            display: inline-block;
            width: 10px;
            height: 10px;
            border: 1px solid #333;
            vertical-align: middle;
            margin-right: 3px;
        }

        .bank-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 8px;
            margin-bottom: 6px;
        }

        .bank-info {
            flex: 0 0 auto;
        }

        .bank-row {
            display: flex;
            align-items: center;
            margin-bottom: 4px;
        }

        .bank-label {
            font-style: italic;
            color: #4a90a4;
            width: 40px;
            margin-right: 5px;
        }

        .bank-value {
            border-bottom: 1px dotted #999;
            flex: 1;
            max-width: 200px;
            min-height: 12px;
        }

        .signature-box {
            width: 120px;
            text-align: center;
            margin-left: auto;
            margin-right: 0;
        }

        .signature-label {
            font-size: 8px;
            font-weight: bold;
            margin-bottom: 30px;
        }

        /* .signature-line {
            border: none;
            border-bottom: 1px solid #333;
            padding: 25px 10px 5px 10px;
            position: relative;
        }

        .signature-line::before {
            content: '(';
            position: absolute;
            left: 0px;
            bottom: -2px;
            font-size: 10px;
        }

        .signature-line::after {
            content: ')';
            position: absolute;
            right: 0px;
            bottom: -2px;
            font-size: 10px;
        } */

        .footer-note {
            font-size: 7px;
            font-style: italic;
            text-align: center;
            color: #4a90a4;
            padding: 4px;
            border-top: 1px solid #ccc;
        }

        .page-break {
            page-break-after: always;
        }

        @media print {
            body {
                margin: 0;
                padding: 3mm;
            }

            .container {
                page-break-inside: avoid;
            }

            .page-break {
                page-break-after: always;
            }
        }
    </style>
</head>

<body>
    @foreach ($kwintansis as $index => $kwintansi)
        <div class="container {{ $index < count($kwintansis) - 1 ? 'page-break' : '' }}">
            <div class="header">
                <div style="display: flex; flex: 1;">
                    <img src="{{ public_path('images/logo-company.png') }}" alt="Logo" class="logo">
                    <div class="company-info">
                        <div class="company-name">PT. AGHITSNA KARYA INDAH</div>
                        <div class="company-details">
                            JL. TANAH BARU RAYA PERUMNAS RT.01/05, BEJ DEPOK, JAWA BARAT<br>
                            Telp. 021-29034923 - 0812.9596.552<br>
                            Email: zulkarnainmarzuki@yahoo.com
                        </div>
                    </div>
                </div>
                <div class="receipt-meta">
                    <div>{{ $kwintansi->location }},
                        {{ \Carbon\Carbon::parse($kwintansi->kwintansi_date)->format('d/m/Y') }}</div>
                    <div>No: {{ $kwintansi->id_kwintansi }}</div>
                    <div>Hal: {{ \Carbon\Carbon::parse($kwintansi->kwintansi_date)->format('d M Y') }}</div>
                </div>
            </div>

            <div class="title">KWITANSI</div>

            <div class="content-box">
                <div class="field-row">
                    <div class="field-label">Sudah terima dari:</div>
                    {{-- <div class="field-separator"></div> --}}
                    <div class="field-value">{{ $kwintansi->received_from }}</div>
                </div>

                <div class="field-row">
                    <div class="field-label">Banyaknya uang:</div>
                    {{-- <div class="field-separator">:</div> --}}
                    <div class="field-value">{{ ucfirst(terbilang($kwintansi->amount)) }} rupiah</div>
                </div>

                <div class="payment-section">
                    <div class="field-row">
                        <div class="field-label">Untuk Pembayaran:</div>
                        {{-- <div class="field-separator">:</div> --}}
                        <div class="field-value">{{ $kwintansi->payment_for }}</div>
                    </div>

                    {{-- <div class="payment-lines">
                        <div class="payment-line"></div>
                        <div class="payment-line"></div>
                        <div class="payment-line"></div>
                        <div class="payment-line"></div>
                    </div> --}}
                </div>
            </div>

            <div class="amount-box">
                <div class="amount-section">
                    <div class="amount-label">Rp.</div>
                    <div class="amount-value">{{ number_format($kwintansi->amount, 0, ',', '.') }}</div>
                </div>
                <div class="remainder-section">
                    <div class="remainder-label">Sisa:</div>
                    <div class="remainder-value">
                        {{ $kwintansi->remaining ? 'Rp. ' . number_format($kwintansi->remaining, 0, ',', '.') : '' }}
                    </div>
                </div>
            </div>

            <div class="signature-section">
                <div class="payment-methods">
                    <div>
                        <span class="checkbox"></span> TUNAI
                    </div>
                    <div>
                        <span class="checkbox"></span> CHEQUE
                    </div>
                    <div>
                        <span class="checkbox"></span> BILYET GIRO
                    </div>
                </div>

                @if ($kwintansi->include_bank && $kwintansi->paymentAccount)
                    <div class="bank-details">
                        <div class="bank-info">
                            <div class="bank-row">
                                <div class="bank-label">BANK</div>
                                <div class="bank-value">{{ $kwintansi->paymentAccount->account_name }}</div>
                            </div>
                            <div class="bank-row">
                                <div class="bank-label">NO.</div>
                                <div class="bank-value">{{ $kwintansi->paymentAccount->account_number }}</div>
                            </div>
                            <div class="bank-row">
                                <div class="bank-label">TGL</div>
                                <div class="bank-value">
                                    {{ \Carbon\Carbon::parse($kwintansi->kwintansi_date)->format('d/m/Y') }}</div>
                            </div>
                        </div>
                        <div class="signature-box">
                            <div class="signature-label">SIGNATURE</div>
                            <div class="signature-line">(__________________)</div>
                        </div>
                    </div>
                @else
                    <div class="bank-details">
                        <div class="bank-info">
                            <div class="bank-row">
                                <div class="bank-label">BANK:</div>
                                <div class="bank-value"></div>
                            </div>
                            <div class="bank-row">
                                <div class="bank-label">NO:</div>
                                <div class="bank-value"></div>
                            </div>
                            <div class="bank-row">
                                <div class="bank-label">TGL:</div>
                                <div class="bank-value"></div>
                            </div>
                        </div>
                        <div class="signature-box">
                            <div class="signature-label">SIGNATURE</div>
                            <div class="signature-line"></div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="footer-note">
                Kwitansi ini baru dianggap sah, setelah Pembayaran dengan Bilyet Giro / Cheque tsb, dapat di uangkan.
            </div>
        </div>
    @endforeach
</body>

</html>
