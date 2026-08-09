<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Penawaran Harga Pembangunan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size:12px;
            line-height: 1.5;
            color: #000;
            padding: 15mm;
        }

        /* ── Kop Surat ────────────────────────────────────────── */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        .header-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }

        .logo-cell {
            width: 90px;
        }

        .logo-cell img {
            display: block;
            width: 80px;
            height: auto;
            object-fit: contain;
        }

        .company-title {
            color: #e53935;
            font-weight: bold;
            font-size: 18px;
            letter-spacing: 0.5px;
            font-family: Arial, sans-serif;
            margin-bottom: 3px;
        }

        .company-address {
            color: #1565c0;
            font-size: 9.5px;
            font-weight: bold;
            line-height: 1.3;
            font-family: Arial, sans-serif;
        }

        .header-divider {
            border-bottom: 3px solid #1565c0;
            margin-bottom: 15px;
        }

        /* ── Document Title ──────────────────────────────────── */
        .doc-title {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            text-decoration: underline;
            margin-bottom: 15px;
            letter-spacing: 0.5px;
        }

        /* ── Document Meta Info ──────────────────────────────── */
       .meta-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10px; /* Jarak ke teks di bawahnya (Kepada Yth) */
    font-size:12px;
    line-height: 1.1;    /* Dipersingkat/dirapatkan (default body 1.5) */
}

