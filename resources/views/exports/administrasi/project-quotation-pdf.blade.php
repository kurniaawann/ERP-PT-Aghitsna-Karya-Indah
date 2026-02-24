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
            margin: 10px 0 8px 0;
            font-size: 10px;
        }

        .recipient-inline {
            margin-bottom: 3px;
        }

        .recipient-inline strong {
            display: inline;
        }

        .recipient-name {
            display: inline;
            margin-left: 5px;
        }

        /* ── Opening text ───────────────────────────────────── */
        .opening {
            font-size: 10px;
            margin-bottom: 8px;
            margin-top: 8px;
        }

        /* ── Items Table ────────────────────────────────────── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 4px 5px;
            font-size: 9.5px;
        }

        .items-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        .items-table td.c {
            text-align: center;
        }

        .items-table td.r {
            text-align: right;
        }

        .items-table td.l {
            text-align: left;
        }

        /* Group header row */
        .row-group-header td {
            font-weight: bold;
            background-color: #fff;
        }

        /* Subtotal row */
        .row-subtotal td {
            font-weight: bold;
            background-color: #FFFF99;
        }

        /* Grand total row */
        .row-grand-total td {
            font-weight: bold;
            background-color: #FFFF00;
            font-size: 11px;
        }

        /* ── Footer ─────────────────────────────────────────── */
        .terbilang {
            font-size: 9.5px;
            font-style: italic;
            margin: 10px 0 8px 0;
        }

        .payment-info {
            font-size: 9.5px;
            margin: 10px 0 8px 0;
            line-height: 1.7;
        }

        .closing {
            font-size: 9.5px;
            margin: 10px 0 8px 0;
        }

        .signature {
            margin-top: 15px;
            font-size: 10px;
        }

        .signature-line {
            margin-top: 50px;
            font-weight: bold;
        }

        .signature-division {
            font-size: 9.5px;
            margin-top: 2px;
        }
    </style>
</head>

<body>
    @php
        $q = $quotation;
        $groups = $q->groups;
        $grandTotal = $q->total_amount;

        // Resolve payment accounts
        $selectedIds = $q->selected_payment_accounts ?? [];
        if (!empty($selectedIds)) {
            $payAccounts = \App\Models\Finance\PaymentAccount::whereIn('id', $selectedIds)->orderBy('id')->get();
        } else {
            $payAccounts = \App\Models\Finance\PaymentAccount::active()->get();
        }
    @endphp

    {{-- ═══ HEADER ════════════════════════════════════════════════════════════════ --}}
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
                    <td><strong>{{ $q->quotation_number }}</strong></td>
                </tr>
                <tr>
                    <td>Tanggal</td>
                    <td>:</td>
                    <td>{{ \Carbon\Carbon::parse($q->date)->isoFormat('DD MMMM YYYY') }}</td>
                </tr>
                <tr>
                    <td>Hal</td>
                    <td>:</td>
                    <td>{{ $q->subject }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ═══ RECIPIENT ══════════════════════════════════════════════════════════════ --}}
    <div class="recipient-section">
        <div class="recipient-inline">
            <strong>Kepada Yth :</strong>
            <span class="recipient-name">{{ $q->recipient }}</span>
        </div>
        <div>
            <strong>{{ $q->recipient_address }}</strong>
        </div>
    </div>

    <div class="opening">
        Dengan ini kami sampaikan Penawaran Harga, sebagai berikut :
    </div>

    {{-- ═══ ITEMS TABLE ═════════════════════════════════════════════════════════════ --}}
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
            @foreach ($groups as $groupIndex => $group)
                {{-- Group header row --}}
                <tr class="row-group-header">
                    <td class="c">{{ $groupIndex + 1 }}.</td>
                    <td colspan="5" class="l">{{ $group->name }}</td>
                </tr>

                {{-- Item rows --}}
                @foreach ($group->items as $item)
                    <tr>
                        <td class="c"></td>
                        <td class="l">&nbsp;&nbsp;&nbsp;{{ $item->description }}</td>
                        <td class="c">{{ $item->volume ?? '-' }}</td>
                        <td class="c">{{ $item->unit ?? '-' }}</td>
                        <td class="r">Rp &nbsp;{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                        <td class="r">Rp &nbsp;{{ number_format($item->total_price, 0, ',', '.') }}</td>
                    </tr>
                @endforeach

                {{-- Subtotal row --}}
                <tr class="row-subtotal">
                    <td colspan="5" class="c">Jumlah</td>
                    <td class="r">Rp &nbsp;{{ number_format($group->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach

            {{-- Grand Total --}}
            <tr class="row-grand-total">
                <td colspan="5" class="c">Total</td>
                <td class="r">Rp &nbsp;{{ number_format($grandTotal, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ═══ FOOTER ══════════════════════════════════════════════════════════════════ --}}
    <div class="terbilang">
        <em>Terbilang : {{ $q->amount_in_words ?? ucwords(terbilang($grandTotal)) . ' rupiah' }}</em>
    </div>

    <div class="payment-info">
        Pembayaran dapat di transfer melalui rekening<br>
        @foreach ($payAccounts as $acc)
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
        <div class="signature-line">{{ $q->signed_by ?? 'Akhmad Khaidir' }}</div>
        <div class="signature-division">Divisi Alumunium</div>
    </div>

</body>

</html>
