<!DOCTYPE html>
<html lang="id" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">

<head>
    <meta charset="UTF-8">
    <title>Surat Perintah Kerja - PT. Aghitsna Karya Indah</title>
    <!--[if gte mso 9]><xml><w:WordDocument><w:View>Print</w:View><w:Zoom>100</w:Zoom></w:WordDocument></xml><![endif]-->
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #111;
        }

        .kop-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #0f386b;
        }

        .kop-company {
            text-align: center;
            vertical-align: middle;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f386b;
        }

        .company-address {
            font-size: 9px;
        }

        .company-contact {
            font-size: 9px;
        }

        .title-section {
            text-align: center;
            margin: 18px 0 14px;
        }

        .doc-title {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
        }

        .doc-subtitle {
            font-size: 11px;
        }

        .meta-line {
            text-align: center;
            font-size: 11px;
            margin-bottom: 16px;
        }

        .content {
            font-size: 11px;
            text-align: justify;
            margin-bottom: 10px;
        }

        .identitas {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        .identitas td {
            padding: 2px 0;
            font-size: 11px;
            vertical-align: top;
        }

        .identitas-label {
            width: 20%;
            font-weight: bold;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 14px 0;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 6px 7px;
            font-size: 10px;
            vertical-align: middle;
        }

        .items-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .total-row td {
            font-weight: bold;
        }

        .signature {
            margin-top: 60px;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signature-table td {
            text-align: center;
            font-size: 11px;
            vertical-align: top;
            width: 33%;
        }
    </style>
</head>

<body>
    @foreach ($suratPerintahKerjas as $spk)
        @if (!$loop->first)
            <br clear="all" style="page-break-before: always;">
        @endif

        {{-- KOP SURAT --}}
        <table class="kop-table">
            <tr>
                <td class="kop-company">
                    <div class="company-name">PT. Aghitsna Karya Indah</div>
                    <div class="company-address">Jl. Contoh Alamat Perusahaan No. 123, Bandung, Jawa Barat</div>
                    <div class="company-contact">Telp: (022) 1234567 | Email: info@aghitsnakaryaindah.co.id</div>
                </td>
            </tr>
        </table>

        {{-- JUDUL --}}
        <div class="title-section">
            <div class="doc-title">Surat Perintah Kerja</div>
            <div class="doc-subtitle">(SPK)</div>
        </div>

        {{-- NOMOR --}}
        <div class="meta-line">Nomor: <strong>{{ $spk->nomor }}</strong></div>

        {{-- ISI --}}
        <div class="content">
            <p>Yang bertanda tangan di bawah ini, selaku Pemberi Tugas, dengan ini memerintahkan kepada:</p>

            <table class="identitas">
                <tr>
                    <td class="identitas-label">Nama</td>
                    <td>: {{ $spk->pemberi_tugas_nama }}</td>
                </tr>
                <tr>
                    <td class="identitas-label">Alamat</td>
                    <td>: {{ $spk->pemberi_tugas_alamat }}</td>
                </tr>
                <tr>
                    <td class="identitas-label">Proyek</td>
                    <td>: {{ $spk->proyek }}</td>
                </tr>
                <tr>
                    <td class="identitas-label">Lokasi</td>
                    <td>: {{ $spk->lokasi }}</td>
                </tr>
                <tr>
                    <td class="identitas-label">Tanggal</td>
                    <td>: {{ \Carbon\Carbon::parse($spk->tanggal)->format('d F Y') }}</td>
                </tr>
            </table>

            <p>untuk melaksanakan pekerjaan sebagaimana diuraikan pada tabel berikut ini:</p>
        </div>

        {{-- TABLE ITEM --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 12%;">Kode</th>
                    <th style="width: 33%;">Keterangan</th>
                    <th style="width: 9%;">Volume</th>
                    <th style="width: 9%;">Satuan</th>
                    <th style="width: 14%;">Harga</th>
                    <th style="width: 18%;">Jumlah</th>
                </tr>
            </thead>
                <tbody>
                    @forelse ($spk->items as $item)
                        @foreach (data_get($item, 'details', []) as $detail)
                            <tr>
                                <td class="text-center">{{ data_get($item, 'no', $loop->parent->iteration) }}</td>
                                <td class="text-center">{{ data_get($item, 'kode') ?? '-' }}</td>
                                <td>{{ $detail['keterangan'] ?? '-' }}</td>
                                <td class="text-center">{{ $detail['volume'] }}</td>
                                <td class="text-center">{{ $detail['satuan'] ?? '-' }}</td>
                                <td class="text-right">{{ number_format($detail['harga'], 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($detail['jumlah'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">Tidak ada item pekerjaan</td>
                        </tr>
                    @endforelse
                <tr class="total-row">
                    <td colspan="6" class="text-right">TOTAL</td>
                    <td class="text-right">{{ number_format($spk->total_amount, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        {{-- TANDA TANGAN --}}
        <div class="signature">
            <table class="signature-table">
                <tr>
                    <td><strong>MENGETAHUI / PEMBERI TUGAS</strong></td>
                    <td></td>
                    <td><strong>YANG BERTANDA TANGAN</strong></td>
                </tr>
                <tr>
                    <td><br><br><br><br>( {{ $spk->pemberi_tugas_nama }} )</td>
                    <td></td>
                    <td><br><br><br><br>( {{ $spk->signer_nama }} )</td>
                </tr>
                <tr>
                    <td style="font-size: 9px;">Alamat: {{ $spk->pemberi_tugas_alamat }}</td>
                    <td></td>
                    <td style="font-size: 9px;">{{ $spk->signer_jabatan }}</td>
                </tr>
            </table>
        </div>
    @endforeach
</body>

</html>
