<?php
namespace Database\Factories;
use App\Models\Administrasi\DeliveryNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryNoteFactory extends Factory
{
    protected $model = DeliveryNote::class;

    public function definition(): array
    {
        $items = [];
        $totalQty = 0;
        $numItems = fake()->numberBetween(1, 5);
        for ($i = 0; $i < $numItems; $i++) {
            $qty = fake()->numberBetween(1, 50);
            $items[] = [
                'description' => fake()->words(2, true),
                'quantity' => $qty,
                'unit' => fake()->randomElement(['pcs', 'm', 'kg', 'set', 'box']),
            ];
            $totalQty += $qty;
        }

        return [
            'id_delivery_note' => null,
            'document_number' => fake()->bothify('DOC-####'),
            'delivery_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'shipper_name' => fake()->company(),
            'shipper_address' => fake()->address(),
            'receiver_name' => fake()->company(),
            'receiver_address' => fake()->address(),
            'description' => fake()->optional()->sentence(),
            'items' => $items,
            'driver_name' => fake()->name(),
            'vehicle_number' => fake()->bothify('??? ####'),
            'total_quantity' => $totalQty,
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
        ];
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => ['created_by' => $user->id]);
    }
}
