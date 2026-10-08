<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Penawaran Alumunium</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Margin halaman A4. Di dompdf, @page diterapkan ke elemen root (html) sehingga ikut
           tertimpa reset "*" di atas — karena itu margin halaman ditegaskan langsung di html
           (berlaku di setiap halaman, termasuk cetak multi penawaran). */
        html {
            margin: 12mm 15mm;
        }

        /* Seluruh isi dokumen Times New Roman 12pt (kop surat: nama usaha 12pt, alamat 11pt) */
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.3;
            color: #000;
        }

        /* ── Kop Surat ──────────────────────────────────────── */
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            border: none;
            padding: 0;
        }

        .logo-cell img {
            display: block;
            width: 115px;
            height: auto;
        }

        .doc-title {
            font-size: 20pt;
            font-weight: bold;
            letter-spacing: 2px;
            text-align: center;
        }

        /* Kop surat (revisi klien): nama usaha 12pt, alamat/telp/email 11pt */
        .company-name {
            font-size: 12pt;
            font-weight: bold;
            padding-top: 6px !important;
            padding-bottom: 2px !important;
        }

        .company-address {
            font-size: 11pt;
            line-height: 1.3;
        }

        .invoice-info td {
            padding: 0 0 1px 0;
            vertical-align: top;
        }

        .invoice-info td.label {
            width: 70px;
        }

        .invoice-info td.colon {
            width: 12px;
        }

        /* ── Recipient ──────────────────────────────────────── */
        /* Jarak tepat 1 baris kosong (1 baris 12pt x 1.3 = 15.6pt): Email → Kepada Yth → "Dengan ini kami sampaikan" */
        .recipient-section {
            margin: 15.6pt 0 0 0;
        }

        .recipient-label {
            margin-bottom: 2px;
        }

        /* ── Opening text ───────────────────────────────────── */
        .opening {
            margin: 15.6pt 0 8px 0;
        }

        /* Blok No/Tanggal/Hal rata ke tepi kanan (sejajar tepi kanan tabel item) */
        table.invoice-info {
            margin-left: auto;
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
            font-size: 12pt;
            vertical-align: middle;
        }

        .items-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        /* Sel rata tengah (No, Volume, Satuan, Harga, Jumlah, ringkasan) tidak dipecah baris
           agar "Rp" tidak terpisah dari nominal */
        .items-table td.c {
            text-align: center;
            white-space: nowrap;
        }

        .items-table td.r {
            text-align: right;
        }

        .items-table td.l {
            text-align: left;
        }

        /* Grand total row */
        .row-grand-total td {
            font-weight: bold;
        }

        .row-grand-total td.yellow-cell {
            background-color: #FFFF00;
        }

        /* Sel kosong di baris Discount & Total (tanpa garis) */
        .items-table td.empty-cell {
            background-color: transparent;
            border: none;
        }

        /* ── Footer ─────────────────────────────────────────── */
        .terbilang {
            font-style: italic;
            font-weight: bold;
            margin: 10px 0 8px 0;
        }

        .payment-info {
            margin: 8px 0;
            line-height: 1.4;
        }

        .closing {
            margin: 8px 0 0 0;
            line-height: 1.4;
        }

        .signature {
            margin-top: 15px;
        }

        .signature-line {
            margin-top: 50px;
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
            // Total akhir = setelah diskon (sama dengan total invoice yang dibuat dari penawaran)
            $grandTotal = $q->getFinalTotal();
            $discountAmount = ($q->discount_type && (float) $q->discount_value > 0) ? $q->getDiscountAmount() : 0;
            $selectedIds = $q->selected_payment_accounts ?? [];
            if (!empty($selectedIds)) {
                $payAccounts = \App\Models\Finance\PaymentAccount::whereIn('id', $selectedIds)->orderBy('id')->get();
            } else {
                $payAccounts = \App\Models\Finance\PaymentAccount::active()->get();
            }
        @endphp

    {{-- ═══ KOP SURAT ═══════════════════════════════════════════════════════════════ --}}
    {{-- Baris 1: logo (kiri) + judul dokumen (tengah halaman) --}}
    <table class="header-table" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
            <td width="30%" valign="middle" class="logo-cell">
                <img src="{{ public_path('images/logo.jpeg') }}" alt="Logo" width="115">
            </td>
            <td width="40%" valign="middle">
                <div class="doc-title">PENAWARAN</div>
            </td>
            <td width="30%"></td>
        </tr>
    </table>

    {{-- Baris 2: nama & alamat perusahaan (kiri) + info penawaran (kanan) --}}
    <table class="header-table" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
            <td colspan="2" class="company-name">AGHITSNA ALUMUNIUM DAN BAJA RINGAN</td>
        </tr>
        <tr>
            <td width="55%" valign="top">
                <div class="company-address">
                    JL. CEMARA RT 02 RW 07, KEL. GROGOL,<br>
                    KEC. LIMO, KOTA DEPOK<br>
                    Telp : 0838 9004 1408 / 0818 0844 4519<br>
                    Email : Design@aghitsna.id
                </div>
            </td>
            <td width="45%" valign="top">
                <table class="invoice-info" cellpadding="0" cellspacing="0" border="0" align="right">
                    <tr>
                        <td class="label">No</td>
                        <td class="colon">:</td>
                        <td>{{ $q->quotation_number }}</td>
                    </tr>
                    <tr>
                        <td class="label">Tanggal</td>
                        <td class="colon">:</td>
                        <td>{{ \Carbon\Carbon::parse($q->date)->isoFormat('DD MMMM YYYY') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Hal</td>
                        <td class="colon">:</td>
                        <td>{{ $q->subject }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ═══ RECIPIENT ══════════════════════════════════════════════════════════════ --}}
    <div class="recipient-section">
        <div class="recipient-label">Kepada Yth :</div>
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
                <th style="width:5%">No</th>
                <th style="width:34%">Keterangan</th>
                <th style="width:10%">Volume</th>
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
                    <td class="c">{{ format_rupiah($item['harga'] ?? 0) }}</td>
                    <td class="c">{{ format_rupiah((float) ($item['volume'] ?? 0) * ($item['harga'] ?? 0)) }}</td>
                </tr>
            @endforeach

            @if ($discountAmount > 0)
                <tr>
                    <td colspan="4" class="empty-cell"></td>
                    <td class="c">Discount
                        {{ $q->discount_type === 'percentage' ? '(' . format_persen($q->discount_value) . '%)' : '' }}</td>
                    <td class="c">{{ format_rupiah(-$discountAmount) }}</td>
                </tr>
            @endif

            {{-- Grand Total --}}
            <tr class="row-grand-total">
                <td colspan="4" class="empty-cell"></td>
                <td class="c yellow-cell">Total</td>
                <td class="c yellow-cell">{{ format_rupiah($grandTotal) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ═══ FOOTER ══════════════════════════════════════════════════════════════════ --}}
    <div class="terbilang">
        Terbilang : {{ ucwords(terbilang($grandTotal)) . ' rupiah' }}
    </div>

    <div class="payment-info">
        Pembayaran dapat di transfer melalui rekening<br>
        @foreach ($payAccounts as $acc)
            Bank {{ $acc->bank_name }} / No : {{ $acc->account_number }} a/n {{ $acc->account_holder }}<br>
        @endforeach
    </div>

    <div class="closing">
        Demikian penawaran ini kami sampaikan atas perhatian dan kerjasamanya kami ucapkan terimakasih<br>
        Hormat Kami,<br>
        PT. AGHITSNA KARYA INDAH
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
