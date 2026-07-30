<?php
namespace Database\Seeders;
use App\Models\Administrasi\DocumentReceipt;
use App\Models\User;
use Illuminate\Database\Seeder;

class DocumentReceiptSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $sources = [
            'Admin Proyek', 'Supplier', 'Bagian Pembelian', 'Tim QC', 'Manufaktur',
            'Klien', 'Lab Independen', 'Consulting Engineer', 'Tim Desain',
            'Instansi Terkait', 'Tim Sistem', 'Vendor Training', 'Owner',
            'K3 Independen', 'Tim Proyek',
        ];

        foreach ($sources as $i => $source) {
            DocumentReceipt::updateOrCreate(
                ['id_document' => 'DOC-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT)],
                [
                    'received_from' => $source,
                    'regarding' => 'Dokumen dari ' . $source,
                    'form_of' => fake()->randomElement(['Surat', 'Dokumen', 'Berkas', 'Laporan']),
                    'receipt_date' => fake()->dateTimeBetween('-3 months', 'now'),
                    'receipt_time' => fake()->time('H:i'),
                    'location' => fake()->randomElement(['Depok', 'Jakarta', 'Bandung']),
                    'created_by' => $admin?->id,
                ]
            );
        }
    }
}
