<?php
namespace Database\Seeders;
use App\Models\Report\ExpenseRecap;
use App\Models\Report\TransactionCategory;
use App\Models\Report\SalesRecap;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExpenseRecapSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $categories = TransactionCategory::all();
        $recaps = SalesRecap::all();
        if ($categories->isEmpty()) return;

        $counter = 1;
        $year = date('Y');

        for ($i = 0; $i < 15; $i++) {
            $cat = $categories->random();
            $isIncome = $cat->type === 'INCOME';

            ExpenseRecap::create([
                'id' => 'RE-' . str_pad($counter++, 5, '0', STR_PAD_LEFT),
                'transaction_category_id' => $cat->id,
                'invoice_number' => fake()->optional()->bothify('INV-####'),
                'transaction_date' => fake()->dateTimeBetween('-6 months', 'now'),
                'description' => $cat->name . ' - ' . fake()->sentence(3),
                'income_amount' => $isIncome ? rand(1000000, 50000000) : null,
                'expense_amount' => !$isIncome ? rand(100000, 10000000) : null,
                'money_source' => fake()->randomElement(['Cash', 'Bank BCA', 'Bank Mandiri']),
                'sales_recap_id' => $recaps->isNotEmpty() ? $recaps->random()->id_sales_recap : null,
                'created_by' => $admin?->id,
            ]);
        }
    }
}
