<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InvoiceProyekReminderSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('invoice_proyek_reminders')->insert([
            [
                'invoice_number' => 'P-2026-001',
                'invoice_date' => now()->format('Y-m-d'),
                'recipient' => 'PT. Cahaya Sentosa',
                'total_amount' => 15000000,
                'reminder_date' => now()->addDays(7)->format('Y-m-d'),
                'status' => 'pending',
                'notes' => 'Ingatkan pembayaran termin 1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
