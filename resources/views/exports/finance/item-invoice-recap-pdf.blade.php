<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Invoice Item</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10px;
            padding: 15px;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
        }

        .header h1 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .header h2 {
            font-size: 13px;
            font-weight: normal;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 10px;
            margin-top: 3px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background-color: #4472C4;
            color: white;
            font-weight: bold;
            padding: 8px 4px;
            text-align: center;
            border: 1px solid #000;
            font-size: 9px;
        }

        td {
            padding: 6px 4px;
            border: 1px solid #000;
            font-size: 9px;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .total-row {
            background-color: #E7E6E6;
            font-weight: bold;
        }

        .status-paid {
            background-color: #C6EFCE;
            color: #006100;
            font-weight: bold;
            text-align: center;
        }

        .status-unpaid {
            background-color: #FFF4CC;
            color: #806000;
            font-weight: bold;
            text-align: center;
        }

        .summary {
            margin-top: 15px;
            padding: 10px;
            border: 1px solid #000;
            background-color: #f9f9f9;
        }

        .summary h3 {
            font-size: 11px;
            margin-bottom: 8px;
        }

        .summary p {
            font-size: 10px;
            margin: 3px 0;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>LAPORAN REKAP INVOICE BARANG</h1>
        <h2>PT AGHITSNA KARYA INDAH</h2>
        <p>Periode: <strong>{{ strtoupper($periodTitle) }}</strong></p>
        <p>Tanggal Cetak: {{ date('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 12%;">No Invoice</th>
                <th style="width: 9%;">Tanggal</th>
                <th style="width: 16%;">Kepada</th>
                <th style="width: 15%;">Keterangan</th>
                <th style="width: 11%;">Total Penjualan</th>
                <th style="width: 11%;">Total Modal</th>
                <th style="width: 11%;">Profit</th>
                <th style="width: 11%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $index => $invoice)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $invoice->invoice_number }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}</td>
                    <td class="text-left">{{ $invoice->recipient }}</td>
                    <td class="text-left">{{ $invoice->project_description ?? '-' }}</td>
                    <td class="text-right">Rp {{ number_format((int) ($invoice->total_selling ?? 0), 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format((int) ($invoice->total_capital ?? 0), 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format((int) ($invoice->total_profit ?? 0), 0, ',', '.') }}</td>
                    <td class="status-{{ ($invoice->salesRecap?->status ?? '') === 'Lunas' ? 'paid' : 'unpaid' }}">
                        {{ $invoice->salesRecap?->status ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data rekap invoice barang</td>
                </tr>
            @endforelse

            @if ($invoices->count() > 0)
                <tr class="total-row">
                    <td colspan="5" class="text-center">TOTAL</td>
                    <td class="text-right">Rp {{ number_format($totals->total_invoice ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($invoices->sum(fn($i) => (int) ($i->total_capital ?? 0)), 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($totals->total_profit ?? 0, 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="summary">
        <h3>Ringkasan:</h3>
        <p>Total Data: {{ $totals->invoice_count ?? 0 }} invoice</p>
        <p>Total Invoice: Rp {{ number_format($totals->total_invoice ?? 0, 0, ',', '.') }}</p>
        <p>Total Profit: Rp {{ number_format($totals->total_profit ?? 0, 0, ',', '.') }}</p>
        <p>Lunas: {{ $totals->paid_count ?? 0 }} | Belum Lunas: {{ ($totals->invoice_count ?? 0) - ($totals->paid_count ?? 0) }}</p>
    </div>
</body>

</html>
