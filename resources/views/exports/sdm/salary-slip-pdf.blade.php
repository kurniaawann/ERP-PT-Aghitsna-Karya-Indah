<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="UTF-8">
<title>Slip Gaji - {{ ($slips->first() ?? $slip)->employee->name ?? ($slips->first() ?? $slip)->employee_code }}</title>
{{--
    Cetak Slip Gaji Karyawan Kantor (satu slip per halaman).

    Revisi klien:
    - Semua teks hitam; hanya judul "Dibayar Perusahaan" berwarna biru
      dengan latar kuning.
    - Baris Lembur tepat di bawah baris Uang Makan.
    - Kasbon dicicil: "Rp. total → Rp. cicilan (sisa Rp. sisa)"; potongan
      bulan ini = cicilan (kasbon_deduction).
    - Kotak luar lebih ringkas (lebar & jarak dalam dikurangi), tanda tangan
      "Payroll," dan "Diterima Oleh," berdekatan di tengah.
    - Nominal memakai format "Rp. 50.000" (helper format_rupiah).
    - Label & titik dua data karyawan (ID, NAMA, ...) sejajar dengan label &
      titik dua kolom Penerimaan (lebar kolom label/titik dua disamakan).
--}}
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

/* Kotak luar slip: lebih sempit dari lebar halaman & rata tengah */
.slip-page {
    width: 74%;
    margin: 0 auto;
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
}

.header-table td {
    vertical-align: middle;
    padding: 6px 10px;
}

.company-logo {
    max-height: 40px;
    width: auto;
}

.company-name {
    font-size: 14px;
    font-weight: bold;
    letter-spacing: 0.5px;
}

.company-tagline {
    font-size: 11px;
}

.date-text {
    text-align: right;
    font-size: 11px;
}

/* ── JUDUL ── */
.slip-title {
    text-align: center;
    font-weight: bold;
    font-size: 14px;
    margin: 6px 10px 6px 10px;
    letter-spacing: 1px;
}

/* ── DATA KARYAWAN ── */
.info-table {
    width: 45%;
    border-collapse: collapse;
    border-spacing: 0;
    margin: 0 0 6px 10px;
}

/* Lebar kolom label & titik dua dipakai bersama oleh .info-table dan
   .inner-table agar teks dan ":" sejajar satu garis vertikal. */
.col-label {
    width: 130px;
}

.col-colon {
    width: 12px;
}

