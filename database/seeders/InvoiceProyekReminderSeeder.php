<?php
namespace Database\Seeders;
use App\Models\Notification\InvoiceProyekReminder;
use App\Models\Finance\InvoiceProyek;
use App\Models\User;
use Illuminate\Database\Seeder;

class InvoiceProyekReminderSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $invoices = InvoiceProyek::all();
        if ($invoices->isEmpty()) return;

        foreach ($invoices as $inv) {
            InvoiceProyekReminder::create([
                'invoice_number' => $inv->invoice_number,
                'invoice_date' => $inv->invoice_date,
                'recipient' => $inv->recipient,
                'total_amount' => $inv->total_amount,
                'reminder_date' => fake()->dateTimeBetween('-1 week', '+30 days'),
                'status' => 'pending',
                'created_by' => $admin?->id,
            ]);
        }
    }
}
