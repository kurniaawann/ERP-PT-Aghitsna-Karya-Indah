<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeliveryNoteSeeder extends Seeder
{
    public function run(): void
    {
        $deliveries = [
            ['id' => 'DN-001', 'date' => now(), 'shipper' => 'PT. AKI Gudang', 'receiver' => 'PT. Sumber Makmur', 'desc' => 'Profil aluminium Proyek A', 'item' => 'Profil Aluminium 5x130x300', 'qty' => 10, 'driver' => 'Budi', 'vehicle' => 'B 1234 CD'],
            ['id' => 'DN-002', 'date' => now()->subDays(1), 'shipper' => 'PT. AKI Gudang', 'receiver' => 'CV. Mekar Jaya', 'desc' => 'Rangka jendela Proyek B', 'item' => 'Rangka Jendela', 'qty' => 5, 'driver' => 'Slamet', 'vehicle' => 'D 4321 EF'],
            ['id' => 'DN-003', 'date' => now()->subDays(2), 'shipper' => 'PT. Baja Sentosa', 'receiver' => 'PT. Cahaya Sentosa', 'desc' => 'Baja WF Proyek C', 'item' => 'Baja WF 300x200', 'qty' => 50, 'driver' => 'Anto', 'vehicle' => 'B 5555 GH'],
            ['id' => 'DN-004', 'date' => now()->subDays(3), 'shipper' => 'CV. Catylac Pro', 'receiver' => 'Proyek D', 'desc' => 'Cat finishing Proyek D', 'item' => 'Cat Catylac 20L', 'qty' => 20, 'driver' => 'Rudi', 'vehicle' => 'B 6666 IJ'],
            ['id' => 'DN-005', 'date' => now()->subDays(4), 'shipper' => 'PT. Kaca Bersinar', 'receiver' => 'Proyek E', 'desc' => 'Kaca tempered Proyek E', 'item' => 'Kaca Tempered 10mm', 'qty' => 100, 'driver' => 'Doni', 'vehicle' => 'B 7777 KL'],
            ['id' => 'DN-006', 'date' => now()->subDays(5), 'shipper' => 'PT. AKI Gudang', 'receiver' => 'Proyek F', 'desc' => 'Material finishing Proyek F', 'item' => 'Handle & Sealant', 'qty' => 30, 'driver' => 'Herry', 'vehicle' => 'B 8888 MN'],
            ['id' => 'DN-007', 'date' => now()->subDays(6), 'shipper' => 'CV. Utilitas Teknik', 'receiver' => 'Proyek G', 'desc' => 'Perlengkapan instalasi Proyek G', 'item' => 'Engsel & Hinges', 'qty' => 25, 'driver' => 'Bambang', 'vehicle' => 'B 9999 OP'],
            ['id' => 'DN-008', 'date' => now()->subDays(7), 'shipper' => 'PT. Logistik Cepat', 'receiver' => 'Proyek H', 'desc' => 'Material Proyek H', 'item' => 'Baja Ringan (2 ton)', 'qty' => 2, 'driver' => 'Taufik', 'vehicle' => 'B 1111 QR'],
            ['id' => 'DN-009', 'date' => now()->subDays(8), 'shipper' => 'PT. Batu Alam', 'receiver' => 'Proyek I', 'desc' => 'Batu alam travertine Proyek I', 'item' => 'Batu Alam Travertine', 'qty' => 50, 'driver' => 'Joko', 'vehicle' => 'B 2222 ST'],
            ['id' => 'DN-010', 'date' => now()->subDays(9), 'shipper' => 'CV. Kayu Jati', 'receiver' => 'Proyek J', 'desc' => 'Kayu jati Proyek J', 'item' => 'Kayu Jati Grade A', 'qty' => 15, 'driver' => 'Gunawan', 'vehicle' => 'B 3333 UV'],
            ['id' => 'DN-011', 'date' => now()->subDays(10), 'shipper' => 'PT. Signage Indonesia', 'receiver' => 'Proyek K', 'desc' => 'Signage box letter Proyek K', 'item' => 'Signage Box Letter', 'qty' => 8, 'driver' => 'Wahyu', 'vehicle' => 'B 4444 WX'],
            ['id' => 'DN-012', 'date' => now()->subDays(11), 'shipper' => 'PT. Pabrik Precast', 'receiver' => 'Proyek L', 'desc' => 'Beton precast Proyek L', 'item' => 'Pil Precast P-200', 'qty' => 200, 'driver' => 'Iwan', 'vehicle' => 'B 5555 YZ'],
            ['id' => 'DN-013', 'date' => now()->subDays(12), 'shipper' => 'PT. Utilitas Listrik', 'receiver' => 'Proyek M', 'desc' => 'Material listrik Proyek M', 'item' => 'Kabel NYM & Panel', 'qty' => 1, 'driver' => 'Supri', 'vehicle' => 'B 6666 AB'],
            ['id' => 'DN-014', 'date' => now()->subDays(13), 'shipper' => 'CV. Batu Alam', 'receiver' => 'Proyek N', 'desc' => 'Batu split & pasir Proyek N', 'item' => 'Batu Split 20 ton', 'qty' => 20, 'driver' => 'Basuki', 'vehicle' => 'B 7777 CD'],
            ['id' => 'DN-015', 'date' => now()->subDays(14), 'shipper' => 'PT. AKI Gudang', 'receiver' => 'Proyek O', 'desc' => 'Perlengkapan umum Proyek O', 'item' => 'Paku & Sekrup (Qty)', 'qty' => 40, 'driver' => 'Edi', 'vehicle' => 'B 8888 EF'],
        ];

        foreach ($deliveries as $dn) {
            DB::table('delivery_notes')->insert([
                'id_delivery_note' => $dn['id'],
                'document_number' => $dn['id'],
                'delivery_date' => $dn['date']->format('Y-m-d'),
                'shipper_name' => $dn['shipper'],
                'shipper_address' => 'Jl. Industri',
                'receiver_name' => $dn['receiver'],
                'receiver_address' => 'Ditempat',
                'description' => $dn['desc'],
                'items' => json_encode([['no' => 1, 'name' => $dn['item'], 'quantity' => $dn['qty'], 'unit' => 'pcs']]),
                'driver_name' => $dn['driver'],
                'vehicle_number' => $dn['vehicle'],
                'total_quantity' => $dn['qty'],
                'notes' => null,
                'status' => 'shipped',
                'created_at' => $dn['date'],
                'updated_at' => now(),
            ]);
        }
    }
}