.info-table td {
    padding: 1px 0;
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

.main-table thead tr th {
    border-top: 1px solid #000 !important;
    border-bottom: 1px solid #000 !important;
    border-left: none !important;
    border-right: none !important;
    padding: 4px 6px;
    font-size: 11px;
    font-weight: bold;
    text-align: left;
    box-sizing: border-box;
}

.main-table tbody td {
    border: none !important;
    padding: 2px 6px;
    font-size: 11px;
    vertical-align: top;
    box-sizing: border-box;
}

.main-table th:first-child,
.main-table td:first-child {
    padding-left: 10px !important;
}

/* Sub-tabel kolom Penerimaan (label : nilai) */
.inner-table,
.inner-table tr,
.inner-table td {
    border: none !important;
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

/* Jangan ikut padding-left kolom pertama .main-table (label jadi menjorok) */
.main-table .inner-table tr td {
    padding: 0 !important;
}

/* Judul "Dibayar Perusahaan": teks biru, latar kuning */
.bg-yellow-header {
    background-color: #ffff00 !important;
    color: #0066cc;
    text-align: center !important;
    font-weight: bold;
}

/* Judul "Dibayar Karyawan": teks hitam */
.employee-header {
    text-align: center !important;
    font-weight: bold;
}

/* Kotak total Dibayar Perusahaan */
.bg-yellow-total {
    background-color: #ffff00 !important;
    text-align: center;
    font-weight: bold;
    border-top: 1px solid #000 !important;
}

.value-italic {
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

/* Panah kasbon memakai DejaVu Sans (glyph → tidak ada di Helvetica DomPDF) */
.arrow {
    font-family: 'DejaVu Sans', sans-serif;
    font-size: 10px;
}

/* ── BARIS TOTAL ── */
.main-table tbody tr.total-row td {
    font-weight: bold;
    border-top: 1px solid #000 !important;
    border-bottom: 1px solid #000 !important;
    padding: 4px 6px;
}

/* ── THP ── */
.thp-section {
    margin: 8px 10px 4px 10px;
    font-size: 11px;
}

.thp-label {
    font-weight: bold;
    display: inline-block;
    width: 40px;
}

.thp-value {
    font-weight: bold;
    font-style: italic;
    border-bottom: 3px double #000;
    padding-bottom: 1px;
}

/* ── TANDA TANGAN (berdekatan, rata tengah) ── */
.footer-table {
    border-collapse: collapse;
    border-spacing: 0;
    margin: 4px auto 8px auto;
}

.footer-table td {
    width: 170px;
    text-align: center;
    vertical-align: top;
    border: none !important;
    padding: 0 8px;
}

/* Judul blok tanda tangan; tinggi disamakan agar nama sejajar */
.sig-title {
    padding: 2px 8px;
    display: inline-block;
    font-size: 11px;
}

.received-box {
    background-color: #d4edda;
}

.sig-space {
    height: 45px;
}

.sig-name {
    font-weight: bold;
    text-decoration: underline;
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
        ? $slip->payment_date->translatedFormat('l , d F Y')
        : date('l , d F Y');
    $jpnCompany = (int) round($slip->ump * 0.02);
    $totalCompanyPaid = ($slip->bpjs_kesehatan_company ?? 0) +
                        ($slip->jht_company ?? 0) +
                        ($slip->jkk_company ?? 0) +
                        $jpnCompany +
                        ($slip->jkm_company ?? 0);

    // Kasbon dicicil: total sisa kasbon → cicilan bulan ini → sisa.
    $kasbonTotal = (int) ($slip->kasbon_total ?? 0);
    $kasbonDeduction = (int) ($slip->kasbon_deduction ?? 0);
    if ($kasbonTotal < $kasbonDeduction) {
        // Slip lama (sebelum fitur cicilan) belum menyimpan total kasbon.
        $kasbonTotal = $kasbonDeduction;
    }
    $kasbonRemaining = max(0, $kasbonTotal - $kasbonDeduction);

    $signature = is_array($slip->signatures) ? ($slip->signatures['dibuat'] ?? null) : null;
    $payrollName = $signature['name'] ?? 'KAMILA';
@endphp

<div class="slip-page">

    <!-- Header -->
    <table class="header-table" cellspacing="0" cellpadding="0">
        <tr>
            <td width="16%">
                <img src="{{ public_path('images/logo.jpeg') }}" alt="PT. AGHITSNA KARYA INDAH" class="company-logo">
            </td>
            <td width="44%">
                <div class="company-name">PT AGHITSNA KARYA INDAH</div>
                <div class="company-tagline">Design & Built</div>
            </td>
            <td width="40%" class="date-text">
                Tanggal &nbsp;&nbsp; : &nbsp;&nbsp; {{ $slipDate }}
            </td>
        </tr>
    </table>

    <!-- Judul Dokumen -->
    <div class="slip-title">SLIP GAJI</div>

    <!-- Data Karyawan -->
    <table class="info-table" cellspacing="0" cellpadding="0">
        <tr>
            <td class="col-label">ID</td>
            <td class="col-colon">:</td>
            <td>{{ $slip->employee_code }}</td>
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
    </table>

    <!-- Tabel Utama -->
    <table class="main-table" cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <th width="34%">PENERIMAAN</th>
                <th width="20%">POTONGAN</th>
                <th width="23%" class="bg-yellow-header">Dibayar Perusahaan</th>
                <th width="23%" class="employee-header">Dibayar Karyawan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <table class="inner-table" cellspacing="0" cellpadding="0">
                        <tr>
                            <td class="col-label">Gaji Pokok</td>
                            <td class="col-colon">:</td>
                            <td>{{ format_rupiah($slip->base_salary) }}</td>
                        </tr>
                    </table>
                </td>
                <td>Kasbon <span class="colon-right">:</span></td>
                @if ($kasbonTotal > 0)
                    {{-- Total kasbon → cicilan bulan ini (dipotong) → sisa --}}
                    <td colspan="2" class="text-left">
                        {{ format_rupiah($kasbonTotal) }}
                        <span class="arrow">&rarr;</span>
                        <strong>{{ format_rupiah($kasbonDeduction) }}</strong>
                        (sisa {{ format_rupiah($kasbonRemaining) }})
                    </td>
                @else
                    <td></td>
                    <td class="text-left">{{ format_rupiah(0) }}</td>
                @endif
            </tr>
            <tr>
                <td>
                    <table class="inner-table" cellspacing="0" cellpadding="0">
                        <tr>
                            <td class="col-label">Transport / {{ $slip->present_days }} Hari</td>
                            <td class="col-colon">:</td>
                            <td class="value-italic">{{ format_rupiah($slip->transport_total) }}</td>
                        </tr>
                    </table>
                </td>
                <td>BPJS KESEHATAN <span class="colon-right">:</span></td>
                <td class="text-left value-italic">{{ format_rupiah($slip->bpjs_kesehatan_company) }}</td>
                <td class="text-left value-italic">{{ format_rupiah($slip->bpjs_kesehatan_employee) }}</td>
            </tr>
            <tr>
                <td>
                    <table class="inner-table" cellspacing="0" cellpadding="0">
                        <tr>
                            <td class="col-label">Uang Makan / {{ $slip->present_days }} Hari</td>
                            <td class="col-colon">:</td>
                            <td class="value-italic">{{ format_rupiah($slip->meal_total) }}</td>
                        </tr>
                    </table>
                </td>
                <td>JHT <span class="colon-right">:</span></td>
                <td class="text-left value-italic">{{ format_rupiah($slip->jht_company) }}</td>
                <td class="text-left value-italic">{{ format_rupiah($slip->jht_employee) }}</td>
            </tr>
            <tr>
                <td>
                    {{-- Lembur (modul Lembur) tepat di bawah Uang Makan --}}
                    <table class="inner-table" cellspacing="0" cellpadding="0">
                        <tr>
                            <td class="col-label">Lembur</td>
                            <td class="col-colon">:</td>
                            <td class="value-italic">{{ format_rupiah($slip->overtime_total ?? 0) }}</td>
                        </tr>
                    </table>
                </td>
                <td>JKK <span class="colon-right">:</span></td>
                <td class="text-left value-italic">{{ format_rupiah($slip->jkk_company) }}</td>
                <td class="text-center">-</td>
            </tr>
            <tr>
                <td></td>
                <td>JPN <span class="colon-right">:</span></td>
                <td class="text-left value-italic">{{ format_rupiah($jpnCompany) }}</td>
                <td class="text-left value-italic">{{ format_rupiah($slip->jpn_employee) }}</td>
            </tr>
            <tr>
                <td></td>
                <td>JKM <span class="colon-right">:</span></td>
                <td class="text-left value-italic">{{ format_rupiah($slip->jkm_company) }}</td>
                <td class="text-center">-</td>
            </tr>
            <tr>
                <td></td>
                <td>PPH 21 <span class="colon-right">:</span></td>
                <td></td>
                <td class="text-left value-italic">{{ format_rupiah($slip->pph21) }}</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td class="bg-yellow-total text-center">{{ format_rupiah($totalCompanyPaid) }}</td>
                <td></td>
            </tr>
            <tr class="total-row">
                <td>
                    <span style="float: left;">TOTAL PENERIMAAN</span>
                    <span style="float: right;">{{ format_rupiah($slip->total_income) }}</span>
                </td>
                <td></td>
                <td>TOTAL POTONGAN <span class="colon-right">:</span></td>
                <td class="text-left">{{ format_rupiah($slip->total_deduction) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Ringkasan THP -->
    <div class="thp-section">
        <span class="thp-label">THP</span>
        <span class="thp-value">{{ format_rupiah($slip->net_salary) }}</span>
    </div>

    <!-- Tanda Tangan: Payroll & Diterima Oleh berdekatan di tengah -->
    <table class="footer-table" cellspacing="0" cellpadding="0">
        <tr>
            <td>
                <span class="sig-title">Payroll,</span><br>
                <div class="sig-space">
                    @if (!empty($signature['signature_image']))
                        <img src="{{ storage_path('app/public/' . $signature['signature_image']) }}" alt="Signature" style="max-height: 40px;">
                    @endif
                </div>
                <div class="sig-name">{{ $payrollName }}</div>
            </td>
            <td>
                <span class="sig-title received-box">Diterima Oleh,</span><br>
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
