<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Invoice - {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Font dokumen: Times New Roman 12pt (termasuk isi tabel item) */
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.25;
            color: #000;
            padding: 10mm 15mm;
        }

        /* ── Kop Surat (lebih besar dari isi dokumen) ────────── */
        .header-top {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        .header-top td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }

        .logo-cell {
            width: 30%;
            text-align: left;
        }

        .logo-cell img {
            display: block;
            width: 118px;
            height: 70px;
        }

        .title-cell {
            width: 40%;
            text-align: center;
            font-size: 20pt;
            font-weight: bold;
            letter-spacing: 2px;
        }

        .dummy-cell {
            width: 30%;
        }

        .header-divider {
            border-bottom: 3px solid #000;
            margin-bottom: 8px;
        }

        /* ── Info Perusahaan & Meta Surat ────────────────────── */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }

        .info-table td {
            vertical-align: top;
            padding: 0;
        }

        /* Kop surat (revisi klien): nama perusahaan 12pt, alamat/telp/email 11pt */
        .company-info {
            width: 60%;
            font-size: 11pt;
            line-height: 1.3;
        }

        .company-name {
            font-size: 12pt;
            font-weight: bold;
            line-height: 1.25;
            margin-bottom: 2px;
        }

        .meta-info {
            width: 40%;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 1px 0;
            vertical-align: top;
        }

        .meta-table td.label {
            width: 70px;
            white-space: nowrap;
        }

        .meta-table td.colon {
            width: 15px;
            text-align: center;
        }

        /* ── Recipient & Opening ─────────────────────────────── */
        /* Kepada Yth tidak di-bold. Tepat 1 baris kosong (= 1 baris teks 12pt x 1.25 = 15pt)
           sebelum "Kepada Yth :" dan sebelum "Dengan Hormat," (revisi klien) */
        .recipient-block {
            margin-top: 15pt;
        }

        .opening-text {
            margin-top: 15pt;
            margin-bottom: 6px;
            text-align: justify;
        }

        /* ── Items Table (Sesuai Asli) ───────────────────────── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
            font-size: 12pt;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 3px 6px;
        }

        .items-table thead tr {
            background-color: #a6a6a6;
        }

        .items-table thead th {
            font-weight: bold;
            text-align: center;
            padding: 4px 6px;
            color: #000;
        }

        .items-table tbody tr td.empty-cell {
            background-color: transparent !important;
            border: none !important;
        }

        /* Baris ringkasan: label & nominal rata tengah */
        .items-table tbody tr td.summary-cell {
            background-color: #a6a6a6 !important;
            font-weight: bold;
            text-align: center;
        }

        .c { text-align: center; }
        .l { text-align: left; }
        .r { text-align: right; }

        /* ── Footer Info ────────────────────────────────────── */
        .terbilang {
            margin: 6px 0 8px 0;
            font-style: italic;
            font-weight: bold;
        }

        .payment-info {
            margin: 8px 0 0 0;
            line-height: 1.4;
        }

        /* Kalimat penutup: 2 baris kosong di atasnya (2 x 15pt, revisi klien) */
        .closing {
            margin: 30pt 0 8px 0;
            text-align: justify;
        }

        .bank-table {
            margin-top: 4px;
            border-collapse: collapse;
        }

        /* Nama bank, nomor & pemilik rekening tidak di-bold */
        .bank-table td {
            padding: 0 18px 1px 0;
            vertical-align: top;
        }

        /* ── Signature Section ──────────────────────────────── */
        .signature-container {
            width: 100%;
            margin-top: 12px;
        }

        .signature-box {
            float: left;
            width: 300px;
            line-height: 1.3;
        }

        .signature-img-wrapper {
            height: 60px;
            margin: 4px 0;
        }

        .signature-img-wrapper img {
            max-height: 60px;
            max-width: 170px;
        }

        /* Nama penandatangan tidak di-bold (tetap bergaris bawah) */
        .signature-name {
            text-decoration: underline;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        /* Stempel LUNAS (watermark_lunas.jpeg, rasio ±3:1) di paling depan, miring & di tengah halaman.
           Dibuat lebih transparan (revisi klien) agar teks di belakangnya mudah dibaca. */
        .stamp-lunas-overlay {
            position: fixed;
            top: 33%;
            left: 12%;
            width: 76%;
            opacity: 0.18;
            transform: rotate(-25deg);
            -webkit-transform: rotate(-25deg);
            z-index: 9999; /* Memastikan berada di PALING DEPAN */
            pointer-events: none;
        }
    </style>
</head>

<body>
    @if ($invoice->isFullyPaid())
        <img src="{{ public_path('images/watermark_lunas.jpeg') }}" class="stamp-lunas-overlay" alt="LUNAS">
    @endif

    @php
        $items = is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items;

        // % per item opsional (null bila kosong): Jumlah dihitung InvoiceProyek::itemAmount()
        // (persentase kosong → Jumlah = Harga), sama dengan perhitungan total di service.
        $hasPersentase = fn ($item) => isset($item['persentase']) && $item['persentase'] !== '';
        $itemJumlah = fn ($item) => \App\Models\Finance\InvoiceProyek::itemAmount(is_array($item) ? $item : []);

        $totalAmount = 0;
        foreach ($items as $item) {
            $totalAmount += $itemJumlah($item);
        }

        $discountAmount = ($invoice->discount_value && $invoice->discount_value > 0)
            ? $invoice->getDiscountAmount($totalAmount)
            : 0;
        $ppnAmount = $invoice->getPpnAmount();
        // Jumlah akhir (baris paling bawah & terbilang) = total item setelah discount + PPN
        $finalAmount = $totalAmount - $discountAmount + $ppnAmount;

        $selectedAccountIds = is_string($invoice->selected_payment_accounts)
            ? json_decode($invoice->selected_payment_accounts, true)
            : $invoice->selected_payment_accounts ?? [];

        $paymentAccounts = !empty($selectedAccountIds)
            ? \App\Models\Finance\PaymentAccount::whereIn('id', $selectedAccountIds)->orderBy('id')->get()
            : collect();
    @endphp

    {{-- ═══ KOP SURAT ═════════════════════════════════════════════════════════════ --}}
    <table class="header-top">
        <tr>
            <td class="logo-cell">
                <img src="{{ public_path('images/logo.jpeg') }}" alt="Logo">
            </td>
            <td class="title-cell">
                INVOICE
            </td>
            <td class="dummy-cell"></td>
        </tr>
    </table>
    <div class="header-divider"></div>

    {{-- ═══ PERUSAHAAN & METADATA ══════════════════════════════════════════════════ --}}
    <table class="info-table">
        <tr>
            <td class="company-info">
                <div class="company-name">PT. AGHITSNA KARYA INDAH</div>
                <div>JL. TANAH BARU RAYA PERTIWI RT.01/05</div>
                <div>BEJI. DEPOK.JAWA BARAT</div>
                <div>Telp. 021-29034923 – 0812.9596.552</div>
                <div>Email : Design@aghitsna.id</div>
            </td>
            <td class="meta-info">
                <table class="meta-table">
                    <tr>
                        <td class="label">No</td>
                        <td class="colon">:</td>
                        <td>{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td class="label">Tanggal</td>
                        <td class="colon">:</td>
                        <td>{{ \Carbon\Carbon::parse($invoice->invoice_date)->isoFormat('D MMMM YYYY') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Hal</td>
                        <td class="colon">:</td>
                        <td>{{ $invoice->regarding ?? 'Penagihan Pembayaran' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ═══ PENERIMA SURAT ═══════════════════════════════════════════════════════ --}}
    <div class="recipient-block">
        Kepada Yth :<br>
        {{ $invoice->recipient }}<br>
        Di Tempat
    </div>

    {{-- ═══ PARAGRAF PEMBUKA ══════════════════════════════════════════════════════ --}}
    <div class="opening-text">
        Dengan Hormat,<br>
        @if ($invoice->project_description)
            Dengan ini kami sampaikan invoice untuk pekerjaan {{ $invoice->project_description }}, Lokasi {{ $invoice->location ?? $invoice->quotation?->location ?? '-' }}, sebagai berikut :
        @else
            @if ($invoice->location ?? $invoice->quotation?->location)
                Dengan ini kami sampaikan invoice sebagai berikut : Lokasi {{ $invoice->location ?? $invoice->quotation?->location }}
            @else
                Dengan ini kami sampaikan invoice sebagai berikut :
            @endif
        @endif
    </div>

    {{-- ═══ TABEL ITEMS (ADMIN) ════════════════════════════════════════════ --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:5%">No</th>
                <th style="width:43%">Deskripsi</th>
                <th style="width:19%">Harga</th>
                <th style="width:11%">%</th>
                <th style="width:22%">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $idx => $item)
                <tr>
                    <td class="c">{{ $idx + 1 }}.</td>
                    <td class="l">{{ $item['deskripsi'] ?? '-' }}</td>
                    <td class="c">{{ format_rupiah($item['harga'] ?? 0) }}</td>
                    {{-- % tanpa nol di belakang koma (25%, 12,5%); kosong bila tidak diisi --}}
                    <td class="c">{{ $hasPersentase($item) ? format_persen($item['persentase']) . '%' : '' }}</td>
                    <td class="c">{{ format_rupiah($itemJumlah($item)) }}</td>
                </tr>
            @endforeach

            {{-- Ringkasan (revisi klien): [Discount] → PPN → Pembayaran Ke-n → Jumlah (paling bawah) --}}
            @if ($discountAmount > 0)
                <tr>
                    <td colspan="2" class="empty-cell"></td>
                    <td colspan="2" class="summary-cell">Discount{{ $invoice->discount_type === 'percentage' ? ' (' . format_persen($invoice->discount_value) . '%)' : '' }}</td>
                    <td class="summary-cell">{{ format_rupiah($discountAmount) }}</td>
                </tr>
            @endif

            {{-- Baris PPN selalu tampil, walau PPN kosong --}}
            <tr>
                <td colspan="2" class="empty-cell"></td>
                <td colspan="2" class="summary-cell">{{ $ppnAmount > 0 ? 'PPN (' . format_persen($invoice->ppn) . '%)' : 'PPN' }}</td>
                <td class="summary-cell">{{ format_rupiah($ppnAmount) }}</td>
            </tr>

            {{-- Baris Cicilan --}}
            @php
                $paymentInstallments = is_string($invoice->payment_installments)
                    ? json_decode($invoice->payment_installments, true)
                    : $invoice->payment_installments;
            @endphp
            @if (is_array($paymentInstallments) && count($paymentInstallments) > 0)
                @foreach ($paymentInstallments as $index => $payment)
                    <tr>
                        <td colspan="2" class="empty-cell"></td>
                        <td colspan="2" class="summary-cell">{{ $payment['label'] ?? 'Pembayaran ' . ($index + 1) }}</td>
                        <td class="summary-cell">{{ format_rupiah($payment['amount'] ?? 0) }}</td>
                    </tr>
                @endforeach
            @endif

            {{-- Baris Jumlah (akhir): item setelah discount + PPN --}}
            <tr>
                <td colspan="2" class="empty-cell"></td>
                <td colspan="2" class="summary-cell">Jumlah</td>
                <td class="summary-cell">{{ format_rupiah($finalAmount) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ═══ TERBILANG ═══════════════════════════════════════════════════════════ --}}
    <div class="terbilang">
        Terbilang : <em>{{ ucwords(terbilang(round($finalAmount))) . ' Rupiah' }}</em>
    </div>

    {{-- ═══ INFO PEMBAYARAN ═══════════════════════════════════════════════════════ --}}
    @if ($paymentAccounts->isNotEmpty())
        <div class="payment-info">
            Pembayaran dapat ditransfer melalui nomor rekening :
            <table class="bank-table">
                @foreach ($paymentAccounts as $acc)
                    <tr>
                        <td>{{ $acc->bank_name }}</td>
                        <td>{{ $acc->account_number }}</td>
                        <td>{{ $acc->account_holder }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    {{-- ═══ PENUTUP ═════════════════════════════════════════════════════════════ --}}
    <div class="closing">
        Demikian invoice ini kami sampaikan atas perhatian dan kerja samanya kami ucapkan terimakasih.
    </div>

    {{-- ═══ TANDA TANGAN ═════════════════════════════════════════════════════════ --}}
    <div class="signature-container clearfix">
        <div class="signature-box">
            <div>Hormat Kami,</div>
            <div>PT. AGHITSNA KARYA INDAH</div>
            <div class="signature-img-wrapper">
                @if ($invoice->signedBy?->signature_image)
                    <img src="{{ storage_path('app/public/' . $invoice->signedBy->signature_image) }}" alt="Tanda Tangan">
                @endif
            </div>
            @if ($invoice->signedBy)
            <div class="signature-name">{{ $invoice->signedBy->name }}</div>
            <div>{{ $invoice->signedBy->position }}</div>
            @endif
        </div>
    </div>

</body>

</html>