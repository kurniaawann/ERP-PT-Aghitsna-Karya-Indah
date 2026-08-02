<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kwitansi - PT. Aghitsna Karya Indah</title>
    <style>
        @page {
            size: A4;
            margin: 0.5cm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            padding: 5mm;
            background: #ffffff;
            color: #0f386b; /* Warna biru khas cetakan kwitansi */
        }

        .container {
            border: 2px solid #0f386b;
            padding: 14px;
            margin-bottom: 20px;
            position: relative;
        }

        /* HEADER TABLE */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .company-cell {
            vertical-align: top;
            width: 65%;
        }

        .company-header {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .logo {
            width: 55px;
            height: auto;
        }

        .company-title {
            font-size: 15px;
            font-weight: 900;
            color: #0f386b;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .company-details {
            font-size: 8.5px;
            line-height: 1.35;
            color: #0f386b;
            font-weight: 600;
        }

        .title-meta-cell {
            vertical-align: top;
            text-align: right;
            width: 35%;
        }

        .title-kwitansi {
            font-size: 20px;
            font-weight: 900;
            letter-spacing: 2px;
            color: #0f386b;
            margin-bottom: 6px;
        }

        .meta-table {
            float: right;
            border-collapse: collapse;
        }

        .meta-table td {
            font-size: 10px;
            font-weight: bold;
            padding: 2px 0;
            color: #0f386b;
        }

        .meta-label {
            width: 35px;
            text-align: left;
        }

        .meta-colon {
            width: 10px;
            text-align: center;
        }

        .meta-value {
            border-bottom: 1px dotted #0f386b;
            min-width: 130px;
            text-align: left;
            padding-left: 4px;
        }

        /* FORM CONTENT BOX */
        .content-box {
            border: 1.5px solid #0f386b;
            padding: 10px;
            margin-bottom: 8px;
        }

        .form-table {
            width: 100%;
            border-collapse: collapse;
        }

        .form-table td {
            padding: 5px 0;
            vertical-align: bottom;
            font-size: 11px;
            color: #0f386b;
        }

        .label-col {
            width: 125px;
            font-weight: bold;
            white-space: nowrap;
        }

        .colon-col {
            width: 15px;
            text-align: center;
            font-weight: bold;
        }

        .value-col {
            border-bottom: 1px dotted #0f386b;
            padding-left: 6px;
            font-size: 11px;
            font-weight: bold;
        }

        .multiline-dots {
            border-bottom: 1px dotted #0f386b;
            height: 22px;
        }

        /* AMOUNT BOX (NOMINAL & SISA) */
        .amount-box {
            border: 3px double #0f386b; /* Garis ganda khas fisik kwitansi */
            padding: 6px 12px;
            margin-bottom: 10px;
        }

        .amount-table {
            width: 100%;
            border-collapse: collapse;
        }

        .amount-table td {
            vertical-align: middle;
        }

        .rp-cell {
            width: 45%;
            font-size: 18px;
            font-weight: 900;
            color: #0f386b;
        }

        .rp-slash {
            display: inline-block;
            margin: 0 4px;
            font-size: 20px;
            font-weight: normal;
        }

        .remainder-cell {
            width: 55%;
            text-align: right;
            font-size: 13px;
            font-weight: bold;
            color: #0f386b;
        }

        .remainder-dots {
            display: inline-block;
            border-bottom: 1px dotted #0f386b;
            /* min-width: 160px; */
            height: 14px;
            margin-left: 6px;
        }

        /* PAYMENT METHOD & BANK / SIGNATURE */
        .payment-methods {
            margin-bottom: 8px;
            font-size: 10px;
            font-weight: bold;
            color: #0f386b;
        }

        .checkbox-item {
            display: inline-block;
            margin-right: 25px;
        }

        .checkbox {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1.5px solid #0f386b;
            vertical-align: middle;
            margin-right: 5px;
        }

        .footer-layout {
            width: 100%;
            border-collapse: collapse;
        }

        .bank-cell {
            vertical-align: top;
            width: 60%;
        }

        .bank-table {
            width: 90%;
            border-collapse: collapse;
        }

        .bank-table td {
            padding: 3px 0;
            font-size: 10px;
            font-weight: bold;
            color: #0f386b;
            vertical-align: bottom;
        }

        .bank-label {
            width: 45px;
        }

        .bank-colon {
            width: 12px;
            text-align: center;
        }

        .bank-value {
            border-bottom: 1px dotted #0f386b;
            padding-left: 4px;
        }

        .signature-cell {
            vertical-align: top;
            text-align: center;
            width: 40%;
        }

        .signature-title {
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1px;
            color: #0f386b;
            margin-bottom: 50px;
        }

        .signature-line {
            font-size: 11px;
            color: #0f386b;
        }

        /* FOOTER NOTE */
        .footer-note {
            margin-top: 10px;
            font-size: 8px;
            font-style: italic;
            text-align: center;
            color: #0f386b;
            padding-top: 5px;
            border-top: 1px solid #0f386b;
        }

        .page-break {
            page-break-after: always;
        }

        @media print {
            body {
                padding: 0;
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
            
            <!-- HEADER -->
            <table class="header-table">
                <tr>
                    <!-- LOGO & PERUSAHAAN (KIRI) -->
                    <td class="company-cell">
                        <table style="width: 100%;">
                            <tr>
                                <td style="width: 60px; vertical-align: top;">
                                    <img src="{{ public_path('images/logo.jpeg') }}" alt="Logo" class="logo">
                                </td>
                                <td style="vertical-align: top;">
                                    <div class="company-title">PT. AGHITSNA KARYA INDAH</div>
                                    <div class="company-details">
                                        JL. TANAH BARU RAYA PERTIWI RT.01/05, BEJI, DEPOK, JAWA BARAT<br>
                                        Telp. 021-29034923 - 0812.9596.552<br>
                                        Email : design@aghitsna.id
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>

                    <!-- JUDUL & META NO/TGL (KANAN) -->
                    <td class="title-meta-cell">
                        <div class="title-kwitansi">KWITANSI</div>
                        <table class="meta-table">
                            <tr>
                                <td class="meta-label">No.</td>
                                <td class="meta-colon">:</td>
                                <td class="meta-value">{{ $kwintansi->id_kwintansi }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">Tgl.</td>
                                <td class="meta-colon">:</td>
                                <td class="meta-value">{{ \Carbon\Carbon::parse($kwintansi->kwintansi_date)->format('d F Y') }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- CONTENT BOX -->
            <div class="content-box">
                <table class="form-table">
                    <tr>
                        <td class="label-col">Sudah terima dari</td>
                        <td class="colon-col">:</td>
                        <td class="value-col">{{ $kwintansi->received_from }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Banyaknya uang</td>
                        <td class="colon-col">:</td>
                        <td class="value-col">{{ ucfirst(terbilang($kwintansi->amount)) }} rupiah</td>
                    </tr>
                    <tr>
                        <td class="label-col">Untuk Pembayaran</td>
                        <td class="colon-col">:</td>
                        <td class="value-col">{{ $kwintansi->payment_for }}</td>
                    </tr>
                    <!-- Garis titik-titik tambahan untuk catatan panjang -->
                    <tr>
                        <td colspan="3" class="multiline-dots"></td>
                    </tr>
                </table>
            </div>

            <!-- AMOUNT BOX (Rp. & Sisa) -->
            <div class="amount-box">
                <table class="amount-table">
                    <tr>
                        <td class="rp-cell">
                            Rp. {{ number_format($kwintansi->amount, 0, ',', '.') }}
                        </td>
                        <td class="remainder-cell">
                            Sisa : <span class="remainder-dots">{{ $kwintansi->remaining ? 'Rp. ' . number_format($kwintansi->remaining, 0, ',', '.') : '' }}</span>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- PAYMENT METHOD -->
            <div class="payment-methods">
                <span class="checkbox-item"><span class="checkbox"></span> TUNAI</span>
                <span class="checkbox-item"><span class="checkbox"></span> CHEQUE</span>
                <span class="checkbox-item"><span class="checkbox"></span> BILYET GIRO</span>
            </div>

            <!-- FOOTER (BANK DETAILS & SIGNATURE) -->
            <table class="footer-layout">
                <tr>
                    <td class="bank-cell">
                        <table class="bank-table">
                            <tr>
                                <td class="bank-label">BANK</td>
                                <td class="bank-colon">:</td>
                                <td class="bank-value">
                                    {{ $kwintansi->include_bank && $kwintansi->paymentAccount ? $kwintansi->paymentAccount->bank_name : '' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="bank-label">NO.</td>
                                <td class="bank-colon">:</td>
                                <td class="bank-value">
                                    {{ $kwintansi->include_bank && $kwintansi->paymentAccount ? $kwintansi->paymentAccount->account_number : '' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="bank-label">TGL.</td>
                                <td class="bank-colon">:</td>
                                <td class="bank-value">
                                    {{ $kwintansi->include_bank ? \Carbon\Carbon::parse($kwintansi->kwintansi_date)->format('d/m/Y') : '' }}
                                </td>
                            </tr>
                        </table>
                    </td>

                    <td class="signature-cell">
                        <div class="signature-title">SIGNATURE,</div>
                        <div class="signature-line">( ____________________ )</div>
                    </td>
                </tr>
            </table>

            <!-- FOOTER NOTE -->
            <div class="footer-note">
                Kwitansi ini baru dianggap sah, setelah Pembayaran dengan Bilyet Giro / Cheque tsb, dapat di uangkan.
            </div>

        </div>
    @endforeach
</body>

</html>