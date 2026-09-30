{{-- =====================================================================
     NOTA PDF - PT Aghitsna Karya Indah

     Satu file PDF bisa berisi banyak nota (1 nota per halaman).
     Layout dipilih per nota berdasarkan tipe_nota:
     - proyek    -> partials/nota-proyek
     - sewa_jual -> partials/nota-sewa-jual

     Kedua layout memakai kerangka yang sama agar rapi & konsisten:
     1. Kop surat (lebih besar dari isi) + garis ganda
     2. Judul "NOTA"
     3. Blok info: kiri = nomor referensi, kanan = tempat/tanggal + Kepada Yth.
     4. Tabel barang (Qty/Satuan/Harga/Jumlah rata tengah)
     5. Total / rincian biaya
     6. Tanda tangan sejajar (Tanda Terima/Penerima & Hormat Kami)

     Font: Times New Roman 12pt (kop & judul lebih besar).
     Style bersama didefinisikan di sini dan di-scope ke wrapper .nota.
     ===================================================================== --}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota - PT. Aghitsna Karya Indah</title>
    <style>
        @page {
            size: A4;
            margin: 12mm 14mm;
        }

        /* Reset tidak memakai selector "*": pada dompdf margin elemen <html>
           dipakai sebagai margin halaman, sehingga "*" akan menimpa @page margin. */
        body * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.3;
            color: #000;
            background: #fff;
        }

        .page-break {
            page-break-after: always;
        }

        /* ==========================================
           KOP SURAT
           ========================================== */
        .nota .kop {
            width: 100%;
            border-collapse: collapse;
        }

        .nota .kop td {
            vertical-align: middle;
            padding: 0;
        }

        .nota .kop-side {
            width: 18%;
        }

        .nota .kop-logo {
            width: 118px;
            height: auto;
        }

        .nota .kop-text {
            width: 64%;
            text-align: center;
        }

        .nota .kop-name {
            font-size: 20pt;
            font-weight: bold;
            letter-spacing: 1px;
            line-height: 1.15;
        }

        .nota .kop-tagline {
            font-size: 13pt;
            letter-spacing: 3px;
            margin-bottom: 4px;
        }

        .nota .kop-address {
            font-size: 12pt;
            line-height: 1.3;
        }

        .nota .kop-banner-cell {
            text-align: center;
        }

        .nota .kop-banner {
            width: 380px;
            height: auto;
        }

        /* Garis ganda di bawah kop */
        .nota .kop-rule {
            border-top: 3px solid #000;
            border-bottom: 1px solid #000;
            height: 3px;
            margin: 8px 0 12px 0;
        }

        /* ==========================================
           JUDUL
           ========================================== */
        .nota .doc-title {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            letter-spacing: 6px;
            margin-bottom: 12px;
        }

        /* Garis bawah judul simetris: padding kiri = sisa letter-spacing di kanan */
        .nota .doc-title span {
            display: inline-block;
            padding-left: 6px;
            border-bottom: 1px solid #000;
        }

        /* ==========================================
           BLOK INFO (NOMOR, TANGGAL, KEPADA)
           ========================================== */
        .nota .info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .nota .info > tbody > tr > td,
        .nota .info > tr > td {
            vertical-align: top;
            padding: 0;
        }

        .nota .info td.info-left {
            width: 58%;
            padding: 0 16px 0 0;
        }

        .nota .info td.info-right {
            width: 42%;
        }

        .nota .meta {
            width: 100%;
            border-collapse: collapse;
        }

        .nota .meta td {
            padding: 1px 0;
            vertical-align: top;
        }

        .nota .meta-label {
            width: 92px;
            white-space: nowrap;
        }

        .nota .meta-colon {
            width: 14px;
            text-align: center;
        }

        .nota .place-date {
            margin-bottom: 8px;
        }

        /* "Kepada Yth." tidak bold (konvensi klien) */
        .nota .kepada-label {
            font-weight: normal;
        }

        .nota .kepada-value {
            border-bottom: 1px dotted #000;
            padding: 2px 0;
            min-height: 20px;
        }

        /* ==========================================
           TABEL BARANG
           ========================================== */
        .nota .items {
            width: 100%;
            border-collapse: collapse;
        }

        .nota .items th,
        .nota .items td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: middle;
        }

        .nota .items th {
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            background: #e8e8e8;
        }

        .nota .items td {
            height: 24px;
        }

        .nota .text-center {
            text-align: center;
        }

        .nota .text-right {
            text-align: right;
        }

        .nota .nowrap {
            white-space: nowrap;
        }

        /* Baris total di bawah tabel barang */
        .nota .items .total-blank {
            border: none;
            border-top: 1px solid #000;
        }

        .nota .items .total-label,
        .nota .items .total-value {
            font-weight: bold;
            text-align: center;
            background: #e8e8e8;
        }

        /* ==========================================
           TANDA TANGAN
           ========================================== */
        .nota .sign {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            page-break-inside: avoid;
        }

        .nota .sign td {
            text-align: center;
            vertical-align: top;
            padding: 0;
        }

        .nota .sign-col {
            width: 38%;
        }

        .nota .sign-gap {
            width: 24%;
        }

        .nota .sign-space {
            height: 72px;
            vertical-align: bottom !important;
        }

        .nota .sign-image {
            max-height: 70px;
            max-width: 160px;
        }

        /* Nama penanda tangan tidak bold (konvensi klien) */
        .nota .sign-name {
            display: inline-block;
            min-width: 200px;
            border-top: 1px solid #000;
            padding-top: 3px;
            font-weight: normal;
        }
    </style>
</head>

<body>
    @foreach ($notas as $nota)
        @if ($nota->tipe_nota === \App\Models\Administrasi\Nota::TIPE_PROYEK)
            {{-- Layout Nota Proyek --}}
            @include('exports.administrasi.partials.nota-proyek', ['nota' => $nota])
        @else
            {{-- Layout Nota Sewa/Jual --}}
            @include('exports.administrasi.partials.nota-sewa-jual', ['nota' => $nota])
        @endif

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>

</html>