.meta-table td {
    padding: 0;          /* Mengeliminasi jarak padding atas-bawah sel */
    vertical-align: top;
}

        .meta-table td.label {
            width: 75px;
        }

        .meta-table td.colon {
            width: 15px;
            text-align: center;
        }

        /* ── Recipient & Opening ─────────────────────────────── */
        .recipient-block {
            margin-bottom: 12px;
            font-size:12px;
            line-height: 1.4;
        }

        .opening-text {
            margin-bottom: 12px;
            font-size:12px;
            text-align: justify;
            line-height: 1.5;
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
            padding: 5px 5px;
        }

        .items-table thead tr {
            background-color: #e8e8e8;
        }

        .items-table thead th {
            font-weight: bold;
            text-align: center;
            font-size: 10.5px;
        }

        .items-table tbody tr.row-grand-total {
            font-weight: bold;
            font-size: 10.5px;
        }

        .items-table tbody tr.row-grand-total td {
            padding: 6px 5px;
        }

        /* Menghilangkan garis/border pada area kosong Discount & Total */
        .items-table tbody tr td.empty-cell {
            background-color: transparent !important;
            border: none !important;
        }

       

        /* Alignment Helpers */
        .c { text-align: center; }
        .l { text-align: left; }
        .r { text-align: right; }

        /* ── Footer Info ────────────────────────────────────── */
        .terbilang {
            margin: 8px 0;
            font-size: 10px;
            font-style: italic;
        }

        .payment-info {
            margin: 10px 0;
            font-size: 10px;
            line-height: 1.5;
        }

        .closing-text {
            margin: 12px 0;
            font-size:12px;
            text-align: justify;
            line-height: 1.5;
        }

        /* ── Signature Section ──────────────────────────────── */
        .signature-container {
            width: 100%;
            margin-top: 15px;
        }

        .signature-box {
            float: left;
            width: 230px;
            font-size:12px;
            line-height: 1.4;
        }

        .signature-img-wrapper {
            height: 55px;
            margin: 5px 0;
        }

        .signature-img-wrapper img {
            max-height: 55px;
            max-width: 160px;
            object-fit: contain;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .page-break {
            page-break-after: always;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
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
            $discountAmount = ($q->discount_type && (float) $q->discount_value > 0) ? (int) $q->getDiscountAmount() : 0;
            $grandTotal = (int) ($q->total_amount ?? 0) - $discountAmount;
        @endphp

        {{-- ═══ KOP SURAT ═════════════════════════════════════════════════════════════ --}}
        <table class="header-table">
            <tr>
                <td>
                    <div class="logo-cell">
                        <img src="{{ public_path('images/logo.jpeg') }}" alt="Logo" width="80" height="auto">
                    </div>
                </td>
                <td style="padding-left: 10px;">
                    <div class="company-title">PT. AGHITSNA KARYA INDAH</div>
                    <div class="company-address">
                        JL. PERTIWI NO.36 TANAH BARU RAYA RT.01/05, BEJI. DEPOK, JAWA BARAT<br>
                        Telp. 021-29034923 – 0812.9596.552, Email : design@aghitsna.id / Zulkarnainmarzuki@yahoo.com
                    </div>
                </td>
            </tr>
        </table>
        <div class="header-divider"></div>

        {{-- ═══ JUDUL SURAT ══════════════════════════════════════════════════════════ --}}
        <div class="doc-title">SURAT PENAWARAN HARGA PEMBANGUNAN</div>

        {{-- ═══ METADATA SURAT ═══════════════════════════════════════════════════════ --}}
        <table class="meta-table">
            <tr>
                <td class="label">Nomor</td>
                <td class="colon">:</td>
                <td>{{ $q->quotation_number }}</td>
            </tr>
            <tr>
                <td class="label">Lampiran</td>
                <td class="colon">:</td>
                <td>{{ $q->attachment ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Perihal</td>
                <td class="colon">:</td>
                <td>{{ $q->subject }}</td>
            </tr>
        </table>

        {{-- ═══ PENERIMA SURAT ═══════════════════════════════════════════════════════ --}}
        <div class="recipient-block">
            Kepada Yth,<br>
            Bapak {{ $q->recipient }}<br>
            Di Tempat
        </div>

        {{-- ═══ PARAGRAF PEMBUKA ══════════════════════════════════════════════════════ --}}
        <div class="opening-text">
            Dengan Hormat,<br>
            Sehubungan dengan rencana pembangunan bangunan <strong>{{ $q->project_description }}</strong> yang berlokasi di jalan <strong>{{ $q->location ?? '-' }}</strong>, bersama ini kami sampaikan penawaran harga pelaksanaan pekerjaan pembangunan dengan rincian sebagai berikut :
        </div>

        {{-- ═══ TABEL ITEMS ══════════════════════════════════════════════════════════ --}}
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
                        <td class="l">{{ $item['keterangan'] ?? '-' }}</td>
                        <td class="c">{{ isset($item['volume']) && $item['volume'] !== null && $item['volume'] !== '' ? number_format((float) $item['volume'], 2, ',', '.') : '-' }}</td>
                        <td class="c">{{ $item['satuan'] ?? '-' }}</td>
                        <td class="c">Rp &nbsp;{{ number_format($item['harga'] ?? 0, 0, ',', '.') }}</td>
                        <td class="r">Rp &nbsp;{{ number_format((float) ($item['volume'] ?? 0) * ($item['harga'] ?? 0), 0, ',', '.') }}</td>
                    </tr>
                @endforeach

                {{-- Baris Discount --}}
                @if ($discountAmount > 0)
                    <tr>
                        <td colspan="4" class="empty-cell"></td>
                        <td class="c">Discount {{ $q->discount_type === 'percentage' ? '(' . rtrim(rtrim(number_format((float) $q->discount_value, 2, ',', '.'), '0'), ',') . '%)' : '' }}</td>
                        <td class="r">Rp &nbsp;-{{ number_format($discountAmount, 0, ',', '.') }}</td>
                    </tr>
                @endif

                {{-- Baris Total --}}
                <tr class="row-grand-total">
                    <td colspan="4" class="empty-cell"></td>
                    <td class="c yellow-cell">Total</td>
                    <td class="r yellow-cell">Rp &nbsp;{{ number_format($grandTotal, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        {{-- ═══ TERBILANG ═══════════════════════════════════════════════════════════ --}}
        <div class="terbilang">
            <em>Terbilang : {{ ucwords(terbilang($grandTotal)) . ' rupiah' }}</em>
        </div>

        {{-- ═══ PARAGRAF PENUTUP ═════════════════════════════════════════════════════ --}}
        <div class="closing-text">
            Demikian surat penawaran ini kami sampaikan. Besar harapan kami untuk dapat bekerja sama dalam pelaksanaan pembangunan tersebut. Atas perhatian dan kepercayaan Bapak, kami ucapkan terima kasih.
        </div>

        {{-- ═══ TANDA TANGAN ═════════════════════════════════════════════════════════ --}}
        <div class="signature-container clearfix">
            <div class="signature-box">
                <div>Jakarta, {{ \Carbon\Carbon::parse($q->date)->isoFormat('D MMMM YYYY') }}</div>
                <div>Hormat Kami,</div>
                <div>{{ $q->division?->name ?? 'Pelaksana Pekerjaan' }}</div>
                <div class="signature-img-wrapper">
                    @if ($q->signedBy?->signature_image)
                        <img src="{{ storage_path('app/public/' . $q->signedBy->signature_image) }}" alt="Tanda Tangan">
                    @endif
                </div>
                <div class="signature-name">{{ $q->signedBy?->name ?? 'Zulkarnain' }}</div>
            </div>
        </div>
    @endforeach

</body>

</html>