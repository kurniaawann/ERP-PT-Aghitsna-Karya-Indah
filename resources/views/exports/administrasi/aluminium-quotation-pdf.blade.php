<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Penawaran Alumunium</title>
    <style>
        /* @page {
            size: A4;
            margin: 1.5cm 1.5cm 1.5cm 1.5cm;
        } */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10px;
            line-height: 1.4;
            color: #000;
            padding: 15mm 15mm 15mm 15mm;
        }

        /* ── Header (Table Layout) ──────────────────────────── */
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            border: none;
            padding: 0;
        }

        .logo-cell {
            width: 100px;
            vertical-align: top;
            padding-right: 10px;
        }

        .logo-cell img {
            display: block;
            width: 80px;
            height: 60px;
            object-fit: contain;
        }

        .invoice-title {
            font-size: 24px;
            font-weight: bold;
        }

        .company-address {
            font-size: 10px;
            line-height: 1.8;
        }

        .invoice-info {
            font-size: 11px;
            line-height: 1.8;
        }

        .invoice-info table {
            margin-left: 0;
        }

        .invoice-info td {
            padding: 2px 0;
        }

        .invoice-info td:first-child {
            width: 65px;
        }

        .invoice-info td:nth-child(2) {
            width: 10px;
        }

        /* ── Recipient ──────────────────────────────────────── */
        .recipient-section {
            margin: 10px 0 8px 0;
            font-size: 10px;
        }

        .recipient-label {
            margin-bottom: 5px;
        }

        .recipient-name {
            margin-left: 80px;
        }

        .recipient-table {
            width: 100%;
            border-collapse: collapse;
        }

        .recipient-table td {
            border: none;
            padding: 2px 0;
            vertical-align: top;
        }

        .recipient-table .recipient-label-cell {
            font-weight: bold;
            width: 80px;
            white-space: nowrap;
        }

        .recipient-table .recipient-name-cell {
            padding-left: 5px;
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

        /* Grand total row */
        .row-grand-total td {
            font-weight: bold;
            font-size: 11px;
        }

        .row-grand-total td.yellow-cell {
            background-color: #FFFF00;
        }

        .row-grand-total td.empty-cell {
            background-color: transparent;
            border: none;
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

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    @php
        if (isset($quotations)) {
            $quotationList = $quotations;
        } else {
            $quotationList = collect([$quotation]);
        }
    @endphp

    @foreach ($quotationList as $loop_idx => $q)
        @if ($loop_idx > 0)
            <div class="page-break"></div>
        @endif
        @php
            $items = $q->items ?? [];
            $grandTotal = $q->total_amount;
            $discountAmount = ($q->discount_type && (float) $q->discount_value > 0) ? $q->getDiscountAmount() : 0;
            $selectedIds = $q->selected_payment_accounts ?? [];
            if (!empty($selectedIds)) {
                $payAccounts = \App\Models\Finance\PaymentAccount::whereIn('id', $selectedIds)->orderBy('id')->get();
            } else {
                $payAccounts = \App\Models\Finance\PaymentAccount::active()->get();
            }
        @endphp

    {{-- ═══ HEADER (TABLE LAYOUT) ══════════════════════════════════════════════════ --}}
    <table class="header-table" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
            <td width="45%" valign="top" style="padding-bottom: 15px;">
                <div class="logo-cell">
                    <img src="{{ public_path('images/logo.jpeg') }}" alt="Logo" width="80" height="80">
                </div>
            </td>
            <td width="20%" valign="middle" style="text-align: center; padding-bottom: 15px;">
                <div class="invoice-title" style="font-weight: bold; font-size: 16px; letter-spacing: 1px;">
                    PENAWARAN ALUMUNIUM
                </div>
            </td>
            <td width="35%" valign="top" style="padding-bottom: 15px;"></td>
        </tr>
        <tr>
            <td valign="top">
                <div class="company-address" style="font-size: 12px; line-height: 1.4;">
                    <strong>AGHITSNA ALUMUNIUM DAN BAJA RINGAN</strong><br>
                    JL. TANAH BARU RAYA PERTIWI RT.01/05<br>
                    BEJI, DEPOK, JAWA BARAT<br>
                    Telp. 021-29034923 - 0812.9596.552<br>
                    Email : Design@aghitsna.id
                </div>
            </td>
            <td valign="top"></td>
            <td valign="top">
                <div class="invoice-info" style="font-size: 12px;">
                    <table cellpadding="0" cellspacing="0" border="0" align="right">
                        <tr>
                            <td style="padding-right: 5px;" valign="top">No</td>
                            <td style="padding-right: 5px;" valign="top">:</td>
                            <td valign="top">{{ $q->quotation_number }}</td>
                        </tr>
                        <tr>
                            <td style="padding-right: 5px;" valign="top">Tanggal</td>
                            <td style="padding-right: 5px;" valign="top">:</td>
                            <td valign="top">{{ \Carbon\Carbon::parse($q->date)->isoFormat('DD MMMM YYYY') }}</td>
                        </tr>
                        <tr>
                            <td style="padding-right: 5px;" valign="top">Hal</td>
                            <td style="padding-right: 5px;" valign="top">:</td>
                            <td valign="top">{{ $q->subject }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    {{-- ═══ RECIPIENT ══════════════════════════════════════════════════════════════ --}}
    <div class="recipient-section">
        <div class="recipient-label"><strong>Kepada Yth :</strong></div>
        <div>
            <div>{{ $q->recipient }}</div>
            @if (!empty($q->proyek))
                <div>{{ $q->proyek }}</div>
            @endif
        </div>
    </div>

    <div class="opening">
        @if ($q->project_description)
            Dengan ini kami sampaikan penawaran untuk proyek {{ $q->project_description }} sebagai berikut :
        @else
            Dengan ini kami sampaikan penawaran sebagai berikut :
        @endif
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
            @foreach ($items as $index => $item)
                <tr>
                    <td class="c">{{ $index + 1 }}.</td>
                    <td class="l">{{ $item['keterangan'] ?? '-' }}</td>
                    <td class="c">{{ isset($item['volume']) && $item['volume'] !== null && $item['volume'] !== '' ? number_format((float) $item['volume'], 2, ',', '.') : '-' }}</td>
                    <td class="c">{{ $item['satuan'] ?? '-' }}</td>
                    <td class="r">Rp &nbsp;{{ number_format($item['harga'] ?? 0, 0, ',', '.') }}</td>
                    <td class="r">Rp &nbsp;{{ number_format((float) ($item['volume'] ?? 0) * ($item['harga'] ?? 0), 0, ',', '.') }}</td>
                </tr>
            @endforeach

            @if ($discountAmount > 0)
                <tr>
                    <td colspan="4" class="empty-cell"></td>
                    <td class="c">Discount
                        {{ $q->discount_type === 'percentage' ? '(' . rtrim(rtrim(number_format((float) $q->discount_value, 2, ',', '.'), '0'), ',') . '%)' : '' }}</td>
                    <td class="r">Rp &nbsp;-{{ number_format($discountAmount, 0, ',', '.') }}</td>
                </tr>
            @endif

            {{-- Grand Total --}}
            <tr class="row-grand-total">
                <td colspan="4" class="empty-cell"></td>
                <td class="c yellow-cell">Total</td>
                <td class="r yellow-cell">Rp &nbsp;{{ number_format($grandTotal, 0, ',', '.') }}</td>
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

    <div class="signature" style="margin-top: {{ $q->signedBy?->signature_image ? '5px' : '15px' }};">
        @if ($q->signedBy?->signature_image)
            <img src="{{ storage_path('app/public/' . $q->signedBy->signature_image) }}" alt="Tanda Tangan"
                style="max-height: 55px; max-width: 160px;">
        @endif
        @if ($q->signedBy)
            <div class="signature-line" style="margin-top: {{ $q->signedBy->signature_image ? '5px' : '50px' }};">{{ $q->signedBy->name }}</div>
        @endif
        @if ($q->division)
            <div class="signature-division">{{ $q->division->name }}</div>
        @endif
    </div>
    @endforeach

</body>

</html>
