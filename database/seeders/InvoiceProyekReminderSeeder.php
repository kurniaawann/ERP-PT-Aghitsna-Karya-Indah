<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InvoiceProyekReminderSeeder extends Seeder
{
    public function run(): void
    {
        $reminders = [
            ['number' => 'P-2026-001', 'date' => now(), 'recipient' => 'PT. Cahaya Sentosa', 'amount' => 15000000, 'remind_date' => now()->addDays(7), 'notes' => 'Ingatkan pembayaran termin 1'],
            ['number' => 'P-2026-002', 'date' => now()->subDays(2), 'recipient' => 'CV. Mitra Jaya', 'amount' => 10000000, 'remind_date' => now()->addDays(5), 'notes' => 'Follow up pembayaran jasa kerja'],
            ['number' => 'P-2026-003', 'date' => now()->subDays(4), 'recipient' => 'PT. Baja Konstruksi', 'amount' => 80000000, 'remind_date' => now()->addDays(3), 'notes' => 'Tagihan baja 10 ton'],
            ['number' => 'P-2026-004', 'date' => now()->subDays(6), 'recipient' => 'CV. Catylac Pro', 'amount' => 15000000, 'remind_date' => now()->addDays(2), 'notes' => 'Pembayaran cat finishing'],
            ['number' => 'P-2026-005', 'date' => now()->subDays(8), 'recipient' => 'PT. Kaca Bersinar', 'amount' => 30000000, 'remind_date' => now()->addDays(1), 'notes' => 'Ingatkan pembayaran kaca tempered'],
            ['number' => 'P-2026-006', 'date' => now()->subDays(1), 'recipient' => 'CV. Utilitas Teknik', 'amount' => 45000000, 'remind_date' => now()->addDays(6), 'notes' => 'Pembayaran sewa crane'],
            ['number' => 'P-2026-007', 'date' => now()->subDays(3), 'recipient' => 'PT. Logistik Cepat', 'amount' => 15000000, 'remind_date' => now()->addDays(4), 'notes' => 'Pembayaran pengiriman material'],
            ['number' => 'P-2026-008', 'date' => now()->subDays(5), 'recipient' => 'CV. Konsultan Teknis', 'amount' => 25000000, 'remind_date' => now()->addDays(2), 'notes' => 'Pembayaran konsultasi proyek'],
            ['number' => 'P-2026-009', 'date' => now()->subDays(7), 'recipient' => 'PT. Instalasi Terpadu', 'amount' => 50000000, 'remind_date' => now()->addDays(3), 'notes' => 'Ingatkan pembayaran instalasi MEP'],
            ['number' => 'P-2026-010', 'date' => now()->subDays(9), 'recipient' => 'CV. Batu Alam', 'amount' => 15000000, 'remind_date' => now()->addDays(5), 'notes' => 'Pembayaran batu alam travertine'],
            ['number' => 'P-2026-011', 'date' => now()->subDays(10), 'recipient' => 'PT. Signage Indonesia', 'amount' => 40000000, 'remind_date' => now()->addDays(8), 'notes' => 'Tagihan pembuatan signage'],
            ['number' => 'P-2026-012', 'date' => now()->subDays(11), 'recipient' => 'CV. Kayu Jati', 'amount' => 20000000, 'remind_date' => now()->addDays(7), 'notes' => 'Pembayaran material kayu'],
            ['number' => 'P-2026-013', 'date' => now()->subDays(12), 'recipient' => 'PT. Keamanan Proyek', 'amount' => 15000000, 'remind_date' => now()->addDays(4), 'notes' => 'Pembayaran jasa keamanan'],
            ['number' => 'P-2026-014', 'date' => now()->subDays(13), 'recipient' => 'CV. Pabrik Precast', 'amount' => 50000000, 'remind_date' => now()->addDays(6), 'notes' => 'Tagihan beton precast'],
            ['number' => 'P-2026-015', 'date' => now()->subDays(14), 'recipient' => 'PT. Utilitas Listrik', 'amount' => 25000000, 'remind_date' => now()->addDays(9), 'notes' => 'Pembayaran material listrik'],
        ];

        foreach ($reminders as $reminder) {
            DB::table('invoice_proyek_reminders')->insert([
                'invoice_number' => $reminder['number'],
                'invoice_date' => $reminder['date']->format('Y-m-d'),
                'recipient' => $reminder['recipient'],
                'total_amount' => $reminder['amount'],
                'reminder_date' => $reminder['remind_date']->format('Y-m-d'),
                'status' => 'pending',
                'notes' => $reminder['notes'],
                'created_at' => $reminder['date'],
                'updated_at' => now(),
            ]);
        }
    }
}
