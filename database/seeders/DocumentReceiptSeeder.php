<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentReceiptSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['id_document' => 'DOC-001', 'received_from' => 'Admin Proyek', 'regarding' => 'Kontrak proyek Gedung A', 'form_of' => 'Slip pengiriman dokumen', 'receipt_date' => now(), 'receipt_time' => '09:15:00', 'location' => 'Depok'],
            ['id_document' => 'DOC-002', 'received_from' => 'Supplier', 'regarding' => 'Sertifikat peralatan', 'form_of' => 'Sertifikat', 'receipt_date' => now()->subDays(1), 'receipt_time' => '10:10:00', 'location' => 'Depok'],
            ['id_document' => 'DOC-003', 'received_from' => 'Bagian Pembelian', 'regarding' => 'Invoice & surat jalan material', 'form_of' => 'Invoice dan Surat Jalan', 'receipt_date' => now()->subDays(3), 'receipt_time' => '08:30:00', 'location' => 'Depok'],
            ['id_document' => 'DOC-004', 'received_from' => 'Tim QC', 'regarding' => 'Inspeksi lapangan', 'form_of' => 'Berita acara', 'receipt_date' => now()->subDays(5), 'receipt_time' => '13:40:00', 'location' => 'Depok'],
            ['id_document' => 'DOC-005', 'received_from' => 'Manufaktur', 'regarding' => 'Garansi produk', 'form_of' => 'Dokumen garansi', 'receipt_date' => now()->subDays(7), 'receipt_time' => '11:05:00', 'location' => 'Depok'],
            ['id_document' => 'DOC-006', 'received_from' => 'Klien', 'regarding' => 'Serah terima aset proyek Pabrik B', 'form_of' => 'Berita acara serah terima', 'receipt_date' => now()->subDays(2), 'receipt_time' => '14:20:00', 'location' => 'Depok'],
            ['id_document' => 'DOC-007', 'received_from' => 'Lab Independen', 'regarding' => 'Uji material', 'form_of' => 'Sertifikat uji', 'receipt_date' => now()->subDays(4), 'receipt_time' => '09:00:00', 'location' => 'Depok'],
            ['id_document' => 'DOC-008', 'received_from' => 'Consulting Engineer', 'regarding' => 'Rekomendasi teknis', 'form_of' => 'Slip pengiriman dokumen', 'receipt_date' => now()->subDays(6), 'receipt_time' => '15:10:00', 'location' => 'Depok'],
            ['id_document' => 'DOC-009', 'received_from' => 'Tim Desain', 'regarding' => 'As-Built Drawing Renovasi C', 'form_of' => 'Laporan', 'receipt_date' => now()->subDays(8), 'receipt_time' => '10:45:00', 'location' => 'Depok'],
            ['id_document' => 'DOC-010', 'received_from' => 'Instansi Terkait', 'regarding' => 'Izin lingkungan', 'form_of' => 'Dokumen izin', 'receipt_date' => now()->subDays(9), 'receipt_time' => '12:25:00', 'location' => 'Depok'],
            ['id_document' => 'DOC-011', 'received_from' => 'Tim Sistem', 'regarding' => 'Test performa sistem', 'form_of' => 'Berita acara hasil test', 'receipt_date' => now()->subDays(10), 'receipt_time' => '16:00:00', 'location' => 'Depok'],
            ['id_document' => 'DOC-012', 'received_from' => 'Vendor Training', 'regarding' => 'Training operator peralatan', 'form_of' => 'Sertifikat training', 'receipt_date' => now()->subDays(11), 'receipt_time' => '09:35:00', 'location' => 'Depok'],
            ['id_document' => 'DOC-013', 'received_from' => 'Owner', 'regarding' => 'Persetujuan tahap lanjutan', 'form_of' => 'Dokumen persetujuan', 'receipt_date' => now()->subDays(12), 'receipt_time' => '08:55:00', 'location' => 'Depok'],
            ['id_document' => 'DOC-014', 'received_from' => 'K3 Independen', 'regarding' => 'Safety audit', 'form_of' => 'Laporan safety audit', 'receipt_date' => now()->subDays(13), 'receipt_time' => '11:20:00', 'location' => 'Depok'],
            ['id_document' => 'DOC-015', 'received_from' => 'Tim Proyek', 'regarding' => 'Handover pekerjaan Kantor D', 'form_of' => 'Dokumen handover', 'receipt_date' => now()->subDays(14), 'receipt_time' => '14:55:00', 'location' => 'Depok'],
        ];

        foreach ($data as $item) {
            DB::table('document_receipts')->insert([
                'id_document' => $item['id_document'],
                'received_from' => $item['received_from'],
                'regarding' => $item['regarding'],
                'form_of' => $item['form_of'],
                'receipt_date' => $item['receipt_date'],
                'receipt_time' => $item['receipt_time'],
                'location' => $item['location'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
