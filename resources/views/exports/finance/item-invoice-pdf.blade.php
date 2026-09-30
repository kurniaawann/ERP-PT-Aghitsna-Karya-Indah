<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Item - {{ $invoice->invoice_number }}</title>
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

        /* Jarak ±1 baris kosong: Email → Kepada Yth → "Dengan ini kami sampaikan" */
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
    @if($invoice->salesRecap?->status === 'Lunas')
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

        <!-- Recipient (tidak di-bold) -->
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

        <!-- Items Table (kolom sama dengan Invoice Alumunium/Proyek; Volume, Satuan, Harga, Jumlah rata tengah) -->
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
                        $quantity = (int) ($item['quantity'] ?? 0);
                        $sellingPrice = (int) ($item['selling_price'] ?? 0);
                        $jumlah = $quantity * $sellingPrice;
                        $totalAmount += $jumlah;
                    @endphp
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td>{{ $item['name_item'] ?? '-' }}</td>
                        <td class="center">{{ $quantity }}</td>
                        <td class="center">{{ $item['satuan'] ?? '' }}</td>
                        <td class="center">Rp {{ number_format($sellingPrice, 0, ',', '.') }}</td>
                        <td class="center">Rp {{ number_format($jumlah, 0, ',', '.') }}</td>
                    </tr>
                @endforeach

                <tr>
                    <td colspan="4" style="border: none; background-color: #fff;"></td>
                    <td class="center" style="background-color: #FFFF00; border: 1px solid #000;"><strong>Jumlah</strong></td>
                    <td class="center" style="background-color: #FFFF00; border: 1px solid #000;"><strong>Rp {{ number_format($totalAmount, 0, ',', '.') }}</strong></td>
                </tr>
            </tbody>
        </table>

        <div class="terbilang">Terbilang : {{ ucwords(terbilang($totalAmount)) }} rupiah</div>

        <!-- Payment Information (nama bank, nomor & pemilik rekening tidak di-bold) -->
        <div class="payment-info">
            Pembayaran dapat ditransfer melalui nomor rekening<br>
            @php
                $selectedAccountIds = is_string($invoice->selected_payment_accounts)
                    ? json_decode($invoice->selected_payment_accounts, true)
                    : $invoice->selected_payment_accounts ?? [];

                if (!empty($selectedAccountIds)) {
                    $paymentAccounts = \App\Models\Finance\PaymentAccount::whereIn('id', $selectedAccountIds)
                        ->orderBy('id')
                        ->get();
                } else {
                    $paymentAccounts = \App\Models\Finance\PaymentAccount::active()->get();
                }
            @endphp
            @foreach ($paymentAccounts as $account)
                {{ $account->bank_name }} / No : {{ $account->account_number }} a/n {{ $account->account_holder }}<br>
            @endforeach
            @if ($paymentAccounts->isEmpty())
                <em>Tidak ada rekening pembayaran yang tersedia</em>
            @endif
        </div>

        <div class="closing">Demikian invoice ini kami sampaikan atas perhatian dan kerjasamanya kami ucapkan terima kasih.</div>

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
