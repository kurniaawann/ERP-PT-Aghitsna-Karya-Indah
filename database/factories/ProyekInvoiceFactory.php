<?php
namespace Database\Factories;
use App\Models\Finance\InvoiceProyek;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProyekInvoiceFactory extends Factory
{
    protected $model = InvoiceProyek::class;

    public function definition(): array
    {
        $items = [];
        $numItems = fake()->numberBetween(2, 5);
        $total = 0;
        for ($i = 0; $i < $numItems; $i++) {
            $price = fake()->numberBetween(500000, 5000000);
            $qty = fake()->numberBetween(1, 20);
            $items[] = [
                'description' => fake()->words(3, true),
                'quantity' => $qty,
                'unit' => fake()->randomElement(['pcs', 'm', 'kg', 'set']),
                'unit_price' => $price,
                'total_price' => $price * $qty,
            ];
            $total += $price * $qty;
        }

        return [
            'invoice_number' => null,
            'invoice_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'recipient' => fake()->company(),
            'regarding' => fake()->optional()->sentence(),
            'project_description' => fake()->sentence(),
            'items' => $items,
            'total_amount' => $total,
            'discount_type' => null,
            'discount_value' => null,
            'total_after_discount' => null,
            'dp_type' => null,
            'dp_value' => null,
            'dp_amount' => null,
            'payment_installments' => null,
            'selected_payment_accounts' => null,
            'created_by' => null,
            'signed_by' => fake()->optional()->name(),
            'division' => fake()->optional()->randomElement(['Direktur', 'Manager', ' Supervisor']),
        ];
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => ['created_by' => $user->id]);
    }
}
