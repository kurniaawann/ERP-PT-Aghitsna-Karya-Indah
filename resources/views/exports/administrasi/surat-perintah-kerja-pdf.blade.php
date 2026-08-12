<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Perintah Kerja - PT. Aghitsna Karya Indah</title>
    <style>
        @page {
            size: A4;
            margin: 1cm 1.5cm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.3;
            color: #000;
            background: #fff;
            padding: 20px;
        }

        .page {
            padding: 10px 0;
        }

        .page-break {
            page-break-after: always;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        /* KOP SURAT */
        .kop-table {
            margin-bottom: 5px;
            border-bottom: 3px solid #000;
        }

        .kop-table td {
            padding-bottom: 8px;
            vertical-align: middle;
        }

        .kop-logo {
            width: 25%;
            text-align: center;
        }

        .kop-company {
            width: 75%;
            text-align: center;
        }

        .company-logo {
            max-width: 140px;
            height: auto;
        }

        .company-name {
            font-size: 15pt;
            font-weight: bold;
            color: #d9531e;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .company-address,
        .company-contact {
            font-size: 9.5pt;
            color: #000;
        }

        /* JUDUL SURAT */
        .title-section {
            text-align: center;
            margin: 15px 0 3px;
        }

        .doc-title {
            font-size: 12pt;
            font-weight: bold;
            text-decoration: underline;
            letter-spacing: 0.5px;
        }

        .meta-line {
            text-align: center;
            font-size: 11pt;
            margin-bottom: 15px;
        }

        /* IDENTITAS & PARAGRAF */
        .identitas-table {
            width: 100%;
            margin-bottom: 8px;
        }

        .identitas-table td {
            padding: 2px 0;
            font-size: 11pt;
            vertical-align: top;
        }

        .identitas-label {
            width: 15%;
        }

        .identitas-colon {
            width: 3%;
        }

        .identitas-value {
            width: 82%;
        }

        .content-text {
            font-size: 11pt;
            margin: 8px 0;
            text-align: justify;
        }

        /* TABLE ITEM */
        .items-table {
            margin: 10px 0 15px;
            border: none !important; /* Hapus border terluar tabel utama */
        }

        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 4px 5px;
            font-size: 10pt;
            vertical-align: middle;
        }

        .items-table th {
            background-color: #a6a6a6;
            font-weight: bold;
            text-align: center;
            color: #000;
        }

        .category-header {
            font-weight: bold;
            background-color: #ffffff;
            text-align: left;
            padding-left: 8px !important;
        }

        .item-code {
            font-weight: bold;
            text-align: left;
            padding-left: 8px !important;
        }

        .currency-symbol {
            width: 15%;
            text-align: left;
            border-right: none !important;
        }

        .currency-amount {
            text-align: right;
            border-left: none !important;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }

        /* MODIFIKASI BARIS TOTAL */
        .total-row td {
            font-weight: bold;
        }

        /* AREA KOSONG SEBELAH KIRI (HANYA GARIS ATAS, HILANGKAN GARIS KIRI KELUAR) */
        .total-row td.empty-cells {
            border: none !important;
            border-top: 1px solid #000 !important; /* Menjaga garis horizontal yang ditandai kuning */
            background-color: transparent !important;
        }

        /* KOTAK KUNING TOTAL */
        .total-row td.total-label {
            background-color: #ffc000 !important;
            text-align: left !important;
            padding-left: 10px !important;
            font-size: 11pt;
            border: 1px solid #000 !important;
        }

        .total-row td.total-value-symbol {
            background-color: #ffc000 !important;
            border-top: 1px solid #000 !important;
            border-bottom: 1px solid #000 !important;
            border-left: 1px solid #000 !important;
            border-right: none !important;
        }

        .total-row td.total-value-amount {
            background-color: #ffc000 !important;
            border-top: 1px solid #000 !important;
            border-bottom: 1px solid #000 !important;
            border-right: 1px solid #000 !important;
            border-left: none !important;
            font-size: 11pt;
        }

        /* PARAGRAF PENUTUP */
        .closing-text {
            margin-top: 15px;
            font-size: 11pt;
            text-align: justify;
            /* background-color: #d9ead3; */
            padding: 2px;
        }

        /* TANDA TANGAN */
        .date-section {
            text-align: right;
            margin-top: 15px;
            margin-bottom: 10px;
            font-size: 11pt;
            padding-right: 10%;
        }

        .signature-table {
            width: 100%;
            margin-top: 10px;
        }

        .signature-table td {
            vertical-align: top;
            text-align: center;
            font-size: 11pt;
            width: 50%;
        }

        .signature-space {
            height: 70px;
        }
    </style>
</head>

<body>
    @foreach ($suratPerintahKerjas as $spk)
        <div class="page @if (!$loop->last) page-break @endif">

            {{-- KOP SURAT --}}
            <table class="kop-table">
                <tr>
                    <td class="kop-logo">
                        <img src="{{ public_path('images/logo.jpeg') }}" alt="Logo PT AGHITSNA" class="company-logo">
                    </td>
                    <td class="kop-company">
                        <div class="company-name">PT. AGHITSNA KARYA INDAH</div>
                        <div class="company-address">JL. PERTIWI TANAH BARU RAYA No.36 RT.01/05, BEJI.</div>
                        <div class="company-address">DEPOK, JAWA BARAT</div>
                        <div class="company-contact">Telp. 021-29034923 – 0812.9596.552</div>
                        <div class="company-contact">Email : design@aghitsna.id / Zulkarnainmarzuki@yahoo.com</div>
                    </td>
                </tr>
            </table>

            {{-- JUDUL & NOMOR --}}
            <div class="title-section">
                <div class="doc-title">SURAT PERINTAH KERJA ( SPK )</div>
            </div>
            <div class="meta-line">No. {{ $spk->nomor }}</div>

            {{-- INFO PROYEK & LOKASI --}}
            <table class="identitas-table">
                <tr>
                    <td class="identitas-label">Proyek</td>
                    <td class="identitas-colon">:</td>
                    <td class="identitas-value">{{ $spk->proyek }}</td>
                </tr>
                <tr>
                    <td class="identitas-label">Lokasi</td>
                    <td class="identitas-colon">:</td>
                    <td class="identitas-value">{{ $spk->lokasi }}</td>
                </tr>
            </table>

            <div class="content-text">
                Pada Hari {{ \Carbon\Carbon::parse($spk->tanggal)->locale('id')->isoFormat('dddd') }} ,Tanggal {{ \Carbon\Carbon::parse($spk->tanggal)->format('d') }} Bulan {{ \Carbon\Carbon::parse($spk->tanggal)->locale('id')->isoFormat('MMMM') }} Tahun {{ \Carbon\Carbon::parse($spk->tanggal)->format('Y') }}, yang bertanda tangan dibawah ini :
            </div>

            {{-- IDENTITAS PEMBERI & PENERIMA TUGAS --}}
            <table class="identitas-table">
                <tr>
                    <td class="identitas-label">Nama</td>
                    <td class="identitas-colon">:</td>
                    <td class="identitas-value">{{ $spk->pemberi_tugas_nama }}</td>
                </tr>
                <tr>
                    <td class="identitas-label">Jabatan</td>
                    <td class="identitas-colon">:</td>
                    <td class="identitas-value">{{ $spk->pemberi_tugas_jabatan ?? 'Pelaksana' }}</td>
                </tr>
            </table>

            <div class="content-text">Dalam hal ini disebut <strong>Pemberi Tugas</strong>,</div>

            <table class="identitas-table">
                <tr>
                    <td class="identitas-label">Nama</td>
                    <td class="identitas-colon">:</td>
                    <td class="identitas-value">{{ $spk->penerima_tugas_nama ?? $spk->signer_nama }}</td>
                </tr>
                <tr>
                    <td class="identitas-label">Alamat</td>
                    <td class="identitas-colon">:</td>
                    <td class="identitas-value">{{ $spk->penerima_tugas_alamat ?? $spk->pemberi_tugas_alamat }}</td>
                </tr>
            </table>

            <div class="content-text">Dalam hal ini disebut <strong>Penerima Tugas</strong>,</div>

            <div class="content-text">
                Dengan ini <strong>Pemberi Tugas</strong> menunjuk <strong>Penerima Tugas</strong> untuk melaksanakan pekerjaan dengan ketentuan sebagai berikut :
            </div>

            {{-- TABLE ITEM --}}
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 6%;">No</th>
                        <th style="width: 38%;">Keterangan</th>
                        <th style="width: 10%;">Volume</th>
                        <th style="width: 10%;">Satuan</th>
                        <th colspan="2" style="width: 18%;">Harga</th>
                        <th colspan="2" style="width: 18%;">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($spk->items as $item)
                        {{-- Kategori Utama --}}
                        @if(!empty($item->kategori))
                            <tr>
                                <td colspan="8" class="category-header">{{ $item->kategori }}</td>
                            </tr>
                        @endif

                        {{-- Kode Item --}}
                        <tr>
                            <td class="text-center">{{ data_get($item, 'no', $loop->iteration) }}.</td>
                            <td colspan="7" class="item-code">{{ data_get($item, 'kode') ?? '-' }}</td>
                        </tr>

                        {{-- Detail Sub Item --}}
                        @foreach (data_get($item, 'details', []) as $detail)
                            <tr>
                                <td></td>
                                <td>{{ $detail['keterangan'] ?? '-' }}</td>
                                <td class="text-center">{{ $detail['volume'] }}</td>
                                <td class="text-center">{!! $detail['satuan'] ?? '-' !!}</td>
                                <td class="currency-symbol">Rp</td>
                                <td class="currency-amount">{{ number_format($detail['harga'], 0, ',', '.') }}</td>
                                <td class="currency-symbol">Rp</td>
                                <td class="currency-amount">{{ number_format($detail['jumlah'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">Tidak ada item pekerjaan</td>
                        </tr>
                    @endforelse

                    {{-- TOTAL ROW --}}
                    <tr class="total-row">
                        <!-- Area Sebelah Kiri: Memiliki Garis Atas, Tanpa Garis Kiri -->
                        <td colspan="3" class="empty-cells"></td>
                        
                        <!-- Gabungan Kolom Satuan & Harga untuk kata 'Jumlah' -->
                        <td colspan="3" class="total-label">Jumlah</td>
                        
                        <!-- Kolom Nilai Total -->
                        <td class="currency-symbol total-value-symbol">Rp</td>
                        <td class="currency-amount total-value-amount">{{ number_format($spk->total_amount, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>

            {{-- PARAGRAF PENUTUP --}}
            <div class="closing-text">
                Demikian surat perintah ini dibuat,agar dapat dilaksanakan sebaik-baiknya dengan penuh tanggung jawab, jika nantinya terdapat suatu kondisi diluar ketentuan surat perintah ini maka dibicarakan lebih lanjut.
            </div>

            {{-- TANGGAL SURAT --}}
            <div class="date-section">
                {{ $spk->kota_surat ?? 'Depok' }}, {{ \Carbon\Carbon::parse($spk->tanggal)->locale('id')->isoFormat('D MMMM YYYY') }}
            </div>

            {{-- TANDA TANGAN --}}
            <table class="signature-table">
                <tr>
                    <td>Pemberi Tugas</td>
                    <td>Penerima Tugas</td>
                </tr>
                <tr>
                    <td class="signature-space"></td>
                    <td class="signature-space"></td>
                </tr>
                <tr>
                    <td>( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )</td>
                    <td>( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )</td>
                </tr>
                <tr>
                    <td style="padding-top: 5px;">Pelaksana</td>
                    <td></td>
                </tr>
            </table>

        </div>
    @endforeach
</body>

</html>