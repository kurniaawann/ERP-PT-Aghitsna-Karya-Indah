{{-- =====================================================================
     NOTA PDF - LAYOUT SEWA/JUAL
     PT Aghitsna Karya Indah

     Design nota existing (tipe_nota = sewa_jual):
     - Header: Logo + box FAKTUR/SJ di kiri, box tanggal & Kepada Yth di kanan
     - Tabel utama: BANYAKNYA / NAMA BARANG / HARGA SATUAN / JUMLAH
     - Bagian bawah kiri: Periode, Rekening, catatan, Penerima
     - Bagian bawah kanan: summary Sewa/Jual, Ongkir, Bongkar, Lembur,
       Uang Jaminan, PPN, Jumlah

     Seluruh selector CSS di-scope di bawah wrapper .nota-sewa-jual agar
     tidak bentrok dengan layout nota proyek pada dokumen yang sama.
     ===================================================================== --}}

<style>
    .nota-sewa-jual {
        font-family: 'Arial', 'Helvetica', sans-serif;
        font-size: 10px;
        color: #0f386b;
        background: #fff;
        margin: 20px;
    }

    .nota-sewa-jual .header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }

    .nota-sewa-jual .header-left {
        vertical-align: top;
        width: 60%;
    }

    .nota-sewa-jual .company-logo {
        max-width: 280px;
        height: auto;
        display: block;
        margin-bottom: 8px;
    }

    .nota-sewa-jual .faktur-box {
        border-collapse: collapse;
        width: 220px;
        margin-top: 4px;
    }

    .nota-sewa-jual .faktur-box td {
        border: 1px solid #0f386b;
        padding: 3px 6px;
        font-size: 10px;
        font-weight: bold;
    }

    .nota-sewa-jual .faktur-label {
        width: 85px;
        background-color: #ffffff;
    }

    .nota-sewa-jual .header-right {
        vertical-align: top;
        width: 40%;
        text-align: right;
    }

    .nota-sewa-jual .kepada-box-wrapper {
        display: inline-block;
        text-align: left;
        width: 220px;
    }

    .nota-sewa-jual .location-date {
        font-size: 10px;
        font-weight: bold;
        margin-bottom: 6px;
    }

    .nota-sewa-jual .kepada-title {
        font-size: 10px;
        font-weight: bold;
        margin-bottom: 4px;
    }

    .nota-sewa-jual .kepada-line {
        border-bottom: 1px dotted #0f386b;
        min-height: 18px;
        font-size: 10px;
        font-weight: bold;
        padding-left: 2px;
    }

    .nota-sewa-jual .main-table {
        width: 100%;
        border-collapse: collapse;
    }

    .nota-sewa-jual .main-table th,
    .nota-sewa-jual .main-table td {
        border: 1px solid #0f386b;
        padding: 5px 6px;
        font-size: 10px;
        vertical-align: middle;
    }

    .nota-sewa-jual .main-table th {
        font-weight: bold;
        text-align: center;
        text-transform: uppercase;
        font-size: 10px;
        letter-spacing: 0.5px;
    }

    .nota-sewa-jual .col-banyaknya { width: 12%; text-align: center; }
    .nota-sewa-jual .col-nama { width: 48%; }
    .nota-sewa-jual .col-harga { width: 20%; text-align: center; }
    .nota-sewa-jual .col-jumlah { width: 20%; text-align: center; }

    .nota-sewa-jual .text-center { text-align: center; }
    .nota-sewa-jual .text-right { text-align: right; }

    .nota-sewa-jual .item-row { height: 22px; }
    .nota-sewa-jual .empty-row td { height: 22px; }

    .nota-sewa-jual .bottom-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: -1px;
    }

    .nota-sewa-jual .bottom-left-cell {
        vertical-align: top;
        width: 60%;
        border: none;
        padding: 10px 10px 0 0;
    }

    .nota-sewa-jual .bottom-right-cell {
        vertical-align: top;
        width: 40%;
        padding: 0;
        border: none;
    }

    .nota-sewa-jual .period-text {
        font-size: 10px;
        font-weight: bold;
        margin-bottom: 8px;
    }

    .nota-sewa-jual .period-line {
        display: inline-block;
        border-bottom: 1px dotted #0f386b;
        min-width: 100px;
        text-align: center;
    }

    .nota-sewa-jual .bank-info {
        font-size: 8.5px;
        margin-bottom: 8px;
        line-height: 1.3;
    }

    .nota-sewa-jual .footer-note {
        font-size: 8.5px;
        line-height: 1.3;
        font-style: italic;
        margin-bottom: 25px;
    }

    .nota-sewa-jual .penerima-box {
        font-size: 10px;
        font-weight: bold;
        text-align: left;
    }

    .nota-sewa-jual .penerima-space {
        height: 40px;
    }

    .nota-sewa-jual .summary-table {
        width: 100%;
        border-collapse: collapse;
    }

    .nota-sewa-jual .summary-table td {
        border: 1px solid #0f386b;
        padding: 4px 6px;
        font-size: 9.5px;
        font-weight: bold;
        height: 22px;
    }

    .nota-sewa-jual .summary-label {
        width: 50%;
        text-align: left;
    }

    .nota-sewa-jual .summary-value {
        width: 50%;
        text-align: right;
    }

    .nota-sewa-jual .total-row td {
        font-size: 10.5px;
        font-weight: bold;
    }
</style>

<div class="nota-sewa-jual">

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