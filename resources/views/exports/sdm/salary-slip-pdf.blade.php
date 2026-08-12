<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="UTF-8">
<title>Slip Gaji - {{ ($slips->first() ?? $slip)->employee->name ?? ($slips->first() ?? $slip)->employee_code }}</title>
<style>
@page {
    size: A4 landscape;
    margin: 0.8cm;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11px;
    color: #000;
    line-height: 1.3;
    background-color: #fff;
}

.slip-page {
    border: 2px solid #000;
    padding: 0;
    page-break-inside: avoid;
    box-sizing: border-box;
}

/* ── HEADER ── */
.header-table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
    border-bottom: 2px solid #000;
    margin-bottom: 8px;
}

.header-table td {
    vertical-align: middle;
    padding: 12px 15px 10px 15px;
}

.company-logo {
    max-height: 45px;
    width: auto;
}

.company-name {
    font-size: 15px;
    font-weight: bold;
    letter-spacing: 0.5px;
}

.company-tagline {
    font-size: 11px;
    color: #333;
}

.date-text {
    text-align: right;
    font-size: 11px;
}

/* ── JUDUL ── */
.slip-title {
    text-align: center;
    font-weight: bold;
    font-size: 15px;
    margin: 8px 15px 12px 15px;
    letter-spacing: 1px;
}

/* ── DATA KARYAWAN ── */
.info-table {
    width: 36%;
    border-collapse: collapse;
    border-spacing: 0;
    margin: 0 15px 12px 0px;
}

.info-table td {
    padding: 2px 0;
    font-size: 11px;
    vertical-align: top;
    border: none !important;
}

/* ── TABEL UTAMA ── */
.main-table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
    table-layout: fixed;
    border: none !important;
}

/* Header Tabel */
.main-table thead tr th {
    border-top: 1px solid #000 !important;
    border-bottom: 1px solid #000 !important;
    border-left: none !important;
    border-right: none !important;
    padding: 6px 8px;
    font-size: 11px;
    font-weight: bold;
    text-align: left;
    box-sizing: border-box;
}

/* Sel Isi Tabel */
.main-table tbody td {
    border: none !important;
    padding: 4px 8px;
    font-size: 11px;
    box-sizing: border-box;
}

/* Margins Sisi Kiri & Kanan Tabel Utama */
/* .main-table th:first-child,
.main-table td:first-child {
    padding-left: 15px !important;
}

.main-table th:last-child,
.main-table td:last-child {
    padding-right: 15px !important;
} */

.main-table th:first-child {
    padding-left: 15px !important;
}

.main-table td:first-child {
    padding-left: 2px !important;
}
/* .main-table td:first-child .inner-table {
    margin-left: -15px;
    width: calc(100% + 15px);
} */

/* Sub-tabel Dalam Kolom */
.inner-table,
.inner-table tr,
.inner-table td {
    border: none !important;
    border-style: none !important;
    outline: none !important;
    padding: 0 !important;
    font-size: 11px;
    vertical-align: top;
}

.inner-table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
}

.bg-yellow-header {
    background-color: #ffff00 !important;
    text-align: center !important;
    font-weight: bold;
}

.text-blue-header {
    color: #0066cc;
    text-align: center !important;
    font-weight: bold;
}

/* Kotak Total Dibayar Perusahaan */
.bg-yellow-total {
    background-color: #ffff00 !important;
    text-align: center;
    font-weight: bold;
    border-top: 1px solid #000 !important;
    border-bottom: none !important;
}

.red-text {
    color: red;
}

.red-italic {
    color: red;
    font-style: italic;
}

.text-left {
    text-align: left;
}

.text-right {
    text-align: right;
}

.text-center {
    text-align: center;
}

.colon-right {
    float: right;
}

/* ── BARIS TOTAL TUNGGAL (TANPA GARIS DALAM/GANDA) ── */
.main-table tbody tr.total-row td {
    font-weight: bold;
    border-top: 1px solid #000 !important;
    border-bottom: 1px solid #000 !important;
    border-left: none !important;
    border-right: none !important;
    padding: 6px 8px;
}

/* ── THP ── */
.thp-section {
    margin: 18px 15px 25px 135px;
    font-size: 11px;
}

.thp-label {
    font-weight: bold;
    display: inline-block;
    width: 60px;
}

.thp-value {
    color: red;
    font-weight: bold;
    font-style: italic;
    border-bottom: 3px double #000;
    padding-bottom: 1px;
}

/* ── FOOTER & TANDA TANGAN ── */
.footer-table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
    padding: 0 15px 15px 15px;
}

