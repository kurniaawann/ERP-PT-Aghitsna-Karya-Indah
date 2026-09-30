<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Penawaran Proyek</title>
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

        /* Seluruh isi dokumen Times New Roman 12pt (kecuali kop surat yang lebih besar) */
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

        .company-address {
            font-size: 12pt;
            line-height: 1.3;
            padding-top: 8px;
        }

        .invoice-info {
            margin-top: 8px;
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
        /* Jarak ±1 baris kosong: Email → Kepada Yth → "Dengan ini kami sampaikan" */
        .recipient-section {
            margin: 24px 0 0 0;
        }

        .recipient-label {
            margin-bottom: 2px;
        }

        .opening {
            margin: 20px 0 8px 0;
        }

        /* Blok No/Tanggal/Hal rata ke tepi kanan (sejajar tepi kanan tabel item) */
        table.invoice-info {
            margin-left: auto;
        }

        /* ── Free Text (mode Teks/Deskripsi) ─────────────────── */
        .free-text-block {
            margin: 8px 0;
            line-height: 1.5;
            text-align: justify;
            white-space: normal;
        }

        /* ── Items Table ────────────────────────────────────── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0 0 0;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 4px 5px;
            font-size: 12pt;
            vertical-align: middle;
        }

        .items-table thead tr {
            background-color: #e8e8e8;
        }

        .items-table thead th {
            font-weight: bold;
            text-align: center;
        }

        .items-table tbody tr.row-grand-total {
            font-weight: bold;
        }

        /* Menghilangkan border pada cell kosong (No, Keterangan, Volume, Satuan) */
        .items-table td.empty-cell {
            background-color: transparent !important;
            border: none !important;
        }

        /* Sel rata tengah (No, Volume, Satuan, Harga, Jumlah, ringkasan) tidak dipecah baris
           agar "Rp" tidak terpisah dari nominal */
        .items-table td.c {
            white-space: nowrap;
        }

        .items-table td.yellow-cell {
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
            margin: 10px 0 8px 0;
            font-style: italic;
            font-weight: bold;
        }

        .payment-info {
            margin: 8px 0;
            line-height: 1.4;
        }

        .closing {
            margin: 8px 0 0 0;
            line-height: 1.4;
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

    @foreach ($quotationList as $index => $q)
        @if ($index > 0)
            <div class="page-break"></div>
        @endif
        @php
            $items = $q->items ?? [];
            $itemsMode = $q->items_mode ?? 'items';
            // Total akhir = setelah diskon (sama dengan total invoice yang dibuat dari penawaran)
            $grandTotal = $q->getFinalTotal();
            $discountAmount = ($q->discount_type && (float) $q->discount_value > 0) ? $q->getDiscountAmount() : 0;
            $selectedIds = $q->selected_payment_accounts ?? [];
            if (!empty($selectedIds)) {
                $paymentAccounts = \App\Models\Finance\PaymentAccount::whereIn('id', $selectedIds)
                    ->orderBy('id')
                    ->get();
            } else {
                $paymentAccounts = \App\Models\Finance\PaymentAccount::where('is_active', true)->get();
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

    {{-- Baris 2: alamat perusahaan (kiri) + info penawaran (kanan) --}}
    <table class="header-table" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
            <td width="55%" valign="top">
                <div class="company-address">
                    JL. TANAH BARU RAYA PERTIWI RT.01/05<br>
                    BEJI, DEPOK, JAWA BARAT<br>
                    Telp. 021-29034923 - 0812.9596.552<br>
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

    {{-- ═══ ISI PENAWARAN (TABEL ATAU TEKS) ═══════════════════════════════════════ --}}
    @if ($itemsMode === 'text')
        <div class="free-text-block">
            {!! nl2br(e($q->free_text)) !!}
        </div>
    @else
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
            @foreach ($items as $idx => $item)
                <tr>
                    <td class="c">{{ $idx + 1 }}.</td>
                    <td class="l">{{ $item['keterangan'] ?? '-' }}</td>
                    <td class="c">{{ isset($item['volume']) && $item['volume'] !== null && $item['volume'] !== '' ? number_format((float) $item['volume'], 2, ',', '.') : '-' }}</td>
                    <td class="c">{{ $item['satuan'] ?? '-' }}</td>
                    <td class="c">Rp &nbsp;{{ number_format($item['harga'] ?? 0, 0, ',', '.') }}</td>
                    <td class="c">Rp &nbsp;{{ number_format((float) ($item['volume'] ?? 0) * ($item['harga'] ?? 0), 0, ',', '.') }}</td>
                </tr>
            @endforeach

            {{-- Baris Discount (Kolom 1-4 tanpa border/garis) --}}
            @if ($discountAmount > 0)
                <tr>
                    <td colspan="4" class="empty-cell"></td>
                    <td class="c">Discount {{ $q->discount_type === 'percentage' ? '(' . rtrim(rtrim(number_format((float) $q->discount_value, 2, ',', '.'), '0'), ',') . '%)' : '' }}</td>
                    <td class="c">Rp &nbsp;-{{ number_format($discountAmount, 0, ',', '.') }}</td>
                </tr>
            @endif

            {{-- Grand Total --}}
            <tr class="row-grand-total">
                <td colspan="4" class="empty-cell"></td>
                <td class="c yellow-cell">Total</td>
                <td class="c yellow-cell">Rp &nbsp;{{ number_format($grandTotal, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ═══ TERBILANG ══════════════════════════════════════════════════════════════════ --}}
    <div class="terbilang">
        Terbilang : {{ ucwords(terbilang($grandTotal)) . ' rupiah' }}
    </div>
    @endif

    {{-- ═══ FOOTER ══════════════════════════════════════════════════════════════════ --}}
    <div class="payment-info">
        Pembayaran dapat di transfer melalui rekening<br>
        @foreach ($paymentAccounts as $acc)
            Bank {{ $acc->bank_name }} / No : {{ $acc->account_number }} a/n {{ $acc->account_holder }}<br>
        @endforeach
    </div>

    <div class="closing">
        Demikian penawaran ini kami sampaikan atas perhatian dan kerjasamanya kami ucapkan terimakasih<br>
        Hormat Kami,<br>
        PT. AGHITSNA KARYA INDAH
    </div>

    <div class="signature" style="margin-top: {{ $q->signedBy?->signature_image ? '5px' : '60px' }};">
        @if ($q->signedBy?->signature_image)
            <img src="{{ storage_path('app/public/' . $q->signedBy->signature_image) }}" alt="Tanda Tangan"
                style="max-height: 55px; max-width: 160px;">
        @endif
        @if ($q->signedBy)
            <div class="signature-line">{{ $q->signedBy->name }}</div>
        @endif
        @if ($q->division)
            <div class="signature-division">{{ $q->division->name }}</div>
        @endif
    </div>
    @endforeach

</body>

</html>