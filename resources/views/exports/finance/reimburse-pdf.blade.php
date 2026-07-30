<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Reimbursement</title>
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
            margin-bottom: 10px;
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

        tr.total-row {
            background-color: #E7E6E6;
            font-weight: bold;
        }

        .status-approved {
            background-color: #C6EFCE;
            color: #006100;
            font-weight: bold;
            text-align: center;
        }

        .status-draft {
            background-color: #FFF4CC;
            color: #806000;
            font-weight: bold;
            text-align: center;
        }

        .status-rejected {
            background-color: #FFC7CE;
            color: #9C0006;
            font-weight: bold;
            text-align: center;
        }

        .footer {
            margin-top: 15px;
            font-size: 8px;
            color: #666;
            text-align: center;
        }

        .summary-section {
            margin-top: 15px;
            padding: 10px;
            border: 1px solid #000;
            background-color: #f9f9f9;
        }

        .summary-section h3 {
            font-size: 11px;
            margin-bottom: 8px;
        }

        .summary-section p {
            font-size: 10px;
            margin: 3px 0;
        }
    </style>
</head>

<body>
    {{-- ─── Header Laporan ───────────────────────────────────────────────────── --}}
    <div class="header">
        <h1>LAPORAN REIMBURSEMENT</h1>
        <h2>PT AGHITSNA KARYA INDAH</h2>
        @if ($status)
            <p>Status: <strong>{{ strtoupper($status) }}</strong></p>
        @else
            <p>Status: <strong>SEMUA</strong></p>
        @endif
        <p>Tanggal Cetak: {{ date('d/m/Y H:i') }}</p>
    </div>

    {{-- ─── Tabel Data ───────────────────────────────────────────────────────── --}}
    <table>
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 8%;">Kode</th>
                <th style="width: 8%;">Tanggal</th>
                <th style="width: 15%;">Nama Proyek</th>
                <th style="width: 20%;">Keterangan Belanja</th>
                <th style="width: 12%;">Total</th>
                <th style="width: 8%;">Tgl Jatuh Tempo</th>
                <th style="width: 8%;">Status</th>
                <th style="width: 10%;">Tgl Perubahan</th>
                <th style="width: 12%;">Catatan</th>
            </tr>
        </thead>
        <tbody>
            @php
                $no = 1;
            @endphp
            @forelse($reimburses as $reimburse)
                <tr>
                    <td class="text-center">{{ $no++ }}</td>
                    <td class="text-center">{{ $reimburse->reimburse_code }}</td>
                    <td class="text-center">{{ $reimburse->formatted_date }}</td>
                    <td class="text-left">{{ $reimburse->project_name }}</td>
                    <td class="text-left">{{ $reimburse->expense_description }}</td>
                    <td class="text-right">{{ $reimburse->formatted_total_amount }}</td>
                    <td class="text-center">{{ $reimburse->formatted_due_date }}</td>
                    <td class="status-{{ $reimburse->status }}">{{ strtoupper($reimburse->status_label) }}</td>
                    <td class="text-center">{{ $reimburse->formatted_status_changed_at }}</td>
                    <td class="text-left">{{ $reimburse->notes ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">Tidak ada data reimbursement</td>
                </tr>
            @endforelse

            @if ($reimburses->count() > 0)
                <tr class="total-row">
                    <td colspan="5" class="text-center">TOTAL</td>
                    <td class="text-right">Rp {{ number_format($totalAmount, 0, ',', '.') }}</td>
                    <td colspan="4"></td>
                </tr>
            @endif
        </tbody>
    </table>

    {{-- ─── Ringkasan Status ────────────────────────────────────────────────── --}}
    <div class="summary-section">
        <h3>Ringkasan:</h3>
        <p>Total Data: {{ $reimburses->count() }} reimburse</p>
        <p>Total Amount: Rp {{ number_format($totalAmount, 0, ',', '.') }}</p>
        <p>Draft: {{ $draftCount }} | Disetujui: {{ $approvedCount }} | Ditolak: {{ $rejectedCount }}</p>
    </div>

    {{-- ─── Footer ───────────────────────────────────────────────────────────── --}}
    <div class="footer">
        <p>Dokumen ini dibuat secara otomatis oleh sistem ERP PT Aghitsna Karya Indah</p>
    </div>
</body>

</html>