.footer-table td {
    vertical-align: top;
    border: none !important;
}

/* .note-box {
    background-color: #b8daff;
    color: #004080;
    font-size: 10.5px;
    padding: 2px 6px;
    display: inline-block;
    margin-bottom: 3px;
    font-weight: bold;
} */

.received-box {
    background-color: #d4edda;
    padding: 3px 8px;
    display: inline-block;
    font-size: 11px;
}

.sig-space {
    height: 50px;
}

.sig-name {
    font-weight: bold;
    text-decoration: underline;
}

.info-table .colon {
    text-align: left;
    transform: translateX(-3px);
}

.info-table .value {
    transform: translateX(-5px);
}
</style>
</head>

<body>
@php
    $slipCollection = isset($slips) ? collect($slips) : collect([$slip ?? null]);
@endphp

@foreach ($slipCollection->filter() as $slip)
@php
    $slipDate = $slip->payment_date
        ? $slip->payment_date->format('l , d F Y')
        : date('l , d F Y');
    $jpnCompany = (int) round($slip->ump * 0.02);
    $totalCompanyPaid = ($slip->bpjs_kesehatan_company ?? 0) + 
                        ($slip->jht_company ?? 0) + 
                        ($slip->jkk_company ?? 0) + 
                        $jpnCompany + 
                        ($slip->jkm_company ?? 0);
    
    $signature = is_array($slip->signatures) ? ($slip->signatures['dibuat'] ?? null) : null;
    $payrollName = $signature['name'] ?? 'KAMILA';
@endphp

<div class="slip-page">

    <!-- Header -->
    <table class="header-table" cellspacing="0" cellpadding="0">
        <tr>
            <td width="18%">
                <img src="{{ public_path('images/logo.jpeg') }}" alt="PT. AGHITSNA KARYA INDAH" class="company-logo">
            </td>
            <td width="47%">
                <div class="company-name">PT AGHITSNA KARYA INDAH</div>
                <div class="company-tagline">Design & Built</div>
            </td>
            <td width="35%" class="date-text">
                Tanggal &nbsp;&nbsp;&nbsp;&nbsp; : &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{ $slipDate }}
            </td>
        </tr>
    </table>

    <!-- Judul Dokumen -->
    <div class="slip-title">SLIP GAJI</div>

    <!-- Data Karyawan -->
   <table class="info-table" cellspacing="0" cellpadding="0">
    <tr>
        <td width="58%">ID</td>
        <td width="4%" class="colon">:</td>
        <td width="38%" class="value">{{ $slip->employee_code }}</td>
    </tr>
    <tr>
        <td>NAMA</td>
        <td class="colon">:</td>
        <td class="value">{{ $slip->employee->name ?? 'XXXX' }}</td>
    </tr>
    <tr>
        <td>JABATAN</td>
        <td class="colon">:</td>
        <td class="value">{{ $slip->employee->position ?? 'Staff' }}</td>
    </tr>
    <tr>
        <td>STATUS</td>
        <td class="colon">:</td>
        <td class="value">{{ $slip->employee->status ?? 'K/2' }}</td>
    </tr>
