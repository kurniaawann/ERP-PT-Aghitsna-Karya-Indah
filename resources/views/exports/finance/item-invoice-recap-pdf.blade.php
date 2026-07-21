<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Invoice Item</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10px;
            padding: 18px;
        }

        h1 {
            font-size: 16px;
            margin-bottom: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px 5px;
        }

        th {
            background: #f0f0f0;
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }
    </style>
</head>

<body>
    <h1>Rekap Invoice Item</h1>
    <table>
        <thead>
            <tr>
                <th>No Invoice</th>
                <th>Tanggal</th>
                <th>Kepada</th>
                <th>Keterangan</th>
                <th>Total Penjualan</th>
                <th>Total Modal</th>
                <th>Profit</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->invoice_number }}</td>
                    <td class="center">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d-m-Y') }}</td>
                    <td>{{ $invoice->recipient }}</td>
                    <td>{{ $invoice->project_description }}</td>
                    <td class="right">Rp {{ number_format((int) ($invoice->total_selling ?? 0), 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format((int) ($invoice->total_capital ?? 0), 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format((int) ($invoice->total_profit ?? 0), 0, ',', '.') }}</td>
                    <td class="center">{{ $invoice->status_label }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
