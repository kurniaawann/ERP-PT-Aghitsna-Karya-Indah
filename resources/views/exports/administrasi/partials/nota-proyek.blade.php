<style>
    .nota-proyek {
        font-family: 'Arial', 'Helvetica', sans-serif;
        font-size: 10px;
        color: #000;
        background: #fff;
        margin: 15px;
    }

    /* HEADER */
    .nota-proyek .header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }

    .nota-proyek .header-left {
        vertical-align: bottom;
        width: 40%;
        font-size: 11px;
        font-weight: bold;
    }

    .nota-proyek .header-right {
        vertical-align: top;
        width: 60%;
        text-align: right;
        font-size: 11px;
    }

    .nota-proyek .header-date {
        margin-bottom: 8px;
    }

    .nota-proyek .header-project-name {
        font-weight: bold;
        line-height: 1.3;
    }

    /* MAIN TABLE */
    .nota-proyek .main-table {
        width: 100%;
        border-collapse: collapse;
        border-left: 1px solid; /* Garis tebal oranye/merah kiri tabel */
    }

    .nota-proyek .main-table th,
    .nota-proyek .main-table td {
        border-top: 1px solid #000;
        border-right: 1px solid #000;
        border-bottom: 1px solid #000;
        padding: 4px 6px;
        font-size: 10px;
        vertical-align: middle;
    }

    .nota-proyek .main-table th {
        font-weight: bold;
        text-align: center;
        text-transform: uppercase;
        background: #e0e0e0;
    }

    /* Lebar Kolom */
    .nota-proyek .col-qty { width: 10%; text-align: center; }
    .nota-proyek .col-satuan { width: 12%; text-align: center; }
    .nota-proyek .col-nama { width: 38%; }
    .nota-proyek .col-harga { width: 20%; }
    .nota-proyek .col-jumlah { width: 20%; }

    .nota-proyek .text-center { text-align: center; }
    .nota-proyek .text-right { text-align: right; }

    .nota-proyek .price-cell {
        display: table;
        width: 100%;
    }
    .nota-proyek .price-currency {
        display: table-cell;
        text-align: left;
        width: 20px;
    }
    .nota-proyek .price-amount {
        display: table-cell;
        text-align: right;
    }

    .nota-proyek .item-row { height: 20px; }
    .nota-proyek .empty-row td { height: 20px; }

    /* TOTAL BARIS BAWAH TABEL (Hapus border & margin tambahan) */
    .nota-proyek .total-row td {
        border: none !important;
        padding-top: 6px;
        font-weight: bold;
        font-size: 11px;
    }

    /* FOOTER / TANDA TANGAN */
    .nota-proyek .bottom-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 25px;
    }

    .nota-proyek .bottom-left-cell {
        vertical-align: top;
        width: 50%;
        padding: 0;
    }

    .nota-proyek .bottom-right-cell {
        vertical-align: top;
        width: 50%;
        text-align: center;
        padding: 0;
    }

    .nota-proyek .sign-title {
        font-size: 11px;
        font-weight: normal;
        margin-bottom: 50px;
    }

    .nota-proyek .sign-name {
        font-size: 11px;
        font-weight: bold;
    }

    .nota-proyek .sign-divisi {
        font-size: 10px;
        margin-top: 2px;
    }

    .nota-proyek .sign-image {
        display: block;
        margin: 0 auto;
        max-height: 45px;
        max-width: 120px;
        object-fit: contain;
    }
</style>

<div class="nota-proyek">

    <!-- HEADER -->
    <table class="header-table">
        <tr>
            <!-- KIRI: NOTA NO -->
            <td class="header-left">
                NOTA NO. {{ $nota->id_nota ?? '...................' }}
            </td>

            <!-- KANAN: Tanggal & Nama Proyek -->
            <td class="header-right">
                <div class="header-date">
                    {{ \Carbon\Carbon::parse($nota->nota_date)->translatedFormat('d F Y') }}
                </div>
                <div class="header-project-name">
                    {{ $nota->nama_proyek }}<br>
                    @if (!empty($nota->kepada))
                        ({{ $nota->kepada }})
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- TABEL BARANG UTAMA -->
    <table class="main-table">
        <thead>
            <tr>
                <th class="col-qty">QTY</th>
                <th class="col-satuan">SATUAN</th>
                <th class="col-nama">NAMA BARANG</th>
                <th class="col-harga">HARGA</th>
                <th class="col-jumlah">JUMLAH</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($nota->items as $item)
                <tr class="item-row">
                    <td class="col-qty text-center">{{ $item['quantity'] }}</td>
                    <td class="col-satuan text-center">{{ $item['satuan'] ?? '' }}</td>
                    <td class="col-nama">{{ $item['nama_barang'] }}</td>
                    <td class="col-harga">
                        <div class="price-cell">
                            <span class="price-currency">Rp</span>
                            <span class="price-amount">{{ number_format($item['harga'], 0, ',', '.') }}</span>
                        </div>
                    </td>
                    <td class="col-jumlah">
                        <div class="price-cell">
                            <span class="price-currency">Rp</span>
                            <span class="price-amount">{{ number_format($item['jumlah'], 0, ',', '.') }}</span>
                        </div>
                    </td>
                </tr>
            @endforeach

            <!-- Baris Kosong Pelengkap Grid -->
            @for ($i = count($nota->items); $i < 9; $i++)
                <tr class="empty-row">
                    <td class="col-qty"></td>
                    <td class="col-satuan"></td>
                    <td class="col-nama"></td>
                    <td class="col-harga"></td>
                    <td class="col-jumlah"></td>
                </tr>
            @endfor
        </tbody>
    </table>

    <!-- ROW TOTAL BARIS DI BAWAH TABEL (Di Luar Tabel Bergaris Agar Bersih) -->
    <table style="width: 100%; border-collapse: collapse; margin-top: 6px;">
        <tr class="total-row">
            <td style="width: 60%;"></td>
            <td class="text-right" style="width: 20%; padding-right: 15px; font-weight: bold;">Jumlah</td>
            <td style="width: 20%; font-weight: bold;">
                <div class="price-cell">
                    <span class="price-currency">Rp</span>
                    <span class="price-amount">{{ number_format($nota->jumlah_total, 0, ',', '.') }}</span>
                </div>
            </td>
        </tr>
    </table>

    <!-- BAGIAN BAWAH / TANDA TANGAN -->
    <table class="bottom-table">
        <tr>
            <!-- KIRI: Tanda Terima -->
            <td class="bottom-left-cell">
                <div class="sign-title" style="text-align: center; width: 60%;">Tanda Terima</div>
                <div style="height: 40px;"></div>
                <div class="sign-name" style="text-align: center; width: 60%;">{{ $nota->penerima ?? '' }}</div>
            </td>

            <!-- KANAN: Hormat Kami -->
            <td class="bottom-right-cell">
                <div class="sign-title">Hormat Kami</div>
                
                <div style="height: 40px;">
                    @if (!empty($nota->penandatangan['signature_image']))
                        <img src="{{ storage_path('app/public/' . $nota->penandatangan['signature_image']) }}"
                            alt="Tanda Tangan" class="sign-image">
                    @endif
                </div>

                <div class="sign-name">{{ $nota->penandatangan['name'] ?? '' }}</div>
                @if (!empty($nota->penandatangan['divisi']))
                    <div class="sign-divisi">{{ $nota->penandatangan['divisi'] }}</div>
                @endif
            </td>
        </tr>
    </table>

</div>