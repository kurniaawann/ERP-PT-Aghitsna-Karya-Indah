<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice Semen - {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            size: A4;
            margin: 10mm 12mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 9.5pt;
            color: #000;
            line-height: 1.1;
            padding: 7%;
        }

        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3px;
            table-layout: auto; /* Membiarkan lebar kolom menyesuaikan teks otomatis */
        }

        /* Padding 10px kiri-kanan sesuai permintaan */
        .main-table th, .main-table td {
            border: 1px solid #000;
            padding: 2px 10px; 
            font-size: 9pt;
            white-space: nowrap; /* Mencegah teks terpotong turun ke bawah */
        }

        /* Khusus Nama Barang boleh wrap jika teksnya sangat panjang */
        .col-nama-barang {
            white-space: normal !important;
        }

        /* Header Style Hijau Olive / Sage */
        .header-title {
            background-color: #a2c48c;
            text-align: center;
            font-weight: bold;
            font-size: 11pt;
            letter-spacing: 0.5px;
            padding: 4px 10px;
        }

        .table-header-row {
            background-color: #a2c48c;
            font-weight: bold;
            text-align: center;
        }

        /* Highlight Kuning */
        .yellow-bg {
            background-color: #ffff00;
            font-weight: bold;
        }

        .project-title-cell {
            background-color: #ffff00;
            font-weight: bold;
            font-size: 9pt;
        }

        /* Style Rekening Bank */
        .bank-info-cell {
            font-style: italic;
            font-size: 8.5pt;
        }

        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }

        /* Format Currency */
        .currency-cell {
            display: table;
            width: 100%;
        }
        .currency-symbol {
            display: table-cell;
            text-align: left;
            padding-right: 8px;
        }
        .currency-amount {
            display: table-cell;
            text-align: right;
        }

        /* Note NB */
        .note {
            font-style: italic;
            font-weight: bold;
            font-size: 8.5pt;
            margin-top: 6px;
            margin-bottom: 8px;
        }

        /* Total Akhir & Double Underline */
        .grand-total-container {
            width: 100%;
            margin-top: 6px;
        }

        .grand-total-table {
            float: right;
            border-collapse: collapse;
        }

        .grand-total-table td {
            padding: 2px 10px;
            font-size: 9.5pt;
            font-weight: bold;
        }

        .double-underline {
            border-top: 1.5px solid #000;
            border-bottom: 3px double #000;
        }

        /* Tanda Tangan */
        .signature-section {
            float: right;
            width: 200px;
            text-align: center;
            margin-top: 50px;
            clear: both;
        }

        .signature-space {
            height: 50px;
            margin-bottom: 4px;
        }

        .signature-space img {
            max-height: 50px;
            max-width: 160px;
        }

        .signature-title {
            font-size: 9.5pt;
            margin-top: 2px;
        }

        .signature-name {
            font-size: 9.5pt;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    @php
        $projects = is_string($invoice->projects) ? json_decode($invoice->projects, true) : $invoice->projects;
        $grandTotal = 0;
        $totalProjects = count($projects);
    @endphp

    <table class="main-table">
        <!-- Judul Invoice Atas -->
        <tr>
            <td colspan="5" class="header-title">INVOICE</td>
        </tr>
        <tr>
            <td style="width: 1%;">Tanggal</td>
            <td style="width: 1%; text-align: center;">:</td>
            <td colspan="3">
                {{ \Carbon\Carbon::parse($invoice->invoice_date)->isoFormat('dddd, D MMMM YYYY') }}
            </td>
        </tr>
        <tr>
            <td style="width: 1%;">Total Pembayaran</td>
            <td style="width: 1%; text-align: center;">:</td>
            <td colspan="3">Rp. {{ number_format($invoice->total_amount ?? 0, 0, ',', '.') }}</td>
        </tr>
        
        <!-- Pemisah Spasi Tipis -->
        <tr style="height: 6px;">
            <td style="border: none;"></td>
            <td style="border: none;"></td>
            <td style="border: none;"></td>
            <td style="border: none;"></td>
            <td style="border: none;"></td>
        </tr>

        <!-- Loop Setiap Proyek -->
        @foreach ($projects as $project)
            @php
                $items = $project['items'] ?? [];
                $subtotal = 0;
                foreach ($items as $item) {
                    $subtotal += (int) ($item['jumlah'] ?? 0);
                }
                $grandTotal += $subtotal;
                $account = \App\Models\Finance\PaymentAccount::find($project['payment_account_id'] ?? null);
            @endphp

            <!-- Header Kolom (Ukuran Menyesuaikan Teks + 10px Kiri Kanan) -->
            <tr class="table-header-row">
                <td class="center" style="width: 1%;">No.</td>
                <td class="center" style="width: 1%;">Tanggal</td>
                <td class="center col-nama-barang" style="width: auto;">Nama Barang</td>
                <td class="center" style="width: 1%;">QTY</td>
                <td class="center" style="width: 1%;">Jumlah</td>
            </tr>

            <!-- Baris Nama Proyek -->
            <tr>
                <td colspan="3" class="project-title-cell col-nama-barang">
                    Proyek {{ $project['nama_proyek'] ?? '-' }}
                    @if (!empty($project['pengurus_proyek']))
                        ({{ $project['pengurus_proyek'] }})
                    @endif
                </td>
                <td class="project-title-cell"></td>
                <td></td>
            </tr>

            <!-- Item Barang -->
            @foreach ($items as $item)
                <tr>
                    <td class="center">{{ $item['no'] ?? $loop->iteration }}.</td>
                    <td class="center">{{ $item['tanggal'] ? \Carbon\Carbon::parse($item['tanggal'])->format('d M Y') : '-' }}</td>
                    <td class="col-nama-barang">{{ $item['nama_barang'] ?? 'SEMEN' }}</td>
                    <td class="center">{{ $item['qty'] ?? 0 }} Zak</td>
                    <td>
                        <div class="currency-cell">
                            <span class="currency-symbol">Rp</span>
                            <span class="currency-amount">{{ number_format((int)($item['jumlah'] ?? 0), 0, ',', '.') }}</span>
                        </div>
                    </td>
                </tr>
            @endforeach

            <!-- Subtotal Proyek -->
            <tr class="yellow-bg">
                <td colspan="3" class="center bold col-nama-barang">
                    TOTAL 1 BON PROYEK {{ strtoupper($project['nama_proyek'] ?? '') }}
                </td>
                <td class="yellow-bg"></td>
                <td>
                    <div class="currency-cell bold">
                        <span class="currency-symbol">Rp</span>
                        <span class="currency-amount">{{ number_format($subtotal, 0, ',', '.') }}</span>
                    </div>
                </td>
            </tr>

            <!-- Rekening Bank -->
            <tr>
                <td colspan="3" class="bank-info-cell col-nama-barang">
                    Bank {{ $account->bank_name ?? 'BCA' }} : {{ $account->account_number ?? 'Nomor rekening' }} / A/N {{ strtoupper($account->account_holder ?? 'PEMILIK') }}
                </td>
                <td class="bank-info-cell"></td>
                <td class="bank-info-cell"></td>
            </tr>
        @endforeach
    </table>

    <!-- Catatan Tambahan (NB) -->
    @if(!empty($invoice->note))
        <div class="note">
            NB : {{ $invoice->note }}
        </div>
    @endif

    <!-- Grand Total -->
    <div class="grand-total-container">
        <table class="grand-total-table">
            <tr>
                <td style="padding-right: 15px;">TOTAL {{ $totalProjects }} INVOICE:</td>
                <td class="double-underline" style="min-width: 140px;">
                    <div class="currency-cell">
                        <span class="currency-symbol">Rp</span>
                        <span class="currency-amount">{{ number_format($grandTotal, 0, ',', '.') }}</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Tanda Tangan -->
    <div class="signature-section">
        @if ($invoice->signedBy)
            <div class="signature-title">
                {{ $invoice->signedBy->position }}
            </div>
            <div class="signature-space">
                @if ($invoice->signedBy->signature_image)
                    <img src="{{ storage_path('app/public/' . $invoice->signedBy->signature_image) }}"
                        alt="Tanda Tangan">
                @endif
            </div>
            <div class="signature-name">
                {{ $invoice->signedBy->name }}
            </div>
        @else
            <div class="signature-title">&nbsp;</div>
            <div class="signature-space"></div>
            <div class="signature-name">................</div>
        @endif
    </div>

</body>
</html>