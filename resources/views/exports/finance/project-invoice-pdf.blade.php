@php
    /**
     * PDF Invoice Proyek Template
     *
     * Renders the PDF for a project invoice.
     * Variables: $invoice (InvoiceProyek model)
     *
     * Sections:
     * - HTML/CSS: Inline styles for PDF rendering compatibility
     * - Header: Company logo, name, address, invoice metadata
     * - Recipient: Client name and address
     * - Description: Project description
     * - Items Table: Line items with volume, unit, price, subtotal
     * - Financial Summary: Subtotal, discount, DP, payment installments
     * - Terbilang: Indonesian number-to-words
     * - Payment Info: Bank account details for transfer
     * - Signature: Closing and company signature
     */
@endphp
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            padding: 10mm 15mm 10mm 15mm;
            position: relative;
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

        .container {
            max-width: 210mm;
            margin: 0 auto;
        }

        /* ── Kop Surat (lebih besar dari isi dokumen) ── */
        .header-table,
        .company-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td,
        .company-table td {
            border: none;
            padding: 0;
        }

        .logo-cell img {
            display: block;
            width: 118px;
            height: 70px;
        }

        .invoice-title {
            font-size: 20pt;
            font-weight: bold;
            letter-spacing: 2px;
        }

        .company-table {
            margin-top: 6px;
        }

        .company-name {
            font-size: 16pt;
            font-weight: bold;
            line-height: 1.2;
            padding-bottom: 2px !important;
        }

        .invoice-info td {
            padding: 0 0 1px 0;
            vertical-align: top;
        }

        /* Kepada Yth tidak di-bold; jarak ±1 baris kosong: Email → Kepada Yth → "Dengan ini kami sampaikan" */
        .recipient {
            margin: 24px 0 0 0;
        }

        .description {
            margin: 20px 0 6px 0;
            text-align: justify;
        }

        /* Blok No/Tanggal/Hal rata ke tepi kanan (sejajar tepi kanan tabel item) */
        .invoice-info table {
            margin-left: auto;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
        }

        .items-table th {
            background-color: #f0f0f0;
            border: 1px solid #000;
            padding: 4px 6px;
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
        }

        .items-table td {
            border: 1px solid #000;
            padding: 3px 6px;
            font-size: 12pt;
        }

        .items-table td.center {
            text-align: center;
        }

        .items-table td.right {
            text-align: right;
        }

        .items-table td.left {
            text-align: left;
        }

        /* Baris ringkasan (Jumlah/Discount/DP/Sisa): label & nominal rata tengah */
        .items-table td.summary-cell {
            border: 1px solid #000;
            text-align: center;
            font-weight: bold;
        }

        .items-table td.empty-cell {
            border: none;
            background-color: #fff;
        }

        .terbilang {
            font-style: italic;
            font-weight: bold;
            margin: 6px 0;
        }

        .payment-info {
            margin: 6px 0;
            line-height: 1.4;
        }

        .closing {
            margin: 6px 0;
            text-align: justify;
        }
    </style>
</head>

