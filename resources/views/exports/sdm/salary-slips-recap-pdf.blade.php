{{--
    Rekap Slip Gaji PDF Export Template (landscape A4)

    Mencetak rekap seluruh slip gaji sesuai filter (bulan/tahun/pencarian)
    pada halaman indeks. Berisi ringkasan per karyawan + total + tanda tangan.
--}}

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Rekap Slip Gaji</title>
    <style>
        @page {
            margin: 0.6cm 0.8cm;
        }

        body {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 9px;
            color: #000;
            line-height: 1.2;
        }

        .header-table {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
        }

        .company-logo {
            height: 42px;
            width: auto;
        }

        .doc-title {
            font-size: 13px;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
        }

        .doc-sub {
            font-size: 9px;
            text-align: center;
            margin-top: 2px;
        }

        .main-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #333;
            margin-bottom: 8px;
        }

        .main-table th {
            background-color: #b0b0b0;
            color: #000;
            font-weight: bold;
            font-size: 8px;
            padding: 5px 3px;
            border: 1px solid #444;
            text-align: center;
            text-transform: uppercase;
        }

        .main-table td {
            padding: 4px 3px;
            border: 1px solid #666;
            font-size: 8.5px;
        }

        .main-table tfoot td {
            background-color: #b0b0b0;
            font-weight: bold;
            border: 1px solid #444;
            font-size: 9px;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }

        .sig-table {
            width: 100%;
            margin-top: 28px;
            border-collapse: collapse;
        }

        .sig-box {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
        }

        .sig-title {
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .sig-space {
            min-height: 45px;
            height: 45px;
        }

        .sig-name {
            font-size: 8.5px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    {{-- KOP --}}
    <table class="header-table">
        <tr>
            <td style="width: 24%;">
                <img src="{{ public_path('images/logo.jpeg') }}" alt="PT. AGHITSNA KARYA INDAH" class="company-logo">
            </td>
            <td style="width: 52%;">
                <div class="doc-title">REKAP SLIP GAJI KARYAWAN BULANAN</div>
                <div class="doc-sub">Periode: {{ $periodText }}</div>
            </td>
            <td style="width: 24%; text-align: right; font-size: 8px;">
                Dicetak: {{ $printedAt->format('d M Y H:i') }}
            </td>
        </tr>
    </table>

    {{-- TABEL REKAP --}}
    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 25px;">NO</th>
                <th style="width: 150px;">NAMA KARYAWAN</th>
                <th style="width: 100px;">JABATAN</th>
                <th>GAJI POKOK</th>
                <th>HADIR</th>
                <th>IZIN</th>
                <th>SAKIT</th>
                <th>CUTI</th>
                <th>ALPHA</th>
                <th>LIBUR</th>
                <th>TOTAL POTONGAN</th>
                <th>THP</th>
                <th style="width: 55px;">STATUS</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($slips as $index => $slip)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-bold">
                        {{ $slip->employee->name ?? '-' }}
                        <span style="font-weight: normal; color: #666;">({{ $slip->employee_code }})</span>
                    </td>
                    <td class="text-center">{{ $slip->employee->position ?? '-' }}</td>
                    <td class="text-right">{{ number_format($slip->base_salary, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $slip->present_days }}</td>
                    <td class="text-center">{{ $slip->permission_days }}</td>
                    <td class="text-center">{{ $slip->sick_days }}</td>
                    <td class="text-center">{{ $slip->leave_days }}</td>
                    <td class="text-center">{{ $slip->absent_days }}</td>
                    <td class="text-center">{{ $slip->libur_days }}</td>
                    <td class="text-right" style="color: #c0392b;">{{ number_format($slip->total_deduction, 0, ',', '.') }}</td>
                    <td class="text-right font-bold">{{ number_format($slip->net_salary, 0, ',', '.') }}</td>
                    <td class="text-center">
                        {{ $slip->status === 'paid' ? 'LUNAS' : 'DRAFT' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-center">TOTAL</td>
                <td class="text-right">{{ number_format($slips->sum('base_salary'), 0, ',', '.') }}</td>
                <td colspan="6"></td>
                <td class="text-right">{{ number_format($slips->sum('total_deduction'), 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($slips->sum('net_salary'), 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    {{-- BLOK TANDA TANGAN --}}
    <table class="sig-table">
        <tr>
            @foreach (['disetujui' => 'DISETUJUI OLEH,', 'diperiksa' => 'DIPERIKSA OLEH,', 'dibuat' => 'DIBUAT OLEH,'] as $roleKey => $roleLabel)
                @php $signatory = $signatures[$roleKey] ?? null; @endphp
                <td class="sig-box">
                    <div class="sig-title">{{ $roleLabel }}</div>
                    <div class="sig-space">
                        @if ($signatory && !empty($signatory['signature_image']))
                            <img src="{{ storage_path('app/public/' . $signatory['signature_image']) }}"
                                alt="Tanda tangan {{ $signatory['name'] ?? '' }}"
                                style="max-height: 40px; max-width: 120px; object-fit: contain;">
                        @endif
                    </div>
                    <div class="sig-name">
                        ( {{ $signatory['name'] ?? '___________________________________________' }} )
                    </div>
                    @if ($signatory && !empty($signatory['position']))
                        <div style="font-size: 7.5px;">{{ $signatory['position'] }}</div>
                    @endif
                </td>
            @endforeach
        </tr>
    </table>

    <div style="text-align: center; margin-top: 22px; font-size: 7.5px; color: #888;">
        Dicetak otomatis oleh Sistem ERP PT. Aghitsna Karya Indah pada {{ date('d/m/Y H:i') }}
    </div>
</body>

</html>
