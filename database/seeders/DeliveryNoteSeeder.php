<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeliveryNoteSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('delivery_notes')->insert([
            [
                'id_delivery_note' => 'DN-001',
                'document_number' => 'DN-001',
                'delivery_date' => now()->format('Y-m-d'),
                'shipper_name' => 'PT. AKI Gudang',
                'shipper_address' => 'Jl. Industri No.1',
                'receiver_name' => 'PT. Sumber Makmur',
                'receiver_address' => 'Ditempat',
                'description' => 'Pengiriman profil aluminium untuk Proyek Alpha',
                'items' => json_encode([['no' => 1, 'name' => 'Profil Aluminium 5x130x300', 'quantity' => 10, 'unit' => 'pcs']]),
                'driver_name' => 'Budi',
                'vehicle_number' => 'B 1234 CD',
                'total_quantity' => 10,
                'notes' => null,
                'status' => 'shipped',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_delivery_note' => 'DN-002',
                'document_number' => 'DN-002',
                'delivery_date' => now()->format('Y-m-d'),
                'shipper_name' => 'PT. AKI Gudang',
                'shipper_address' => 'Jl. Industri No.1',
                'receiver_name' => 'CV. Mekar Jaya',
                'receiver_address' => 'Ditempat',
                'description' => 'Pengiriman rangka jendela untuk Proyek Beta',
                'items' => json_encode([['no' => 1, 'name' => 'Rangka Jendela', 'quantity' => 5, 'unit' => 'pcs']]),
                'driver_name' => 'Slamet',
                'vehicle_number' => 'D 4321 EF',
                'total_quantity' => 5,
                'notes' => null,
                'status' => 'shipped',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
