<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kwitansi - PT. Aghitsna Karya Indah</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1cm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            padding: 20px;
            background: #ffffff;
            color: #000000;
            font-size: 11pt;
        }

        .container {
            padding: 12px;
            position: relative;
            border: 2px solid #000000;
        }

        /* HEADER */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .logo {
            max-width: 160px;
            height: auto;
        }

        /* META BOX (KANAN ATAS) - Garis bawah hanya dari : sampai value */
        .meta-table {
            border-collapse: collapse;
            float: right;
        }

        .meta-table td {
            padding: 3px 4px;
            font-size: 10pt;
            font-weight: bold;
            vertical-align: bottom;
        }

        .meta-label {
            width: 90px;
            border: none; /* Tanpa garis pada label */
        }

        .meta-colon {
            width: 15px;
            text-align: center;
            border-bottom: 1px solid #000000; /* Garis bawah mulai dari : */
        }

        .meta-value {
            min-width: 140px;
            border-bottom: 1px solid #000000; /* Garis bawah dilanjutkan ke value */
        }

        /* JUDUL KWITANSI */
        .title-kwitansi {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            letter-spacing: 2px;
            margin: 15px 0 15px 0;
        }

        /* FORM ISIAN UTAMA - Garis bawah dari : sampai ke value */
        .form-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .form-table td {
            padding: 6px 4px;
            vertical-align: bottom;
            font-size: 11pt;
        }

        .label-col {
            width: 140px;
            font-weight: bold;
            border: none; /* Tanpa garis pada label */
        }

        .colon-col {
            width: 15px;
            text-align: center;
            font-weight: bold;
            border-bottom: 1px solid #000000; /* Garis bawah mulai dari : */
        }

        .value-col {
            font-weight: normal;
            border-bottom: 1px solid #000000; /* Garis bawah dilanjutkan ke value */
        }

        /* SHADING PADA JUMLAH DIBAYAR (TERBILANG) */
        .terbilang-box {
            background-color: #e6e6e6;
            font-style: italic;
            font-weight: bold;
            padding: 2px 6px;
            display: inline-block;
            width: 98%;
            box-sizing: border-box;
        }

        /* SECTION HIGHLIGHT & NOMINAL */
        .middle-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            margin-top: 12px;
        }

        .highlight-text {
            background-color: #ffff00;
            font-weight: bold;
            font-size: 11pt;
            padding: 3px 6px;
            display: inline-block;
        }

        .amount-box {
            background-color: #ffffcc;
            border: 1px solid #000000;
            font-size: 14pt;
            font-weight: bold;
            font-style: italic;
            text-align: center;
            padding: 8px 15px;
            display: inline-block;
            float: right;
        }

        /* FOOTER (BANK & TANDA TANGAN) */
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        .bank-info {
            font-size: 10pt;
            font-weight: bold;
            line-height: 1.6;
        }

        .disclaimer {
            color: #d90000;
            font-style: italic;
            font-size: 9pt;
            margin-top: 15px;
            max-width: 90%;
        }

        .signature-cell {
            text-align: center;
            vertical-align: top;
            width: 200px;
        }

        .signature-title {
            font-size: 11pt;
            margin-bottom: 12px;
        }

        .signature-name {
            font-weight: bold;
            margin-top: 5px;
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

            <!-- HEADER (LOGO & META KWITANSI) -->
            <table class="header-table">
                <tr>
                    <!-- LOGO + NAMA PERUSAHAAN -->
                    <td style="vertical-align: top;">
                        <img src="{{ public_path('images/logo.jpeg') }}" alt="PT. AGHITSNA KARYA INDAH" class="logo">
                    </td>

                    <!-- META NO / TANGGAL -->
                    <td style="vertical-align: top; text-align: right;">
                        <table class="meta-table">
                            <tr>
                                <td class="meta-label">Kwitansi No.</td>
                                <td class="meta-colon">:</td>
                                <td class="meta-value">{{ $kwintansi->invoice_number ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">No.</td>
                                <td class="meta-colon">:</td>
                                <td class="meta-value">{{ $kwintansi->payment_sequence ? str_pad((string) $kwintansi->payment_sequence, 3, '0', STR_PAD_LEFT) : $kwintansi->id_kwintansi }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">Tanggal</td>
                                <td class="meta-colon">:</td>
                                <td class="meta-value">{{ \Carbon\Carbon::parse($kwintansi->kwintansi_date)->translatedFormat('j F Y') }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- JUDUL -->
            <div class="title-kwitansi">KWITANSI</div>

            <!-- FORM ISIAN UTAMA -->
            <table class="form-table">
                <!-- BARIS 1: TELAH TERIMA DARI -->
                <tr>
                    <td class="label-col">Telah Terima Dari</td>
                    <td class="colon-col">:</td>
                    <td class="value-col">{{ $kwintansi->received_from }}</td>
                </tr>

                <!-- BARIS 2: JUMLAH DIBAYAR -->
                <tr>
                    <td class="label-col">Jumlah Dibayar</td>
                    <td class="colon-col">:</td>
                    <td class="value-col">
                        <div class="terbilang-box">
                            {{ ucfirst(terbilang($kwintansi->amount)) }} Rupiah
                        </div>
                    </td>
                </tr>

                <!-- BARIS 3: KETERANGAN (PEMBAYARAN KE BERAPA) -->
                <tr>
                    <td class="label-col">Keterangan</td>
                    <td class="colon-col">:</td>
                    <td class="value-col">
                        {{ $kwintansi->payment_sequence ? 'Uang Masuk ke '.$kwintansi->payment_sequence : ($kwintansi->payment_for ?? '-') }}
                    </td>
                </tr>

                <!-- BARIS 4: INFO PROYEK (DESKRIPSI + LOKASI) -->
                @if (in_array(auth()->user()?->role, ['admin', 'superadmin'], true) && (!empty($kwintansi->invoiceProyek?->project_description) || !empty($kwintansi->invoiceProyek?->location)))
                    <tr>
                        <td class="label-col"></td>
                        <td class="colon-col"></td>
                        <td class="value-col">
                            {{ $kwintansi->invoiceProyek->project_description ?? '' }}
                            @if (!empty($kwintansi->invoiceProyek->location))
                                {{ $kwintansi->invoiceProyek->location }}
                            @endif
                        </td>
                    </tr>
                @endif
            </table>

            <!-- MIDDLE SECTION (TOTAL UANG MASUK & AMOUNT BOX) -->
            <table class="middle-table">
                <tr>
                    <td style="vertical-align: middle;">
                        @if (isset($kwintansi->total_accumulated) && $kwintansi->total_accumulated)
                            <span class="highlight-text">
                                *Total Uang Masuk Per {{ \Carbon\Carbon::parse($kwintansi->kwintansi_date)->translatedFormat('j F Y') }} = Rp. {{ number_format($kwintansi->total_accumulated, 0, ',', '.') }},-
                            </span>
                        @elseif($kwintansi->remaining)
                            <span class="highlight-text">
                                *Total Uang Masuk Per = Rp. {{ number_format($kwintansi->remaining, 0, ',', '.') }},-
                            </span>
                        @endif
                    </td>
                    <td style="vertical-align: middle; text-align: right; width: 40%;">
                        <div class="amount-box">
                            Rp. {{ number_format($kwintansi->amount, 0, ',', '.') }},-
                        </div>
                    </td>
                </tr>
            </table>

            <!-- FOOTER (BANK DETAILS & SIGNATURE) -->
            <table class="footer-table">
                <tr>
                    <!-- REKENING BANK & DISCLAIMER -->
                    <td style="vertical-align: top; width: 65%;">
                        <div class="bank-info">
                            @php
                                $bankAccounts = $kwintansi->invoiceProyek?->selected_payment_accounts
                                    ? \App\Models\Finance\PaymentAccount::whereIn('id', (array) $kwintansi->invoiceProyek->selected_payment_accounts)->orderBy('id')->get()
                                    : collect([$kwintansi->include_bank ? $kwintansi->paymentAccount : null]);
                                $bankAccounts = $bankAccounts->filter();
                            @endphp

                            @forelse ($bankAccounts as $bankAccount)
                                <div>{{ $bankAccount->bank_name }} &nbsp;/&nbsp; {{ $bankAccount->account_number }} &nbsp;/&nbsp; {{ $bankAccount->account_holder }}</div>
                            @empty
                                <div>-</div>
                            @endforelse
                        </div>

                        <div class="disclaimer">
                            *Kwitansi ini baru di anggap sah, setelah pembayaran dengan Bilyet Giro/Cheque tersebut dapat di uangkan.
                        </div>
                    </td>

                    <!-- TANDA TANGAN -->
                    <td class="signature-cell">
                        <div class="signature-title">Signature,</div>
                        <div style="min-height: 50px;">
                            @if ($kwintansi->invoiceProyek?->signedBy?->signature_image)
                                <img src="{{ storage_path('app/public/' . $kwintansi->invoiceProyek->signedBy->signature_image) }}"
                                    alt="Tanda Tangan" style="max-height: 50px; max-width: 130px;">
                            @endif
                        </div>
                        <div class="signature-name">
                            ( {{ $kwintansi->invoiceProyek?->signedBy?->name ?? 'Zulkarnain,S.T.,M.T.' }} )
                        </div>
                    </td>
                </tr>
            </table>

        </div>
    @endforeach
</body>

</html>