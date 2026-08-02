 <!DOCTYPE html>
 <html lang="id">

 <head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Kwitansi - PT. Aghitsna Karya Indah</title>
     <style>
         @page {
             size: A4;
             margin: 0.3cm;
         }

         * {
             margin: 0;
             padding: 0;
             box-sizing: border-box;
         }

         body {
             font-family: 'Times New Roman', Times, serif;
             /* width: 210mm;
            height: 297mm;
            margin: 0 auto; */
             padding: 3mm;
             background: white;
         }

         .container {
             border: 2px solid #000000;
             padding: 6px;
             height: auto;
             position: relative;
         }

         .header {
             display: flex;
             justify-content: space-between;
             align-items: flex-start;
             margin-bottom: 4px;
             padding-bottom: 3px;
         }

         .logo {
             width: 50px;
             margin-right: 6px;
         }

         .company-info {
             flex: 1;
             text-align: left;
         }

         .company-name {
             color: #000000;
             font-size: 12px;
             font-weight: bold;
             margin-bottom: 1px;
         }

         .company-details {
             font-size: 6.5px;
             line-height: 1.2;
             color: #333;
         }

         .receipt-meta {
             text-align: right;
             font-size: 7px;
             line-height: 1.1;
             position: absolute;
             top: 6px;
             right: 6px;
         }

         .title {
             text-align: center;
             color: #000000;
             font-size: 16px;
             font-weight: bold;
             letter-spacing: 3px;
             margin: 6px 0;
         }

         .field-separator {
             width: 10px;
             text-align: center;
         }

         .content-box {
             border: 2px solid #333;
             padding: 6px;
             margin-bottom: 6px;
             height: auto;
         }

         .field-row {
             display: flex;
             align-items: center;
             font-size: 9px;
             margin-bottom: 5px;
             width: 100%;
         }

         .field-label {
             width: 100px;
             flex-shrink: 0;
         }

         .field-value {
             flex: 1;
             padding-left: 5px;
             padding-bottom: 2px;
             border-bottom: 1px dotted #9a9a9a;
             min-height: 14px;
         }

         .payment-section {
             margin-top: 8px;
         }

         .payment-lines {
             margin-left: 110px;
         }

         .payment-line {
             border-bottom: 1px dotted #999;
             min-height: 13px;
             margin-bottom: 4px;
         }

         .amount-box {
             border: 2px solid #000000;
             padding: 6px;
             margin: 6px 0;
             /* width: 100%; */
             box-sizing: border-box;
         }

         .amount-row {
             display: flex;
             align-items: center;
             font-size: 9px;
             margin-bottom: 5px;
             width: 100%;
         }

         .amount-row:last-child {
             margin-bottom: 0;
         }

         .amount-label {
             width: 100px;
             flex-shrink: 0;
             font-weight: bold;
             font-size: 9px;
         }

         .amount-value {
             flex: 1;
             padding-left: 5px;
             padding-bottom: 2px;
             border-bottom: 1px dotted #9a9a9a;
             min-height: 14px;
             font-weight: bold;
             font-size: 18px;
         }

         .remainder-label {
             width: 100px;
             flex-shrink: 0;
             font-weight: bold;
             font-size: 9px;
         }

         .remainder-value {
             flex: 1;
             padding-left: 5px;
             padding-bottom: 2px;
             border-bottom: 1px dotted #9a9a9a;
             min-height: 14px;
             font-size: 9px;
         }

         .payment-methods {
             margin-bottom: 4px;
             font-size: 8px;
         }

         .payment-methods>div {
             display: inline-block;
             margin-right: 15px;
         }

         .checkbox {
             display: inline-block;
             width: 10px;
             height: 10px;
             border: 1px solid #333;
             vertical-align: middle;
             margin-right: 3px;
         }

         .bank-details {
             display: flex;
             justify-content: space-between;
             align-items: flex-end;
             font-size: 8px;
             margin-bottom: 0;
         }

         .bank-info {
             flex: 0 0 auto;
         }

         .bank-row {
             display: flex;
             align-items: center;
             margin-bottom: 3px;
         }

         .bank-row:last-child {
             margin-bottom: 0;
         }

         .bank-label {
             font-style: italic;
             color: #000000;
             width: 40px;
             margin-right: 5px;
         }

         .bank-value {
             flex: 1;
             max-width: 200px;
             min-height: 11px;
             padding-bottom: 1px;
             border-bottom: 1px dotted #9a9a9a;
         }

         .signature-box {
             width: 120px;
             text-align: center;
             margin-left: auto;
             margin-right: 0;
         }

         .signature-label {
             font-size: 8px;
             font-weight: bold;
             margin-bottom: 40px;
         }

         .footer-note {
             margin-top: 10px;
             font-size: 7px;
             font-style: italic;
             text-align: center;
             color: #000000;
             padding: 4px;
             border-top: 1px solid #ccc;
         }

         .page-break {
             page-break-after: always;
         }

         @media print {
             body {
                 margin: 0;
                 padding: 3mm;
             }

             .container {
                 page-break-inside: avoid;
             }

             .page-break {
                 page-break-after: always;
             }
         }
     </style>
 </head>

 <body>
     @foreach ($kwintansis as $index => $kwintansi)
         <div class="container {{ $index < count($kwintansis) - 1 ? 'page-break' : '' }}">
             <div class="header">
                 <div style="display: flex; flex: 1;">
                     <img src="{{ public_path('images/logo.jpeg') }}" alt="Logo" class="logo">
                     <div class="company-info">
                         {{-- <div class="company-name">PT. AGHITSNA KARYA INDAH</div> --}}
                         <div class="company-details">
                            PT AGHITSNA KARYA INDAH<br>
                            JL. TANAH BARU RAYA PERTIWI RT/01/05
                            BEJI, DEPOK, JAWA BARAT
                            TELP. 021-29034923 - 0812 9596 522 <br>
                            Email : Design@aghitsna.id
                         </div>
                     </div>
                 </div>
                 <div class="receipt-meta">
                     <div>{{ $kwintansi->location }},
                         {{ \Carbon\Carbon::parse($kwintansi->kwintansi_date)->format('d/m/Y') }}</div>
                     <div>No: {{ $kwintansi->id_kwintansi }}</div>
                     <div>Hal: {{ \Carbon\Carbon::parse($kwintansi->kwintansi_date)->format('d M Y') }}</div>
                 </div>
             </div>

             <div class="title">KWITANSI</div>

             <div class="content-box">
                 <div class="field-row">
                     <span class="field-label">Sudah terima dari:</span>
                     <span class="field-value">
                         {{ $kwintansi->received_from }}
                     </span>
                 </div>

                 <div class="field-row">
                     <span class="field-label">Banyaknya uang:</span>
                     <span class="field-value">{{ ucfirst(terbilang($kwintansi->amount)) }} rupiah</span>
                 </div>

                 <div class="payment-section">
                     <div class="field-row">
                         <span class="field-label">Untuk Pembayaran:</span>
                         {{-- <span class="field-separator">:</span> --}}
                         <span class="field-value">{{ $kwintansi->payment_for }}</span>
                     </div>

                 </div>
             </div>

             <div class="amount-box">
                 <div class="amount-row">
                     <span class="amount-label">Rp.</span>
                     <span class="amount-value">
                         {{ number_format($kwintansi->amount, 0, ',', '.') }}
                     </span>
                 </div>

                 <div class="amount-row">
                     <span class="remainder-label">Sisa:</span>
                     <span class="remainder-value">
                         {{ $kwintansi->remaining ? 'Rp. ' . number_format($kwintansi->remaining, 0, ',', '.') : '' }}
                     </span>
                 </div>
             </div>

             <div class="signature-section">
                 <div class="payment-methods">
                     <div>
                         <span class="checkbox"></span> TUNAI
                     </div>
                     <div>
                         <span class="checkbox"></span> CHEQUE
                     </div>
                     <div>
                         <span class="checkbox"></span> BILYET GIRO
                     </div>
                 </div>

                 @if ($kwintansi->include_bank && $kwintansi->paymentAccount)
                     <div class="bank-details">
                         <div class="bank-info">
                             <div class="bank-row">
                                 <div class="bank-label">BANK</div>
                                 <div class="bank-value">{{ $kwintansi->paymentAccount->bank_name }}</div>
                             </div>
                             <div class="bank-row">
                                 <div class="bank-label">NO.</div>
                                 <div class="bank-value">{{ $kwintansi->paymentAccount->account_number }}</div>
                             </div>
                             <div class="bank-row">
                                 <div class="bank-label">TGL</div>
                                 <div class="bank-value">
                                     {{ \Carbon\Carbon::parse($kwintansi->kwintansi_date)->format('d/m/Y') }}</div>
                             </div>
                         </div>
                         <div class="signature-box">
                             <div class="signature-label">Tanda Tangan</div>
                             <div class="signature-line">(___________)</div>
                         </div>
                     </div>
                 @else
                     <div class="bank-details">
                         <div class="bank-info">
                             <div class="bank-row">
                                 <div class="bank-label">BANK:</div>
                                 <div class="bank-value"></div>
                             </div>
                             <div class="bank-row">
                                 <div class="bank-label">NO:</div>
                                 <div class="bank-value"></div>
                             </div>
                             <div class="bank-row">
                                 <div class="bank-label">TGL:</div>
                                 <div class="bank-value"></div>
                             </div>
                         </div>
                         <div class="signature-box">
                             <div class="signature-label">Tanda Tangan</div>
                             <div class="signature-line">(___________)</div>
                         </div>
                     </div>
                 @endif
             </div>

             <div class="footer-note">
                 Kwitansi ini baru dianggap sah, setelah Pembayaran dengan Bilyet Giro / Cheque tsb, dapat di uangkan.
             </div>
         </div>
     @endforeach
 </body>

 </html>
