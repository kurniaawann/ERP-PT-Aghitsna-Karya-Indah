<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Faktur Pembelian</title>
    <style>
        /* Margin halaman diatur di @page; !important wajib karena reset "* { margin: 0 }" ikut menimpa margin halaman di dompdf */
        @page {
            margin: 12mm 10mm !important;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.3;
        }

        /* Header Section */
        .header {
            text-align: center;
            margin-bottom: 12px;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
        }

        .header h1 {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .header p {
            font-size: 11pt;
            margin-bottom: 2px;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            margin-bottom: 10px;
        }

        thead {
            display: table-header-group;
            background-color: #1F4E78;
            color: white;
        }

        tr {
            page-break-inside: avoid;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px 5px;
            text-align: left;
            vertical-align: middle;
        }

        th {
            font-size: 10pt;
            font-weight: bold;
            text-align: center;
        }

        td {
            font-size: 10pt;
        }

        .text-center {
            text-align: center;
        }

        .currency {
            text-align: right;
        }

        /* Nominal "Rp. 50.000" tidak dipecah ke baris baru */
        .nowrap {
            white-space: nowrap;
        }

        /* Footer */
        .footer {
            margin-top: 16px;
            text-align: center;
            font-size: 9pt;
            border-top: 1px solid #000;
            padding-top: 8px;
        }
    </style>
</head>

<body>
    {{-- ==================== Header ==================== --}}
    <div class="header">
        <h1>DAFTAR FAKTUR PEMBELIAN</h1>
        <p>PT Aghitsna Karya Indah</p>
        <p>Tanggal Cetak: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    {{-- ==================== Tabel Data ==================== --}}
    <table>
        <thead>
            <tr>
                <th style="width: 4%;">NO</th>
                <th style="width: 8%;">TANGGAL</th>
                <th style="width: 12%;">NAMA MATERIAL</th>
                <th style="width: 14%;">NPWP</th>
                <th style="width: 15%;">KODE NOMOR SERI PAJAK</th>
                <th style="width: 14%;">NAMA BARANG</th>
                <th style="width: 10%;">HARGA JUAL</th>
                <th style="width: 9%;">PPN PAJAK</th>
                <th style="width: 14%;">KETERANGAN</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $index => $invoice)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $invoice->date->format('d/m/Y') }}</td>
                    <td>{{ $invoice->material_name }}</td>
                    <td class="text-center">{{ $invoice->npwp }}</td>
                    <td class="text-center">{{ $invoice->tax_number_code }}</td>
                    {{-- Revisi klien: Nama Barang, Harga Jual, PPN Pajak rata tengah --}}
                    <td class="text-center">{{ $invoice->item_name }}</td>
                    <td class="text-center nowrap">{{ format_rupiah($invoice->selling_price) }}</td>
                    <td class="text-center nowrap">{{ format_rupiah($invoice->ppn_tax) }}</td>
                    <td>{{ $invoice->notes ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data faktur pembelian</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ==================== Footer ==================== --}}
    <div class="footer">
        <p>Dokumen ini dicetak dari sistem ERP PT Aghitsna Karya Indah</p>
    </div>
</body>

</html>
