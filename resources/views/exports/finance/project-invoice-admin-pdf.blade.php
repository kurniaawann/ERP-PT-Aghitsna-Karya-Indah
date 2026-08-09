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

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11px;
            line-height: 1.4;
            color: #000;
            padding: 12mm 15mm;
        }

        /* ── Kop Surat ────────────────────────────────────────── */
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
            width: 25%;
            text-align: left;
        }

        .logo-cell img {
            display: block;
            height: 55px;
            width: auto;
            object-fit: contain;
        }

        .title-cell {
            width: 50%;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .dummy-cell {
            width: 25%;
        }

        .header-divider {
            border-bottom: 3px solid #000;
            margin-bottom: 12px;
        }

        /* ── Info Perusahaan & Meta Surat ────────────────────── */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 11px;
        }

        .info-table td {
            vertical-align: top;
            padding: 0;
        }

        .company-info {
            width: 55%;
            line-height: 1.3;
        }

        .company-name {
            font-weight: bold;
            margin-bottom: 2px;
        }

        .meta-info {
            width: 20%;
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
        }

        .meta-table td.colon {
            width: 15px;
            text-align: center;
        }

        /* ── Recipient & Opening ─────────────────────────────── */
        .recipient-block {
            margin-bottom: 12px;
            font-size: 11px;
            line-height: 1.4;
        }

        .opening-text {
            margin-bottom: 12px;
            font-size: 11px;
            line-height: 1.4;
        }

        /* ── Items Table (Sesuai Asli) ───────────────────────── */
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
            background-color: #a6a6a6;
        }

        .items-table thead th {
            font-weight: bold;
            text-align: center;
            font-size: 10.5px;
            color: #000;
        }

        .items-table tbody tr td.empty-cell {
            background-color: transparent !important;
            border: none !important;
        }

        .items-table tbody tr td.summary-cell {
            background-color: #a6a6a6 !important;
            font-weight: bold;
            font-size: 10.5px;
        }

        .c { text-align: center; }
        .l { text-align: left; }
        .r { text-align: right; }

        /* ── Footer Info ────────────────────────────────────── */
        .terbilang {
            margin: 10px 0 15px 0;
            font-size: 11px;
            font-style: italic;
        }

        .payment-info {
            margin: 12px 0;
            font-size: 11px;
            line-height: 1.5;
        }

        .bank-table {
            margin-top: 4px;
            border-collapse: collapse;
        }

        .bank-table td {
            padding: 1px 15px 1px 0;
            vertical-align: top;
            font-size: 11px;
        }

        /* ── Signature Section ──────────────────────────────── */
        .signature-container {
            width: 100%;
            margin-top: 20px;
        }

        .signature-box {
            float: left;
            width: 250px;
            font-size: 11px;
            line-height: 1.3;
        }

        .signature-img-wrapper {
            height: 50px;
            margin: 4px 0;
        }

        .signature-img-wrapper img {
            max-height: 50px;
            max-width: 150px;
            object-fit: contain;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

         /* Stempel LUNAS di Paling Depan & Transparan */
        .stamp-lunas-overlay {
            position: fixed;
            top: 28%;
            left: 10%;
            width: 80%;
            max-width: 550px;
            opacity: 0.35; /* Tingkat transparan (0.3 - 0.4 agar teks dibelakangnya tetap terbaca) */
            transform: rotate(-25deg);
            -webkit-transform: rotate(-25deg);
            z-index: 9999; /* Memastikan berada di PALING DEPAN */
            pointer-events: none;
        }
    </style>
</head>

<body>
    @if ($invoice->isFullyPaid())
        <img src="{{ public_path('images/status_paid_proyek_and_item.jpeg') }}" class="stamp-lunas-overlay" alt="LUNAS">
    @endif

    @php
        $items = is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items;
        $totalAmount = 0;
        foreach ($items as $item) {
            $totalAmount += floatval($item['volume'] ?? 0) * floatval($item['harga'] ?? 0);
        }

        $discountAmount = ($invoice->discount_value && $invoice->discount_value > 0)
            ? $invoice->getDiscountAmount($totalAmount)
            : 0;
        $dpAmount = ($invoice->dp_value && $invoice->dp_value > 0)
            ? $invoice->getDpAmount()
            : 0;
        $ppnAmount = $invoice->getPpnAmount();
        $hasAdjustments = $discountAmount > 0 || $dpAmount > 0 || $ppnAmount > 0;
        $finalAmount = $totalAmount - $discountAmount + $ppnAmount - $dpAmount;

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
        Bpk. {{ $invoice->recipient }}<br>
        Di Tempat
    </div>

    {{-- ═══ PARAGRAF PEMBUKA ══════════════════════════════════════════════════════ --}}
    <div class="opening-text">
        Dengan Hormat,<br>
        Dengan ini kami sampaikan Invoice untuk pekerjaan {{ $invoice->project_description }},<strong>Lokasi {{ $invoice->location ?? $invoice->quotation?->location ?? '-' }}, </strong>sebagai berikut : 
    </div>

    {{-- ═══ TABEL ITEMS (TIDAK DIUBAH) ════════════════════════════════════════════ --}}
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

            {{-- Baris Jumlah --}}
            <tr>
                <td colspan="4" class="empty-cell"></td>
                <td class="summary-cell c">Jumlah</td>
                <td class="summary-cell r">Rp &nbsp;{{ number_format($totalAmount, 0, ',', '.') }}</td>
            </tr>

            {{-- Baris Discount --}}
            @if ($discountAmount > 0)
                <tr>
                    <td colspan="4" class="empty-cell"></td>
                    <td class="summary-cell c">Discount {{ $invoice->discount_type === 'percentage' ? '(' . rtrim(rtrim(number_format((float) $invoice->discount_value, 2, ',', '.'), '0'), ',') . '%)' : '' }}</td>
                    <td class="summary-cell r">Rp &nbsp;-{{ number_format($discountAmount, 0, ',', '.') }}</td>
                </tr>
            @endif

            {{-- Baris PPN --}}
            @if ($ppnAmount > 0)
                <tr>
                    <td colspan="4" class="empty-cell"></td>
                    <td class="summary-cell c">PPN ({{ rtrim(rtrim(number_format((float) $invoice->ppn, 2, ',', '.'), '0'), ',') }}%)</td>
                    <td class="summary-cell r">Rp &nbsp;{{ number_format($ppnAmount, 0, ',', '.') }}</td>
                </tr>
            @endif

            {{-- Baris DP --}}
            @if ($dpAmount > 0)
                <tr>
                    <td colspan="4" class="empty-cell"></td>
                    <td class="summary-cell c">DP {{ $invoice->dp_type === 'percentage' ? '(' . rtrim(rtrim(number_format((float) $invoice->dp_value, 2, ',', '.'), '0'), ',') . '%)' : '' }}</td>
                    <td class="summary-cell r">Rp &nbsp;-{{ number_format($dpAmount, 0, ',', '.') }}</td>
                </tr>
            @endif

            {{-- Baris Total / Sisa Pembayaran --}}
            <tr>
                <td colspan="4" class="empty-cell"></td>
                <td class="summary-cell c">{{ $hasAdjustments ? 'Sisa Pembayaran' : 'Total' }}</td>
                <td class="summary-cell r">Rp &nbsp;{{ number_format($finalAmount, 0, ',', '.') }}</td>
            </tr>

            {{-- Baris Cicilan --}}
            @if ($invoice->payment_installments)
                @php
                    $paymentInstallments = is_string($invoice->payment_installments)
                        ? json_decode($invoice->payment_installments, true)
                        : $invoice->payment_installments;
                @endphp
                @if (is_array($paymentInstallments) && count($paymentInstallments) > 0)
                    @foreach ($paymentInstallments as $index => $payment)
                        <tr>
                            <td colspan="4" class="empty-cell"></td>
                            <td class="summary-cell c">{{ $payment['label'] ?? 'Pembayaran ' . ($index + 1) }}</td>
                            <td class="summary-cell r">Rp &nbsp;{{ number_format($payment['amount'] ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                @endif
            @endif
        </tbody>
    </table>

    {{-- ═══ TERBILANG ═══════════════════════════════════════════════════════════ --}}
    <div class="terbilang">
        Terbilang : <em>{{ ucwords(terbilang($finalAmount)) . ' Rupiah' }}</em>
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
            <div class="signature-name">{{ $invoice->signedBy?->name ?? 'Zulkarnain,S.T.,M.T.' }}</div>
            <div>{{ $invoice->signedBy?->position ?? 'Direktur' }}</div>
        </div>
    </div>

</body>

</html>