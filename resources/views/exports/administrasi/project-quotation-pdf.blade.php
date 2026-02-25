<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Penawaran {{ $quotation->quotation_number }}</title>
    <style>
        @page {
            size: A4;
            margin: 0.4cm 0.4cm 0.4cm 0.4cm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #000;
            padding: 15px;
        }

        /* ── Header ─────────────────────────────────────────── */
        .header {
            display: table;
            width: 100%;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .header-left {
            display: table-cell;
            width: 55%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 45%;
            vertical-align: top;
            text-align: right;
        }

        .logo-wrap {
            display: table;
        }

        .logo-wrap .logo-img {
            display: table-cell;
            vertical-align: top;
        }

        .logo-wrap .logo-img img {
            width: 65px;
            height: auto;
        }

        .logo-wrap .company-info {
            display: table-cell;
            vertical-align: middle;
            padding-left: 8px;
        }

        .company-name {
            font-size: 11px;
            font-weight: bold;
            color: #CC5500;
        }

        .company-addr {
            font-size: 9px;
            line-height: 1.5;
        }

        .title-penawaran {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .doc-info-table {
            width: auto;
            margin-left: auto;
            border-collapse: collapse;
        }

        .doc-info-table td {
            padding: 1px 3px;
            font-size: 10px;
        }

        .doc-info-table td:first-child {
            white-space: nowrap;
        }

        /* ── Recipient ──────────────────────────────────────── */
        .recipient-section {
            margin: 8px 0;
            font-size: 10px;
        }

        .recipient-inline {
            margin-bottom: 3px;
        }

        .recipient-name {
            margin-left: 30px;
        }

        .opening {
            margin: 10px 0;
            font-size: 10px;
        }

        /* ── Items Table ────────────────────────────────────── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 9px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 5px 4px;
        }

        .items-table thead tr {
            background-color: #e0e0e0;
        }

        .items-table thead th {
            font-weight: bold;
            text-align: center;
            font-size: 9.5px;
        }

        .items-table tbody tr.row-grand-total {
            background-color: #ffff00;
            font-weight: bold;
            font-size: 10px;
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
            margin: 10px 0;
            font-size: 9px;
        }

        .payment-info {
            margin: 10px 0;
            font-size: 9px;
            line-height: 1.6;
        }

        .closing {
            margin: 10px 0;
            font-size: 10px;
            line-height: 1.5;
        }

        .signature {
            margin-top: 20px;
            font-size: 10px;
        }

        .signature-line {
            font-weight: bold;
            margin-bottom: 2px;
        }

        .signature-division {
            font-size: 9px;
        }
    </style>
</head>

<body>

    {{-- ═══ HEADER ═══════════════════════════════════════════════════════════════ --}}
    <div class="header">
        <div class="header-left">
            <div class="logo-wrap">
                <div class="logo-img">
                    <img src="{{ public_path('images/logo.jpeg') }}" alt="Logo">
                </div>
                <div class="company-info">
                    <div class="company-name">PT.AGHITSNA KARYA INDAH</div>
                    <div class="company-addr">
                        JL. TANAH BARU RAYA PERTIWI RT.01/05<br>
                        BEJI, DEPOK, JAWA BARAT<br>
                        Telp. 021-29034923 - 0812,9596,552<br>
                        Email : Design@aghitsna.id
                    </div>
                </div>
            </div>
        </div>
        <div class="header-right">
            <div class="title-penawaran">PENAWARAN</div>
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

    {{-- ═══ RECIPIENT ══════════════════════════════════════════════════════════════ --}}
    <div class="recipient-section">
        <div class="recipient-inline">
            <strong>Kepada Yth :</strong>
            <span class="recipient-name">{{ $quotation->recipient }}</span>
        </div>
        <div>
            <strong>{{ $quotation->recipient_address }}</strong>
        </div>
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
                <td colspan="5" class="c">Total</td>
                <td class="r">Rp &nbsp;{{ number_format($quotation->total_amount, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ═══ FOOTER ══════════════════════════════════════════════════════════════════ --}}
    <div class="terbilang">
        <em>Terbilang :
            {{ $quotation->amount_in_words ?? ucwords(terbilang($quotation->total_amount)) . ' rupiah' }}</em>
    </div>

    <div class="payment-info">
        Pembayaran dapat di transfer melalui rekening<br>
        @foreach ($paymentAccounts as $acc)
            Bank <strong>{{ $acc->bank_name }}</strong> / No : <strong>{{ $acc->account_number }}</strong>
            a/n <strong>{{ $acc->account_holder }}</strong><br>
        @endforeach
    </div>

    <div class="closing">
        Demikian penawaran ini kami sampaikan atas perhatian dan kerjasamanya kami ucapkan terimakasih<br>
        Hormat Kami,<br>
        PT.AGHITSNA KARYA INDAH
    </div>

    <div class="signature">
        <div class="signature-line">{{ $quotation->signed_by ?? 'Akhmad Khaidir' }}</div>
        <div class="signature-division">{{ $quotation->division ?? 'Divisi Alumunium' }}</div>
    </div>

</body>

</html>
