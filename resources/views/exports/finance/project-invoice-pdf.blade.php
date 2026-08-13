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

       
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11px;
            line-height: 1.4;
            padding: 15mm 15mm 15mm 15mm;
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

        .header {
            width: 100%;
            margin-bottom: 15px;
        }

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

        .title-cell {
            text-align: center;
            vertical-align: middle;
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
            /* margin-right: 15px; */
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

        .recipient {
            margin: 5px 0;
        }

        .recipient-label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .recipient-name {
            margin-left: 80px;
            margin-bottom: 10px;
        }

        .recipient-table {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0;
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

        .description {
            margin: 5px 0;
            text-align: justify;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0;
        }

        .items-table th {
            background-color: #f0f0f0;
            border: 1px solid #000;
            padding: 8px 5px;
            text-align: center;
            font-weight: bold;
            font-size: 10px;
        }

        .items-table td {
            border: 1px solid #000;
            padding: 6px 5px;
            font-size: 10px;
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

        .total-row {
            font-weight: bold;
        }

        .terbilang {
            font-style: italic;
            margin: 5px 0;
            font-size: 10px;
        }

        .payment-info {
            margin: 5px 0;
            line-height: 1.8;
        }

        .closing {
            margin: 5px 0;
            text-align: justify;
        }

        .signature {
            margin-top: 5px;
            text-align: left;
        }

        .signature-line {
            margin-top: 60px;
            font-weight: bold;
        }

        .bold {
            font-weight: bold;
        }

        .italic {
            font-style: italic;
        }
    </style>
</head>

<body>
    <!-- Gambar Stempel Lunas di Lapisan Paling Depan -->
    @if($invoice->isFullyPaid())
        <img src="{{ public_path('images/status_paid_proyek_and_item.jpeg') }}" class="stamp-lunas-overlay" alt="LUNAS">
    @endif

    <div class="container">
            <!-- Header -->
    <table class="header-table" cellpadding="0" cellspacing="0" border="0" width="100%">
        
        <tr>
            <td width="45%" valign="top" style="padding-bottom: 15px;">
                <div class="logo-cell">
                    <img src="{{ public_path('images/logo.jpeg') }}" alt="Logo" width="80" height="80">
                </div>
            </td>
            
            <td width="20%" valign="middle" style="text-align: center; padding-bottom: 15px;">
                <div class="invoice-title" style="font-weight: bold; font-size: 16px; letter-spacing: 1px;">
                    INVOICE
                </div>
            </td>
            
            <td width="35%" valign="top" style="padding-bottom: 15px;"></td>
        </tr>

        <tr>
            <td valign="top">
                <div class="company-info" style="white-space: pre-line">
                    PT. AGHITSNA KARYA INDAH
                    JL. TANAH BARU RAYA PERTIWI RT. 01/05 BEJI, DEPOK, JAWA BARAT
                    Telp. 021 - 29034923 - 0812 9596 552
                    Email : Design@aghitsna.id
                </div>
            </td>

            <!-- Tengah: Dikosongkan -->
            <td valign="top"></td>

            <td valign="top">
                <div class="invoice-info" style="font-size: 12px;">
                    <table cellpadding="0" cellspacing="0" border="0" align="right">
                        <tr>
                            <td style="padding-right: 5px;" valign="top">No</td>
                            <td style="padding-right: 5px;" valign="top">:</td>
                            <td valign="top">{{ $invoice->invoice_number }}</td>
                        </tr>
                        <tr>
                            <td style="padding-right: 5px;" valign="top">Tanggal</td>
                            <td style="padding-right: 5px;" valign="top">:</td>
                            <td valign="top">{{ \Carbon\Carbon::parse($invoice->invoice_date)->isoFormat('DD MMMM YYYY') }}</td>
                        </tr>
                        <tr>
                            <td style="padding-right: 5px;" valign="top">Hal</td>
                            <td style="padding-right: 5px;" valign="top">:</td>
                            <td valign="top">{{ $invoice->regarding ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>

    </table>
</div>

        <!-- Recipient -->
        <div class="recipient">
            <table class="recipient-table">
                <tr>
                    <td class="recipient-label-cell">Kepada Yth :</td>
                    <td class="recipient-name-cell">
                        <div>{{ $invoice->recipient }}</div>
                        @if (!empty($invoice->proyek))
                            <div>{{ $invoice->proyek }}</div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <!-- Description -->
        <div class="description">
            @if ($invoice->project_description)
                Dengan ini kami sampaikan invoice untuk proyek {{ $invoice->project_description }} sebagai berikut :
            @else
                Dengan ini kami sampaikan invoice sebagai berikut :
            @endif
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 35%;">Keterangan</th>
                    <th style="width: 12%;">Volume</th>
                    <th style="width: 10%;">Satuan</th>
                    <th style="width: 18%;">Harga</th>
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
                        <td class="right">{{ number_format($item['volume'], 2, ',', '.') }}</td>
                        <td class="center">{{ $item['satuan'] }}</td>
                        <td class="right">Rp {{ number_format($item['harga'], 0, ',', '.') }}</td>
                        <td class="right">Rp {{ number_format($jumlah, 0, ',', '.') }}</td>
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
                    <td colspan="4" style="border: none; background-color: #fff;"></td>
                    <td class="right" style="border: 1px solid #000;"><strong>Jumlah</strong></td>
                    <td class="right" style="border: 1px solid #000;"><strong>Rp {{ number_format($totalAmount, 0, ',', '.') }}</strong></td>
                </tr>

                @if ($invoice->discount_value && $invoice->discount_value > 0)
                    @php
                        $discountAmount = $invoice->getDiscountAmount($totalAmount);
                    @endphp

                    <!-- Discount Row -->
                    <tr>
                        <td colspan="4" style="border: none; background-color: #fff;"></td>
                        <td class="right" style="border: 1px solid #000;"><strong>Discount
                                @if ($invoice->discount_type === 'percentage')
                                    ({{ number_format($invoice->discount_value, 0) }}%)
                                @endif
                            </strong></td>
                        <td class="right" style="border: 1px solid #000;"><strong>Rp {{ number_format($discountAmount, 0, ',', '.') }}</strong></td>
                    </tr>
                @endif

                @if ($invoice->dp_value && $invoice->dp_value > 0)
                    @php
                        $dpAmount = $invoice->getDpAmount();
                    @endphp

                    <!-- DP Row -->
                    <tr>
                        <td colspan="4" style="border: none; background-color: #fff;"></td>
                        <td class="right" style="border: 1px solid #000;"><strong>DP
                                @if ($invoice->dp_type === 'percentage')
                                    ({{ number_format($invoice->dp_value, 0) }}%)
                                @endif
                            </strong></td>
                        <td class="right" style="border: 1px solid #000;"><strong>Rp {{ number_format($dpAmount, 0, ',', '.') }}</strong></td>
                    </tr>
                @endif

                @if ($invoice->ppn && $invoice->ppn > 0)
                    <!-- PPN Row -->
                    <tr>
                        <td colspan="4" style="border: none; background-color: #fff;"></td>
                        <td class="right" style="border: 1px solid #000;"><strong>PPN
                                ({{ number_format($invoice->ppn, 0) }}%)</strong></td>
                        <td class="right" style="border: 1px solid #000;"><strong>Rp {{ number_format($ppnAmount, 0, ',', '.') }}</strong></td>
                    </tr>
                @endif

                @if ($hasDiscountDpOrPpn)
                    <tr>
                        <td colspan="4" style="border: none; background-color: #fff;"></td>
                        <td class="right" style="border: 1px solid #000;"><strong>Sisa Pembayaran</strong></td>
                        <td class="right" style="border: 1px solid #000;"><strong>Rp {{ number_format($remainingAmount, 0, ',', '.') }}</strong></td>
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
                                <td colspan="4" style="border: none; background-color: #fff;"></td>
                                <td class="right" style="background-color: #E9D5FF; border: 1px solid #000;">
                                    <strong>{{ $payment['label'] ?? 'Pembayaran ' . ($index + 1) }}</strong>
                                </td>
                                <td class="right" style="background-color: #E9D5FF; border: 1px solid #000;">
                                    <strong>Rp {{ number_format($payment['amount'] ?? 0, 0, ',', '.') }}</strong>
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

        <!-- Payment Information -->
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
                    <strong>{{ $account->bank_name }}</strong> / No : <strong>{{ $account->account_number }}</strong> a/n
                    <strong>{{ $account->account_holder }}</strong><br>
                @endforeach
            </div>
        @endif

        <!-- Closing -->
        <div class="closing">
            Demikian Invoice ini kami sampaikan atas perhatian dan kerjasamanya kami ucapkan terima kasih.
        </div>

        <!-- Signature -->
        <table style="width: 100%; border: none; margin-top: 5px;">
            <tr>
                <td style="width: 50%; border: none; vertical-align: top; text-align: left;">
                    <div>Hormat Kami,</div>
                    <div style="font-weight: bold;">PT. AGHITSNA KARYA INDAH</div>
                    @if ($invoice->signedBy)
                    <div style="margin-top: {{ $invoice->signedBy->signature_image ? '5px' : '60px' }};">
                        @if ($invoice->signedBy->signature_image)
                            <img src="{{ storage_path('app/public/' . $invoice->signedBy->signature_image) }}"
                                alt="Tanda Tangan" style="max-height: 55px; max-width: 160px;">
                        @endif
                        <div style="font-weight: bold;">{{ $invoice->signedBy->name }}</div>
                    </div>
                    @endif
                    @if ($invoice->division)
                    <div style="margin-top: 5px;">{{ $invoice->division->name }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
