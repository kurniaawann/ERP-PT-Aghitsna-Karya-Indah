<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Proyek</title>
    <style>
        * {
            margin: 0;
            padding: 0;
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

        .payment-list span {
            display: inline-block;
            margin: 0 3px 3px 0;
            padding: 2px 6px;
            background: #EEE1FF;
            border: 1px solid #C6A7FF;
            border-radius: 999px;
        }
    </style>
</head>

<body>
    {{-- ==================== Header Laporan ==================== --}}
    <div class="header">
        <h1>LAPORAN REKAP INVOICE PROYEK</h1>
        <h2>PT AGHITSNA KARYA INDAH</h2>
        <p>Periode: <strong>{{ strtoupper($periodTitle) }}</strong></p>
        <p>Tanggal Cetak: {{ date('d/m/Y H:i') }}</p>
    </div>

    {{-- ==================== Tabel Rekap ==================== --}}
    <table>
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 12%;">No Invoice</th>
                <th style="width: 9%;">Tanggal</th>
                <th style="width: 16%;">Kepada</th>
                <th style="width: 20%;">Proyek</th>
                <th style="width: 11%;">Total</th>
                <th style="width: 11%;">Dibayar</th>
                <th style="width: 11%;">Sisa</th>
                <th style="width: 12%;">Pembayaran Ke</th>
                <th style="width: 10%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $index => $invoice)
                @php
                    $paymentInstallments = $invoice->payment_installments ?? [];
                    $paymentLabels = collect($paymentInstallments)
                        ->map(fn($payment) => $payment['label'] ?? null)
                        ->filter()
                        ->values();
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $invoice->invoice_number }}</td>
                    <td class="text-center">{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                    <td class="text-left">{{ $invoice->recipient }}</td>
                    <td class="text-left">{{ $invoice->project_description ?? '-' }}</td>
                    <td class="text-right">Rp {{ number_format($invoice->getNetAmount(), 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($invoice->getTotalPaidAmount(), 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($invoice->getRemainingAmount(), 0, ',', '.') }}</td>
                    <td class="text-left payment-list">
                        @if ($paymentLabels->isNotEmpty())
                            @foreach ($paymentLabels as $label)
                                <span>{{ $label }}</span>
                            @endforeach
                        @else
                            -
                        @endif
                    </td>
                    <td class="status-{{ $invoice->isFullyPaid() ? 'paid' : 'unpaid' }}">
                        {{ $invoice->payment_status_label }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">Tidak ada data rekap proyek</td>
                </tr>
            @endforelse

            {{-- ==================== Baris Total ==================== --}}
            @if ($invoices->count() > 0)
                <tr class="total-row">
                    <td colspan="5" class="text-center">TOTAL</td>
                    <td class="text-right">Rp {{ number_format($totals->total_invoice ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($totals->total_paid ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($totals->total_remaining ?? 0, 0, ',', '.') }}</td>
                    <td></td>
                    <td></td>
                </tr>
            @endif
        </tbody>
    </table>

    {{-- ==================== Ringkasan ==================== --}}
    <div class="summary">
        <h3>Ringkasan:</h3>
        <p>Total Data: {{ $totals->invoice_count ?? 0 }} invoice</p>
        <p>Total Invoice: Rp {{ number_format($totals->total_invoice ?? 0, 0, ',', '.') }}</p>
        <p>Total Dibayar: Rp {{ number_format($totals->total_paid ?? 0, 0, ',', '.') }}</p>
        <p>Total Sisa: Rp {{ number_format($totals->total_remaining ?? 0, 0, ',', '.') }}</p>
        <p>Lunas: {{ $totals->paid_count ?? 0 }} | Belum Lunas: {{ $totals->unpaid_count ?? 0 }}</p>
    </div>
</body>

</html>
