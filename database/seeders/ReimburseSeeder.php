<?php
namespace Database\Seeders;
use App\Models\Finance\Reimburse;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReimburseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $statuses = ['approved', 'approved', 'draft'];

        for ($i = 1; $i <= 15; $i++) {
            $status = $statuses[$i % count($statuses)];
            Reimburse::updateOrCreate(
                ['reimburse_code' => 'RMB' . str_pad($i, 3, '0', STR_PAD_LEFT)],
                [
                    'date' => fake()->dateTimeBetween('-3 months', 'now'),
                    'project_name' => 'Proyek ' . fake()->randomLetter(),
                    'expense_description' => fake()->sentence(),
                    'total_amount' => rand(100000, 5000000),
                    'due_date' => fake()->dateTimeBetween('+1 week', '+30 days'),
                    'status' => $status,
                    'status_changed_at' => $status !== 'draft' ? now() : null,
                    'notes' => fake()->optional()->sentence(),
                    'created_by' => $admin?->id,
                ]
            );
        }
    }
}
