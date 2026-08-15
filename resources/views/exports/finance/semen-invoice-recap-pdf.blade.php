<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Invoice Semen</title>
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
        <h1>LAPORAN REKAP INVOICE SEMEN</h1>
        <h2>PT AGHITSNA KARYA INDAH</h2>
        <p>Periode:
            @php
                $month = request('month') ? \Carbon\Carbon::create()->month((int) request('month'))->locale('id')->translatedFormat('F') : '';
                $year = request('year');
            @endphp
            <strong>{{ strtoupper(trim($month . ' ' . $year)) ?: 'SEMUA' }}</strong>
        </p>
        <p>Tanggal Cetak: {{ date('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 16%;">No Invoice</th>
                <th style="width: 10%;">Tanggal</th>
                <th style="width: 45%;">Nama Proyek</th>
                <th style="width: 10%;">Jml Proyek</th>
                <th style="width: 15%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalAmount = 0;
            @endphp
            @forelse($invoices as $index => $invoice)
                @php
                    $totalAmount += (int) ($invoice->total_amount ?? 0);
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $invoice->invoice_number }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}</td>
                    <td class="text-left">{{ $invoice->nama_proyek_list ?: '-' }}</td>
                    <td class="text-center">{{ $invoice->proyek_count }}</td>
                    <td class="text-right">Rp {{ number_format((int) ($invoice->total_amount ?? 0), 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">Tidak ada data rekap invoice semen</td>
                </tr>
            @endforelse

            @if ($invoices->count() > 0)
                <tr class="total-row">
                    <td colspan="5" class="text-center">TOTAL</td>
                    <td class="text-right">Rp {{ number_format($totalAmount, 0, ',', '.') }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="summary">
        <h3>Ringkasan:</h3>
        <p>Total Data: {{ $invoices->count() }} invoice</p>
        <p>Total Invoice: Rp {{ number_format($totalAmount, 0, ',', '.') }}</p>
    </div>
</body>

</html>