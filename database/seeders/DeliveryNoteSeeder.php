<?php
namespace Database\Seeders;
use App\Models\Administrasi\DeliveryNote;
use App\Models\User;
use Illuminate\Database\Seeder;

class DeliveryNoteSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        
        for ($i = 1; $i <= 15; $i++) {
            $items = [];
            $totalQty = 0;
            $numItems = rand(1, 4);
            for ($j = 0; $j < $numItems; $j++) {
                $qty = rand(5, 50);
                $items[] = [
                    'description' => fake()->words(2, true),
                    'quantity' => $qty,
                    'unit' => fake()->randomElement(['pcs', 'm', 'kg', 'set']),
                ];
                $totalQty += $qty;
            }

            DeliveryNote::updateOrCreate(
                ['id_delivery_note' => 'DN-' . str_pad($i, 3, '0', STR_PAD_LEFT)],
                [
                    'document_number' => 'DOC-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                    'delivery_date' => fake()->dateTimeBetween('-3 months', 'now'),
                    'shipper_name' => fake()->company(),
                    'shipper_address' => fake()->address(),
                    'receiver_name' => fake()->company(),
                    'receiver_address' => fake()->address(),
                    'description' => fake()->sentence(),
                    'items' => $items,
                    'driver_name' => fake()->name(),
                    'vehicle_number' => fake()->bothify('??? ####'),
                    'total_quantity' => $totalQty,
                    'created_by' => $admin?->id,
                ]
            );
        }
    }
}
