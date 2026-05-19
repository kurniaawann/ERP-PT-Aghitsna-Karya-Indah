<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentReceiptSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['reference' => 'DR-001', 'description' => 'Slip pengiriman dokumen kontrak proyek Gedung A', 'date' => now(), 'notes' => 'Sudah diterima dan ditandatangani'],
            ['reference' => 'DR-002', 'description' => 'Sertifikat peralatan dari supplier PT. Baja Sentosa', 'date' => now()->subDays(1), 'notes' => 'Dokumen original untuk file arsip'],
            ['reference' => 'DR-003', 'description' => 'Invoice dan surat jalan pembelian material proyek', 'date' => now()->subDays(3), 'notes' => 'Lengkap dengan tanda tangan penerima'],
            ['reference' => 'DR-004', 'description' => 'Berita acara hasil inspeksi lapangan', 'date' => now()->subDays(5), 'notes' => 'Hasil quality control final'],
            ['reference' => 'DR-005', 'description' => 'Dokumen garansi produk dari manufaktur', 'date' => now()->subDays(7), 'notes' => 'Garansi berlaku 2 tahun'],
        ];

        foreach ($data as $item) {
            DB::table('document_receipts')->insert([
                'reference' => $item['reference'],
                'description' => $item['description'],
                'receipt_date' => $item['date'],
                'notes' => $item['notes'],
                'created_at' => $item['date'],
                'updated_at' => now(),
            ]);
        }
    }
}
