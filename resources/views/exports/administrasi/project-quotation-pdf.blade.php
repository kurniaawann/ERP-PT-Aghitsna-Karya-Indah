<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Penawaran {{ $quotation->quotation_number }}</title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #000;
            padding: 1cm 1cm 0.5cm 1cm;
        }

        /* ── Header (3 Column Layout) ───────────────────────── */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .header-row {
            display: table-row;
        }

        .header-logo {
            display: table-cell;
            width: 25%;
            vertical-align: top;
        }

        .header-logo img {
            width: 90px;
            height: auto;
            display: block;
        }

        .header-title {
            display: table-cell;
            width: 40%;
            vertical-align: top;
            text-align: center;
            padding-top: 10px;
        }

        .title-penawaran {
            font-size: 28px;
            font-weight: bold;
            letter-spacing: 2px;
        }

        .header-docinfo {
            display: table-cell;
            width: 35%;
            vertical-align: top;
            text-align: right;
        }

        .company-info {
            margin-top: 8px;
            font-size: 9px;
            line-height: 1.6;
        }

        .company-name {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .doc-info-table {
            width: auto;
            margin-left: auto;
            border-collapse: collapse;
        }

        .doc-info-table td {
            padding: 2px 5px;
            font-size: 10px;
        }

        .doc-info-table td:first-child {
            white-space: nowrap;
        }

        /* ── Recipient ──────────────────────────────────────── */
        .recipient-section {
            margin: 10px 0 8px 0;
            font-size: 11px;
        }

        .recipient-label {
            margin-bottom: 5px;
        }

        .recipient-name {
            margin-left: 80px;
        }

        .ditempat {
            margin: 8px 0;
            font-size: 11px;
        }

        .opening {
            margin: 8px 0 10px 0;
            font-size: 11px;
        }

        /* ── Items Table ────────────────────────────────────── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 10px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 6px 5px;
        }

        .items-table thead tr {
            background-color: #e8e8e8;
        }

        .items-table thead th {
            font-weight: bold;
            text-align: center;
            font-size: 11px;
        }

        .items-table tbody tr.row-grand-total {
            font-weight: bold;
            font-size: 11px;
        }

        .items-table tbody tr.row-grand-total td {
            padding: 8px 5px;
        }

        .items-table tbody tr.row-grand-total td.empty-cell {
            background-color: transparent;
            border: none;
        }

        .items-table tbody tr.row-grand-total td.yellow-cell {
            background-color: #FFFF00;
        }

        /* Cell alignments */
        .c {
            text-align: center;
        }

        .l {
            text-align: left;
        }

        .r {
            text-align: right;
        }

        /* ── Footer sections ────────────────────────────────── */
        .terbilang {
            margin: 12px 0;
            font-size: 10px;
            font-style: italic;
        }

        .payment-info {
            margin: 12px 0;
            font-size: 10px;
            line-height: 1.8;
        }

        .closing {
            margin: 15px 0 10px 0;
            font-size: 11px;
            line-height: 1.6;
        }

        .signature {
            margin-top: 40px;
            font-size: 11px;
        }

        .signature-line {
            font-weight: bold;
            margin-bottom: 3px;
        }

        .signature-division {
            font-size: 10px;
        }
    </style>
</head>

<body>

    {{-- ═══ HEADER (3 COLUMN LAYOUT) ═════════════════════════════════════════════ --}}
    <div class="header">
        <div class="header-row">
            <div class="header-logo">
                <img src="{{ public_path('images/logo.jpeg') }}" alt="Logo">
            </div>
            <div class="header-title">
                <div class="title-penawaran">PENAWARAN</div>
            </div>
            <div class="header-docinfo">
                <table class="doc-info-table">
                    <tr>
                        <td>No</td>
                        <td>:</td>
                        <td><strong>{{ $quotation->quotation_number }}</strong></td>
                    </tr>
                    <tr>
                        <td>Tanggal</td>
                        <td>:</td>
                        <td>{{ \Carbon\Carbon::parse($quotation->date)->isoFormat('DD MMMM YYYY') }}</td>
                    </tr>
                    <tr>
                        <td>Hal</td>
                        <td>:</td>
                        <td>{{ $quotation->subject }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- ═══ COMPANY INFO ═══════════════════════════════════════════════════════════ --}}
    <div class="company-info">
        <div class="company-name">PT.AGHITSNA KARYA INDAH</div>
        <div>JL. TANAH BARU RAYA PERTIWI RT.01/05</div>
        <div>BEJI, DEPOK, JAWA BARAT</div>
        <div>Telp. 021-29034923 - 0812.9596.552</div>
        <div>Email : Design@aghitsna.id</div>
    </div>

    {{-- ═══ RECIPIENT ══════════════════════════════════════════════════════════════ --}}
    <div class="recipient-section">
        <div class="recipient-label">
            <strong>Kepada Yth :</strong>
        </div>
        <div class="recipient-name">
            {{ $quotation->recipient }}
        </div>
    </div>

    <div class="ditempat">
        <strong>Ditempat</strong>
    </div>

    <div class="opening">
        Dengan ini kami sampaikan Penawaran Harga, sebagai berikut :
    </div>

    {{-- ═══ ITEMS TABLE (FLAT - NO GROUPING) ═══════════════════════════════════════ --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:4%">No</th>
                <th style="width:36%">Keterangan</th>
                <th style="width:9%">Volume</th>
                <th style="width:9%">Satuan</th>
                <th style="width:21%">Harga</th>
                <th style="width:21%">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $idx => $item)
                <tr>
                    <td class="c">{{ $idx + 1 }}.</td>
                    <td class="l">{{ $item->description }}</td>
                    <td class="c">{{ $item->volume ?? '-' }}</td>
                    <td class="c">{{ $item->unit ?? '-' }}</td>
                    <td class="r">Rp &nbsp;{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="r">Rp &nbsp;{{ number_format($item->total_price, 0, ',', '.') }}</td>
                </tr>
            @endforeach

            {{-- Grand Total --}}
            <tr class="row-grand-total">
                <td colspan="3" class="empty-cell"></td>
                <td colspan="2" class="c yellow-cell">Jumlah</td>
                <td class="r yellow-cell">Rp &nbsp;{{ number_format($quotation->total_amount, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ═══ FOOTER ══════════════════════════════════════════════════════════════════ --}}
    <div class="terbilang">
        <em>Terbilang :
            {{ $quotation->amount_in_words ?? ucwords(terbilang($quotation->total_amount)) . ' rupiah' }}</em>
    </div>

    <div class="payment-info">
        Pembayaran dapat di transfer melalui rekening<br><br>
        @foreach ($paymentAccounts as $acc)
            Bank <strong>{{ $acc->bank_name }}</strong> / No : <strong>{{ $acc->account_number }}</strong>
            a/n <strong>{{ $acc->account_holder }}</strong><br>
        @endforeach
    </div>

    <div class="closing">
        Demikian penawaran ini kami sampaikan atas perhatian dan kerjasamanya kami ucapkan terimakasih<br><br>
        Hormat Kami,<br>
        <strong>PT.AGHITSNA KARYA INDAH</strong>
    </div>

    <div class="signature">
        <div class="signature-line">{{ $quotation->signed_by ?? 'Akhmad Khaidir' }}</div>
        <div class="signature-division">{{ $quotation->division ?? 'Divisi Alumunium' }}</div>
    </div>

</body>

</html>
