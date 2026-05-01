<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Note - PT. Aghitsna Karya Indah</title>
    <style>
        /* @page {
            size: A4;
            margin: 1.5cm;
        } */
        /* @page {

            margin: 20mm 15mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        } */

        body {
            font-family: 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }

        .page {
            width: 100%;
            /* min-height: 100vh;
            margin-bottom: 20px; */
        }

        .page-break {
            page-break-after: always;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #000;
        }

        .company-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .document-title {
            font-size: 13px;
            font-weight: bold;
            margin: 12px 0 8px 0;
            text-align: center;
            border-bottom: 1px solid #000;
            padding: 4px 0;
        }

        /* Document Info */
        .doc-info {
            display: table;
            width: 100%;
            margin-bottom: 15px;
            font-size: 10px;
        }

        .doc-info-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .doc-info-right {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: top;
        }

        .info-row {
            display: flex;
            margin-bottom: 3px;
        }

        .info-label {
            width: 100px;
            font-weight: bold;
        }

        .info-value {
            flex: 1;
        }

        /* Shipper & Receiver */
        .parties {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .party {
            display: table-cell;
            width: 50%;
            padding-right: 10px;
            font-size: 10px;
            vertical-align: top;
        }

        .party-title {
            font-weight: bold;
            border-bottom: 1px solid #000;
            margin-bottom: 5px;
            padding-bottom: 3px;
        }

        .party-content {
            line-height: 1.5;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10px;
        }

        .items-table thead {
            background-color: #f5f5f5;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: left;
        }

        .items-table th {
            font-weight: bold;
            text-align: center;
        }

        .items-table td:nth-child(1),
        .items-table td:nth-child(4) {
            text-align: center;
        }

        .items-table td:nth-child(3) {
            text-align: right;
        }

        .items-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Notes Section */
        .notes-section {
            margin-bottom: 15px;
            font-size: 10px;
        }

        .notes-title {
            font-weight: bold;
            margin-bottom: 3px;
        }

        .notes-content {
            border: 1px solid #ccc;
            padding: 5px;
            background-color: #fafafa;
            line-height: 1.4;
        }

        /* Signature Section */
        .signature-section {
            display: table;
            width: 100%;
            margin-top: 30px;
            font-size: 10px;
        }

        /* .signature-col {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 0 10px;
            vertical-align: top;
        } */

        .signature-col {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 0 20px;
            vertical-align: top;
        }

        .signature-col strong {
            display: block;
            margin-top: 30px;
            border-top: 1px solid #000;
            padding-top: 3px;
        }

        .signature-line {
            margin-top: 80px;
            border-top: 1px solid #000;
            padding-top: 3px;
        }

        /* Footer */
        .footer {
            text-align: center;
            font-size: 9px;
            color: #666;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
        }
    </style>
</head>

<body>
    @foreach ($deliveryNotes as $deliveryNote)
        <div class="page @if (!$loop->last) page-break @endif">
            {{-- Header --}}
            <div class="header">
                <div class="company-name">PT. AGHITSNA KARYA INDAH</div>
                <div style="font-size: 10px;">Jalan Andalas Kabupaten Lubuk Linggau Sumatera Barat, Indonesia</div>
            </div>

            {{-- Document Title --}}
            <div class="document-title">SURAT JALAN</div>

            {{-- Document Info --}}
            <div class="doc-info">
                <div class="doc-info-left">
                    <div class="info-row">
                        <span class="info-label">No. Dokumen</span>
                        <span class="info-value">: {{ $deliveryNote->id_delivery_note }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Nomor Dokumen</span>
                        <span class="info-value">: {{ $deliveryNote->document_number }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status</span>
                        <span class="info-value">: {{ $deliveryNote->status_label }}</span>
                    </div>
                </div>
                <div class="doc-info-right">
                    <div class="info-row">
                        <span class="info-label">Tanggal</span>
                        <span class="info-value">:
                            {{ \Carbon\Carbon::parse($deliveryNote->delivery_date)->format('d-m-Y') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Total Jumlah</span>
                        <span class="info-value">: {{ $deliveryNote->total_quantity }}</span>
                    </div>
                </div>
            </div>

            {{-- Shipper & Receiver --}}
            <div class="parties">
                <div class="party">
                    <div class="party-title">DARI (Pengirim)</div>
                    <div class="party-content">
                        <div><strong>{{ $deliveryNote->shipper_name }}</strong></div>
                        <div>{{ $deliveryNote->shipper_address }}</div>
                    </div>
                </div>
                <div class="party">
                    <div class="party-title">KEPADA (Penerima)</div>
                    <div class="party-content">
                        <div><strong>{{ $deliveryNote->receiver_name }}</strong></div>
                        <div>{{ $deliveryNote->receiver_address }}</div>
                    </div>
                </div>
            </div>

            {{-- Description --}}
            @if ($deliveryNote->description)
                <div class="notes-section">
                    <div class="notes-title">Deskripsi:</div>
                    <div class="notes-content">{{ $deliveryNote->description }}</div>
                </div>
            @endif

            {{-- Items Table --}}
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 40%;">Nama Barang</th>
                        <th style="width: 15%;">Jumlah</th>
                        <th style="width: 15%;">Satuan</th>
                        <th style="width: 25%;">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($deliveryNote->items as $item)
                        <tr>
                            <td>{{ $item['no'] }}</td>
                            <td>{{ $item['item_name'] }}</td>
                            <td>{{ $item['quantity'] }}</td>
                            <td>{{ $item['unit'] ?? 'pcs' }}</td>
                            <td>{{ $item['notes'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center;">Tidak ada barang</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Driver & Vehicle Info --}}
            @if ($deliveryNote->driver_name || $deliveryNote->vehicle_number)
                <div class="notes-section">
                    <div class="notes-title">Informasi Pengiriman:</div>
                    <div class="notes-content">
                        @if ($deliveryNote->driver_name)
                            <div><strong>Sopir:</strong> {{ $deliveryNote->driver_name }}</div>
                        @endif
                        @if ($deliveryNote->vehicle_number)
                            <div><strong>No. Kendaraan:</strong> {{ $deliveryNote->vehicle_number }}</div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Additional Notes --}}
            @if ($deliveryNote->notes)
                <div class="notes-section">
                    <div class="notes-title">Catatan Tambahan:</div>
                    <div class="notes-content">{{ $deliveryNote->notes }}</div>
                </div>
            @endif

            {{-- Signature Section --}}

            <div class="signature-section">
                <div class="signature-col">
                    <div>Pengirim</div>
                    <div class="signature-line"></div>
                </div>
                <div class="signature-col">
                    <div>Penerima</div>
                    <div class="signature-line"></div>
                </div>
            </div>

        </div>
    @endforeach
</body>

</html>
