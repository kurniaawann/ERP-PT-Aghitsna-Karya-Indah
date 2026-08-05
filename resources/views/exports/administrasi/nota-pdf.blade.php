<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota - PT. Aghitsna Karya Indah</title>
    <style>
        @page {
            size: A4;
            margin: 0.6cm 0.8cm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            font-size: 10px;
            color: #0f386b; /* Warna biru navy khas cetakan nota */
            background: #fff;
        }

        .nota-container {
            /* width: 100%;
            margin: 0 auto;
            padding: 5px; */
            margin: 20px;
        }

        /* HEADER LAYOUT */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .header-left {
            vertical-align: top;
            width: 60%;
        }

        .company-logo {
            max-width: 280px;
            height: auto;
            display: block;
            margin-bottom: 8px;
        }

        /* Kotak Faktur & SJ No di Kiri Bawah Logo */
        .faktur-box {
            border-collapse: collapse;
            width: 220px;
            margin-top: 4px;
        }

        .faktur-box td {
            border: 1px solid #0f386b;
            padding: 3px 6px;
            font-size: 10px;
            font-weight: bold;
        }

        .faktur-label {
            width: 85px;
            background-color: #ffffff;
        }

        /* .header-right {
            vertical-align: top;
            width: 40%;
            text-align: right;
            padding-left: 15px;
        }

        .location-date {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 8px;
            text-align: right;
        }

        .kepada-section {
            text-align: left;
            margin-left: auto;
            width: 200px;
        }

        .kepada-title {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .kepada-line {
            border-bottom: 1px dotted #0f386b;
            min-height: 18px;
            font-size: 10px;
            font-weight: bold;
            padding-left: 4px;
        } */

        .header-right {
    vertical-align: top;
    width: 40%;
    text-align: right; /* Posisikan seluruh kotak ke sebelah kanan kertas */
}

/* Kotak pembungkus agar semua teks di dalamnya LURUS/SEJAJAR di garis kiri yang sama */
.kepada-box-wrapper {
    display: inline-block;
    text-align: left; /* Teks tanggal, Kepada Yth, dan Nama akan sejajar rata kiri */
    width: 220px;
}

.location-date {
    font-size: 10px;
    font-weight: bold;
    margin-bottom: 6px;
}

.kepada-title {
    font-size: 10px;
    font-weight: bold;
    margin-bottom: 4px;
}

.kepada-line {
    border-bottom: 1px dotted #0f386b;
    min-height: 18px;
    font-size: 10px;
    font-weight: bold;
    padding-left: 2px;
}

        /* MAIN TABLE */
        .main-table {
            width: 100%;
            border-collapse: collapse;
        }

        .main-table th,
        .main-table td {
            border: 1px solid #0f386b;
            padding: 5px 6px;
            font-size: 10px;
            vertical-align: middle;
        }

        .main-table th {
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
        }

        .col-banyaknya { width: 12%; text-align: center; }
        .col-nama { width: 48%; }
        .col-harga { width: 20%; text-align: center; }
        .col-jumlah { width: 20%; text-align: center; }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .item-row {
            height: 22px;
        }

        .empty-row td {
            height: 22px;
        }

        /* INTEGRATED BOTTOM SECTION */
        .bottom-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: -1px;
        }

        /* TANPA BORDER DI SISI KIRI BAWAH */
        .bottom-left-cell {
            vertical-align: top;
            width: 60%;
            border: none; /* Border dihapus total */
            padding: 10px 10px 0 0;
        }

        .bottom-right-cell {
            vertical-align: top;
            width: 40%;
            padding: 0;
            border: none;
        }

        .period-text {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .period-line {
            display: inline-block;
            border-bottom: 1px dotted #0f386b;
            min-width: 100px;
            text-align: center;
        }

        .bank-info {
            font-size: 8.5px;
            margin-bottom: 8px;
            line-height: 1.3;
        }

        .footer-note {
            font-size: 8.5px;
            line-height: 1.3;
            font-style: italic;
            margin-bottom: 25px;
        }

        .penerima-box {
            font-size: 10px;
            font-weight: bold;
            text-align: left;
        }

        .penerima-space {
            height: 40px;
        }

        /* TOTALS SUMMARY TABLE */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            border: 1px solid #0f386b;
            padding: 4px 6px;
            font-size: 9.5px;
            font-weight: bold;
            height: 22px;
        }

        .summary-label {
            width: 50%;
            text-align: left;
        }

        .summary-value {
            width: 50%;
            text-align: right;
        }

        .total-row td {
            font-size: 10.5px;
            font-weight: bold;
        }

        .page-break {
            page-break-after: always;
        }

        @media print {
            body {
                padding: 0;
            }

            .page-break {
                page-break-after: always;
            }
        }
    </style>
</head>