</table>
    {{-- <table class="info-table" cellspacing="0" cellpadding="0" style="padding-left: 2px">
        <tr>
            <td width="58%">ID</td>
            <td width="4%">:</td>
            <td width="38%">{{ $slip->employee_code }}</td>
        </tr>
        <tr>
            <td>NAMA</td>
            <td>:</td>
            <td>{{ $slip->employee->name ?? 'XXXX' }}</td>
        </tr>
        <tr>
            <td>JABATAN</td>
            <td>:</td>
            <td>{{ $slip->employee->position ?? 'Staff' }}</td>
        </tr>
        <tr>
            <td>STATUS</td>
            <td>:</td>
            <td>{{ $slip->employee->status ?? 'K/2' }}</td>
        </tr>
    </table> --}}

    <!-- Tabel Utama -->
    <table class="main-table" cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <th width="36%">PENERIMAAN</th>
                <th width="20%">POTONGAN</th>
                <th width="22%" class="bg-yellow-header">Dibayar Perusahaan</th>
                <th width="22%" class="text-blue-header">Dibayar Karyawan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <table class="inner-table" cellspacing="0" cellpadding="0">
                        <tr>
                            <td width="58%">Gaji Pokok</td>
                            <td width="4%">:</td>
                            <td width="38%" style="padding-left: 4px;">Rp {{ number_format($slip->base_salary, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </td>
                <td>Kasbon <span class="colon-right">:</span></td>
                <td class="text-left"></td>
                <td class="text-left">Rp {{ number_format($slip->kasbon_deduction, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>
                    <table class="inner-table" cellspacing="0" cellpadding="0">
                        <tr>
                            <td width="58%">Transport / {{ $slip->present_days }} Hari</td>
                            <td width="4%">:</td>
                            <td width="38%" style="padding-left: 4px;" class="red-italic">Rp {{ number_format($slip->transport_total, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </td>
                <td>BPJS KESEHATAN <span class="colon-right">:</span></td>
                <td class="text-left red-italic">Rp {{ number_format($slip->bpjs_kesehatan_company, 0, ',', '.') }}</td>
                <td class="text-left red-italic">Rp {{ number_format($slip->bpjs_kesehatan_employee, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>
                    <table class="inner-table" cellspacing="0" cellpadding="0">
                        <tr>
                            <td width="58%">Uang Makan / {{ $slip->present_days }} Hari</td>
                            <td width="4%">:</td>
                            <td width="38%" style="padding-left: 4px;" class="red-italic">Rp {{ number_format($slip->meal_total, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </td>
                <td>JHT <span class="colon-right">:</span></td>
                <td class="text-left red-italic">Rp {{ number_format($slip->jht_company, 0, ',', '.') }}</td>
                <td class="text-left red-italic">Rp {{ number_format($slip->jht_employee, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td></td>
                <td>JKK <span class="colon-right">:</span></td>
                <td class="text-left red-italic">Rp {{ number_format($slip->jkk_company, 0, ',', '.') }}</td>
                <td class="text-center">-</td>
            </tr>
            <tr>
                <td></td>
                <td>JPN <span class="colon-right">:</span></td>
                <td class="text-left red-italic">Rp {{ number_format($jpnCompany, 0, ',', '.') }}</td>
                <td class="text-left red-italic">Rp {{ number_format($slip->jpn_employee, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td></td>
                <td>JKM <span class="colon-right">:</span></td>
                <td class="text-left red-italic">Rp {{ number_format($slip->jkm_company, 0, ',', '.') }}</td>
                <td class="text-center">-</td>
            </tr>
            <tr>
                <td></td>
                <td>PPH 21 <span class="colon-right">:</span></td>
                <td></td>
                <td class="text-left red-italic">Rp {{ number_format($slip->pph21, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td class="bg-yellow-total text-center">Rp {{ number_format($totalCompanyPaid, 0, ',', '.') }}</td>
                <td></td>
            </tr>
            <tr class="total-row">
                <td>
                    <span style="float: left; font-weight: bold;">TOTAL PENERIMAAN</span>
                    <span style="float: right; font-weight: bold;">Rp {{ number_format($slip->total_income, 0, ',', '.') }}</span>
                </td>
                <td></td>
                <td>TOTAL POTONGAN <span class="colon-right">:</span></td>
                <td class="text-left red-text">Rp {{ number_format($slip->total_deduction, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Ringkasan THP -->
    <div class="thp-section">
        <span class="thp-label">THP</span>
        <span class="thp-value">Rp {{ number_format($slip->net_salary, 0, ',', '.') }}</span>
    </div>

    <!-- Catatan Tambahan & Tanda Tangan -->
    <table class="footer-table" cellspacing="0" cellpadding="0">
        <tr>
            <td width="20%">
                Payroll,<br>
                <div class="sig-space">
                    @if (!empty($signature['signature_image']))
                        <img src="{{ storage_path('app/public/' . $signature['signature_image']) }}" alt="Signature" style="max-height: 40px;">
                    @endif
                </div>
                <div class="sig-name">{{ $payrollName }}</div>
            </td>
            {{-- <td width="60%" style="padding-left: 10px;">
                <span class="note-box">*Bpjs Kesehatan : 1% dari Gaji Pokok</span><br>
                <span class="note-box">*JHT (Jaminan Hari Tua) : 2% dari Rp. {{ number_format($slip->ump, 0, ',', '.') }} (UMK Depok)</span><br>
                <span class="note-box">*JPN (Jaminan Pensiun) : 1% dari Rp. {{ number_format($slip->ump, 0, ',', '.') }} (UMK Depok)</span>
            </td> --}}
            <td width="20%" class="text-right">
                <span class="received-box">Diterima Oleh,</span><br>
                <div class="sig-space"></div>
                <div class="sig-name">{{ $slip->employee->name ?? 'XXXX' }}</div>
            </td>
        </tr>
    </table>

</div>

@if (!$loop->last)
<div style="page-break-after: always;"></div>
@endif
@endforeach

</body>
</html>