<body>
    <!-- Gambar Stempel Lunas di Lapisan Paling Depan -->
    @if($invoice->isFullyPaid())
        <img src="{{ public_path('images/status_paid_proyek_and_item.jpeg') }}" class="stamp-lunas-overlay" alt="LUNAS">
    @endif

    <div class="container">
        <!-- Header: Logo & Judul -->
        <table class="header-table" cellpadding="0" cellspacing="0" border="0" width="100%">
            <tr>
                <td class="logo-cell" width="30%" valign="middle">
                    <img src="{{ public_path('images/logo.jpeg') }}" alt="Logo">
                </td>
                <td width="40%" valign="middle" style="text-align: center;">
                    <div class="invoice-title">INVOICE</div>
                </td>
                <td width="30%"></td>
            </tr>
        </table>

        <!-- Header: Perusahaan & Info Invoice -->
        <table class="company-table" cellpadding="0" cellspacing="0" border="0" width="100%">
            <tr>
                <td colspan="2" class="company-name">PT. AGHITSNA KARYA INDAH</td>
            </tr>
            <tr>
                <td width="60%" valign="top">
                    <div>JL. TANAH BARU RAYA PERTIWI RT. 01/05</div>
                    <div>BEJI, DEPOK, JAWA BARAT</div>
                    <div>Telp. 021 - 29034923 - 0812 9596 552</div>
                    <div>Email : Design@aghitsna.id</div>
                </td>
                <td width="40%" valign="top">
                    <div class="invoice-info">
                        <table cellpadding="0" cellspacing="0" border="0" align="right">
                            <tr>
                                <td style="padding-right: 8px; white-space: nowrap;">No</td>
                                <td style="padding-right: 6px;">:</td>
                                <td>{{ $invoice->invoice_number }}</td>
                            </tr>
                            <tr>
                                <td style="padding-right: 8px; white-space: nowrap;">Tanggal</td>
                                <td style="padding-right: 6px;">:</td>
                                <td>{{ \Carbon\Carbon::parse($invoice->invoice_date)->isoFormat('DD MMMM YYYY') }}</td>
                            </tr>
                            <tr>
                                <td style="padding-right: 8px; white-space: nowrap;">Hal</td>
                                <td style="padding-right: 6px;">:</td>
                                <td>{{ $invoice->regarding ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Recipient -->
        <div class="recipient">
            <div class="recipient-label">Kepada Yth :</div>
            <div>
                <div>{{ $invoice->recipient }}</div>
                @if (!empty($invoice->proyek))
                    <div>{{ $invoice->proyek }}</div>
                @endif
            </div>
        </div>

        <!-- Description -->
        <div class="description">
            @if ($invoice->project_description)
                Dengan ini kami sampaikan invoice untuk proyek {{ $invoice->project_description }} sebagai berikut :
            @else
                Dengan ini kami sampaikan invoice sebagai berikut :
            @endif
        </div>

        <!-- Items Table (Volume, Satuan, Harga, Jumlah rata tengah) -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 36%;">Keterangan</th>
                    <th style="width: 10%;">Volume</th>
                    <th style="width: 9%;">Satuan</th>
                    <th style="width: 20%;">Harga</th>
                    <th style="width: 20%;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $items = is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items;
                    $totalAmount = 0;
                @endphp

                @foreach ($items as $index => $item)
                    @php
                        $jumlah = floatval($item['volume']) * floatval($item['harga']);
                        $totalAmount += $jumlah;
                    @endphp
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td class="left">{{ $item['keterangan'] }}</td>
                        <td class="center">{{ number_format($item['volume'], 2, ',', '.') }}</td>
                        <td class="center">{{ $item['satuan'] }}</td>
                        <td class="center">Rp {{ number_format($item['harga'], 0, ',', '.') }}</td>
                        <td class="center">Rp {{ number_format($jumlah, 0, ',', '.') }}</td>
                    </tr>
                @endforeach

                @php
                    $discountAmount = 0;
                    $dpAmount = 0;
                    $ppnAmount = 0;
                    if ($invoice->discount_value && $invoice->discount_value > 0) {
                        $discountAmount = $invoice->getDiscountAmount($totalAmount);
                    }
                    if ($invoice->dp_value && $invoice->dp_value > 0) {
                        $dpAmount = $invoice->getDpAmount();
                    }
                    $ppnAmount = $invoice->getPpnAmount();
                    $hasDiscountDpOrPpn = $discountAmount > 0 || $dpAmount > 0 || $ppnAmount > 0;
                    $remainingAmount = $totalAmount - $discountAmount + $ppnAmount - $dpAmount;
                    $grandTotal = $totalAmount - $discountAmount + $ppnAmount;
                @endphp

                <tr>
                    <td colspan="4" class="empty-cell"></td>
                    <td class="summary-cell">Jumlah</td>
                    <td class="summary-cell">Rp {{ number_format($totalAmount, 0, ',', '.') }}</td>
                </tr>

                @if ($invoice->discount_value && $invoice->discount_value > 0)
                    @php
                        $discountAmount = $invoice->getDiscountAmount($totalAmount);
                    @endphp

                    <!-- Discount Row -->
                    <tr>
                        <td colspan="4" class="empty-cell"></td>
                        <td class="summary-cell">Discount
                            @if ($invoice->discount_type === 'percentage')
                                ({{ format_persen($invoice->discount_value) }}%)
                            @endif
                        </td>
                        <td class="summary-cell">Rp {{ number_format($discountAmount, 0, ',', '.') }}</td>
                    </tr>
                @endif

                @if ($invoice->dp_value && $invoice->dp_value > 0)
                    @php
                        $dpAmount = $invoice->getDpAmount();
                    @endphp

                    <!-- DP Row -->
                    <tr>
                        <td colspan="4" class="empty-cell"></td>
                        <td class="summary-cell">DP
                            @if ($invoice->dp_type === 'percentage')
                                ({{ format_persen($invoice->dp_value) }}%)
                            @endif
                        </td>
                        <td class="summary-cell">Rp {{ number_format($dpAmount, 0, ',', '.') }}</td>
                    </tr>
                @endif

                @if ($invoice->ppn && $invoice->ppn > 0)
                    <!-- PPN Row -->
                    <tr>
                        <td colspan="4" class="empty-cell"></td>
                        <td class="summary-cell">PPN ({{ format_persen($invoice->ppn) }}%)</td>
                        <td class="summary-cell">Rp {{ number_format($ppnAmount, 0, ',', '.') }}</td>
                    </tr>
                @endif

                @if ($hasDiscountDpOrPpn)
                    <tr>
                        <td colspan="4" class="empty-cell"></td>
                        <td class="summary-cell">Sisa Pembayaran</td>
                        <td class="summary-cell">Rp {{ number_format($remainingAmount, 0, ',', '.') }}</td>
                    </tr>
                @endif

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
                                <td class="summary-cell" style="background-color: #E9D5FF;">
                                    {{ $payment['label'] ?? 'Pembayaran ' . ($index + 1) }}
                                </td>
                                <td class="summary-cell" style="background-color: #E9D5FF;">
                                    Rp {{ number_format($payment['amount'] ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    @endif
                @endif
            </tbody>
        </table>

        <!-- Terbilang -->
        <div class="terbilang">
            Terbilang : {{ ucwords(terbilang($grandTotal)) }} rupiah
        </div>

        <!-- Payment Information (nama bank, nomor & pemilik rekening tidak di-bold) -->
        @php
            $selectedAccountIds = is_string($invoice->selected_payment_accounts)
                ? json_decode($invoice->selected_payment_accounts, true)
                : $invoice->selected_payment_accounts ?? [];

            $paymentAccounts = !empty($selectedAccountIds)
                ? \App\Models\Finance\PaymentAccount::whereIn('id', $selectedAccountIds)->orderBy('id')->get()
                : collect();
        @endphp
        @if ($paymentAccounts->isNotEmpty())
            <div class="payment-info">
                Pembayaran dapat ditransfer melalui nomor rekening<br>
                @foreach ($paymentAccounts as $account)
                    {{ $account->bank_name }} / No : {{ $account->account_number }} a/n {{ $account->account_holder }}<br>
                @endforeach
            </div>
        @endif

        <!-- Closing -->
        <div class="closing">
            Demikian Invoice ini kami sampaikan atas perhatian dan kerja samanya kami ucapkan terimakasih.
        </div>

        <!-- Signature (nama PT & penandatangan tidak di-bold) -->
        <table cellpadding="0" cellspacing="0" style="width: 100%; border: none; margin-top: 8px;">
            <tr>
                <td style="width: 50%; border: none; vertical-align: top; text-align: left;">
                    <div>Hormat Kami,</div>
                    <div>PT. AGHITSNA KARYA INDAH</div>
                    @if ($invoice->signedBy)
                    <div style="margin-top: {{ $invoice->signedBy->signature_image ? '4px' : '60px' }};">
                        @if ($invoice->signedBy->signature_image)
                            <img src="{{ storage_path('app/public/' . $invoice->signedBy->signature_image) }}"
                                alt="Tanda Tangan" style="max-height: 60px; max-width: 170px;">
                        @endif
                        <div>{{ $invoice->signedBy->name }}</div>
                    </div>
                    @endif
                    @if ($invoice->division)
                    <div style="margin-top: 4px;">{{ $invoice->division->name }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
