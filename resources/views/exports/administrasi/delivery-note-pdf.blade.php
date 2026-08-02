<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Jalan - PT. Aghitsna Karya Indah</title>
    <style>
        @page {
            size: A4;
            margin: 0.8cm 1cm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #111;
            background: #fff;
        }

        .page {
            /* width: 100%;
            padding: 0; */
            margin: 20px;
        }

        .page-break {
            page-break-after: always;
        }

        /* TABLE LAYOUT FIXED AGAR SEJAJAR 100% */
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        /* HEADER SECTION */
        .header-table {
            margin-bottom: 12px;
        }

        .header-table td {
            /* border-bottom: 2px solid #000; */
            padding-bottom: 8px;
        }

        /* Kolom Kiri: Logo */
        .header-col-left {
            width: 30%;
            vertical-align: middle;
            text-align: left;
        }

        .company-logo {
            max-width: 150px;
            height: auto;
            display: block;
        }

        /* Kolom Tengah: Judul Surat Jalan */
        .header-col-center {
            width: 35%;
            vertical-align: middle;
            text-align: center;
        }

        .document-title {
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* Kolom Kanan: Detail Info Dokumen */
        .header-col-right {
            width: 35%;
            vertical-align: middle;
            text-align: right;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 1.5px 0;
            font-size: 9.5px;
            vertical-align: top;
            border-bottom: none;
        }

        .meta-label {
            font-weight: bold;
            width: 48%;
            text-align: left;
            white-space: nowrap;
        }

        .meta-value {
            width: 52%;
            text-align: left;
            padding-left: 2px;
        }

        /* PARTIES SECTION (DARI & KEPADA) */
        .parties-table {
            margin-bottom: 12px;
        }

        .party-box {
            vertical-align: top;
            width: 50%;
            padding: 8px;
            border: 1px solid #000;
            background-color: #fafafa;
        }

        .party-box.left {
            border-right: none;
        }

        .party-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #ccc;
            padding-bottom: 3px;
            margin-bottom: 5px;
            color: #333;
        }

        .party-name {
            font-size: 10.5px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .party-address {
            font-size: 9.5px;
            line-height: 1.35;
        }

        /* DESKRIPSI (TANPA BORDER) */
        .description-box {
            width: 100%;
            border: none;
            padding: 4px 0;
            margin-bottom: 8px;
            font-size: 9.5px;
            background-color: transparent;
        }

        /* ITEMS TABLE */
        .items-table {
            margin-bottom: 12px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 6px 7px;
            font-size: 9.5px;
            vertical-align: middle;
        }

        .items-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.5px;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .item-row {
            height: 24px;
        }

        /* CATATAN TAMBAHAN */
        .info-section {
            margin-bottom: 15px;
        }

        .info-cell {
            vertical-align: top;
            width: 100%;
            font-size: 9px;
        }

        .info-title {
            font-weight: bold;
            margin-bottom: 3px;
        }

        .info-content {
            border: 1px dashed #777;
            padding: 6px;
            background-color: #fff;
            line-height: 1.35;
        }

        /* SIGNATURE SECTION (TANPA GARIS DI ATAS) */
        .signature-table {
            margin-top: 15px;
        }

        .signature-cell {
            vertical-align: top;
            width: 50%;
            text-align: center;
            font-size: 10px;
        }

        .signature-title {
            font-weight: bold;
            margin-bottom: 55px;
        }

        .signature-line {
            font-weight: bold;
            display: inline-block;
        }

        /* FOOTER */
        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 4px;
        }
    </style>
</head>