<body>
    @foreach ($notas as $index => $nota)
        <div class="nota-container">
            
            <!-- HEADER -->
            <table class="header-table">
                <tr>
                    <!-- KIRI: Logo & Box Faktur/SJ -->
                    <td class="header-left">
                        <img src="{{ public_path('images/invoice_administrasi.jpeg') }}" alt="PT. Aghitsna Karya Indah" class="company-logo">
                        
                        <table class="faktur-box">
                            <tr>
                                <td class="faktur-label">FAKTUR No.</td>
                                <td>{{ $nota->faktur_no }}</td>
                            </tr>
                            <tr>
                                <td class="faktur-label">SJ No.</td>
                                <td>{{ $nota->sj_no }}</td>
                            </tr>
                        </table>
                    </td>

                    <!-- KANAN: Tanggal & Kepada Yth. -->
                    {{-- <td class="header-right">
                        <div class="location-date">
                            {{ $nota->location }}, {{ \Carbon\Carbon::parse($nota->nota_date)->format('d F Y') }}
                        </div>
                        <div class="kepada-section">
                            <div class="kepada-title">Kepada Yth,</div>
                            <div class="kepada-line">{{ $nota->kepada }}</div>
                            <div class="kepada-line"></div>
                            <div class="kepada-line"></div>
                        </div>
                    </td> --}}
                    <td class="header-right">
    <div class="kepada-box-wrapper">
        <div class="location-date">
            {{ $nota->location }}, {{ \Carbon\Carbon::parse($nota->nota_date)->format('d F Y') }}
        </div>
        <div class="kepada-title">Kepada Yth,</div>
        <div class="kepada-line">{{ $nota->kepada }}</div>
        <div class="kepada-line"></div>
        <div class="kepada-line"></div>
    </div>
</td>
                </tr>
            </table>

            <!-- TABEL BARANG UTAMA -->
            <table class="main-table">
                <thead>
                    <tr>
                        <th class="col-banyaknya">BANYAKNYA</th>
                        <th class="col-nama">NAMA BARANG</th>
                        <th class="col-harga">HARGA SATUAN</th>
                        <th class="col-jumlah">JUMLAH</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($nota->items as $item)
                        <tr class="item-row">
                            <td class="col-banyaknya text-center">{{ $item['banyaknya'] }}</td>
                            <td class="col-nama">{{ $item['nama_barang'] }}</td>
                            <td class="col-harga text-right">Rp {{ number_format($item['harga_satuan'], 0, ',', '.') }}</td>
                            <td class="col-jumlah text-right">Rp {{ number_format($item['jumlah'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach

                    <!-- Baris Kosong Pelengkap Grid (Minimal 7 Baris) -->
                    @for ($i = count($nota->items); $i < 7; $i++)
                        <tr class="empty-row">
                            <td class="col-banyaknya"></td>
                            <td class="col-nama"></td>
                            <td class="col-harga"></td>
                            <td class="col-jumlah"></td>
                        </tr>
                    @endfor
                </tbody>
            </table>

            <!-- BAGIAN BAWAH -->
            <table class="bottom-table">
                <tr>
                    <!-- KIRI: Periode, Rekening, Catatan & Penerima (TANPA BORDER) -->
                    <td class="bottom-left-cell">
                        <div class="period-text">
                            Periode : <span class="period-line">{{ $nota->periode_start ? \Carbon\Carbon::parse($nota->periode_start)->format('d/m/Y') : '' }}</span> s/d <span class="period-line">{{ $nota->periode_end ? \Carbon\Carbon::parse($nota->periode_end)->format('d/m/Y') : '' }}</span>
                        </div>

                        @php $banks = $nota->paymentAccounts(); @endphp
                        @if ($banks && $banks->count() > 0)
                            <div class="bank-info">
                                @foreach ($banks as $bank)
                                    <div><strong>Rek. {{ $bank->bank_name }}:</strong> {{ $bank->account_number }} a/n {{ $bank->account_holder }}</div>
                                @endforeach
                            </div>
                        @endif

                        <div class="footer-note">
                            *) Faktur dianggap lunas setelah dana kami terima<br>
                            tunai atau telah ditransfer ke rekening kami.
                        </div>

                        <div class="penerima-box">
                            Penerima,
                            <div class="penerima-space"></div>
                        </div>
                    </td>

                    <!-- KANAN: Rincian Total / Summary (Sewa/Jual, Ongkir, dll) -->
                    <td class="bottom-right-cell">
                        <table class="summary-table">
                            <tr>
                                <td class="summary-label">Sewa / Jual</td>
                                <td class="summary-value">{{ $nota->sewa_jual ? 'Rp ' . number_format($nota->sewa_jual, 0, ',', '.') : '' }}</td>
                            </tr>
                            <tr>
                                <td class="summary-label">Ongkos Kirim PP / 1x</td>
                                <td class="summary-value">{{ $nota->ongkos_kirim ? 'Rp ' . number_format($nota->ongkos_kirim, 0, ',', '.') : '' }}</td>
                            </tr>
                            <tr>
                                <td class="summary-label">Bongkar / Pasang</td>
                                <td class="summary-value">{{ $nota->bongkar_pasang ? 'Rp ' . number_format($nota->bongkar_pasang, 0, ',', '.') : '' }}</td>
                            </tr>
                            <tr>
                                <td class="summary-label">Lembur Antar / Ambil</td>
                                <td class="summary-value">{{ $nota->lembur ? 'Rp ' . number_format($nota->lembur, 0, ',', '.') : '' }}</td>
                            </tr>
                            <tr>
                                <td class="summary-label">Uang Jaminan</td>
                                <td class="summary-value">{{ $nota->uang_jaminan ? 'Rp ' . number_format($nota->uang_jaminan, 0, ',', '.') : '' }}</td>
                            </tr>
                            @if ($nota->ppn_percentage > 0)
                            <tr>
                                <td class="summary-label">PPN ({{ $nota->ppn_percentage }}%)</td>
                                <td class="summary-value">Rp {{ number_format($nota->ppn_amount, 0, ',', '.') }}</td>
                            </tr>
                            @endif
                            <tr class="total-row">
                                <td class="summary-label">Jumlah</td>
                                <td class="summary-value">Rp {{ number_format($nota->total_with_ppn, 0, ',', '.') }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

        </div>

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>

</html>