<body>
    @foreach ($deliveryNotes as $deliveryNote)
        <div class="page @if (!$loop->last) page-break @endif">
            
            {{-- HEADER SECTION --}}
            <table class="header-table">
                <tr>
                    <!-- KIRI: Logo Saja -->
                    <td class="header-col-left">
                        <img src="{{ public_path('images/logo.jpeg') }}" alt="Logo" class="company-logo">
                    </td>

                    <!-- TENGAH: Judul Dokumen -->
                    <td class="header-col-center">
                        <div class="document-title">SURAT JALAN</div>
                    </td>

                    <!-- KANAN: Detail Informasi -->
                    <td class="header-col-right">
                        <table class="meta-table">
                            <tr>
                                <td class="meta-label">No. Surat Jalan</td>
                                <td class="meta-value">: {{ $deliveryNote->document_number ?? $deliveryNote->id_delivery_note }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">Tanggal</td>
                                <td class="meta-value">: {{ \Carbon\Carbon::parse($deliveryNote->delivery_date)->format('d F Y') }}</td>
                            </tr>
                            @if ($deliveryNote->vehicle_number)
                            <tr>
                                <td class="meta-label">No. Kendaraan</td>
                                <td class="meta-value">: {{ $deliveryNote->vehicle_number }}</td>
                            </tr>
                            @endif
                            @if ($deliveryNote->driver_name)
                            <tr>
                                <td class="meta-label">Sopir</td>
                                <td class="meta-value">: {{ $deliveryNote->driver_name }}</td>
                            </tr>
                            @endif
                        </table>
                    </td>
                </tr>
            </table>

            {{-- SHIPPER & RECEIVER SECTION --}}
            <table class="parties-table">
                <tr>
                    <td class="party-box left">
                        <div class="party-title">DARI (Pengirim)</div>
                        <div class="party-name">{{ $deliveryNote->shipper_name }}</div>
                        <div class="party-address">{{ $deliveryNote->shipper_address }}</div>
                    </td>
                    <td class="party-box">
                        <div class="party-title">KEPADA YTH (Penerima)</div>
                        <div class="party-name">{{ $deliveryNote->receiver_name }}</div>
                        <div class="party-address">{{ $deliveryNote->receiver_address }}</div>
                    </td>
                </tr>
            </table>

            {{-- DESKRIPSI (TANPA BORDER) --}}
            @if ($deliveryNote->description)
                <div class="description-box">
                    <strong>Deskripsi:</strong> {{ $deliveryNote->description }}
                </div>
            @endif

            {{-- TABEL BARANG --}}
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 6%;">NO</th>
                        <th style="width: 48%;">NAMA BARANG / DESKRIPSI</th>
                        <th style="width: 12%;">JUMLAH</th>
                        <th style="width: 12%;">SATUAN</th>
                        <th style="width: 22%;">CATATAN</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($deliveryNote->items as $item)
                        <tr class="item-row">
                            <td class="text-center">{{ $item['no'] ?? $loop->iteration }}</td>
                            <td>{{ data_get($item, 'item_name', data_get($item, 'name', '-')) }}</td>
                            <td class="text-center"><strong>{{ $item['quantity'] }}</strong></td>
                            <td class="text-center">{{ $item['unit'] ?? 'pcs' }}</td>
                            <td>{{ $item['notes'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">Tidak ada barang yang terdaftar</td>
                        </tr>
                    @endforelse

                    {{-- Baris pelengkap grid jika jumlah barang sedikit --}}
                    @for ($i = count($deliveryNote->items ?? []); $i < 5; $i++)
                        <tr class="item-row">
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    @endfor
                </tbody>
            </table>

            {{-- CATATAN TAMBAHAN (JIKA ADA) --}}
            @if ($deliveryNote->notes)
                <table class="info-section">
                    <tr>
                        <td class="info-cell">
                            <div class="info-title">Catatan Tambahan:</div>
                            <div class="info-content">{{ $deliveryNote->notes }}</div>
                        </td>
                    </tr>
                </table>
            @endif

            {{-- TANDA TANGAN (2 KOLOM: PENGIRIM & PENERIMA TANPA GARIS ATAS) --}}
            <table class="signature-table">
                <tr>
                    <td class="signature-cell">
                        <div class="signature-title">Pengirim</div>
                        <div class="signature-line">( ____________________ )</div>
                    </td>
                    <td class="signature-cell">
                        <div class="signature-title">Penerima</div>
                        <div class="signature-line">( ____________________ )</div>
                    </td>
                </tr>
            </table>

            {{-- FOOTER --}}
            <div class="footer">
                Surat Jalan ini merupakan bukti resmi penerimaan barang. Harap diperiksa dan ditandatangani saat barang diterima.
            </div>

        </div>
    @endforeach
</body>

</html>