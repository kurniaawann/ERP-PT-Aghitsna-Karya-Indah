<?php
namespace Database\Seeders;
use App\Models\Administrasi\CashOutProof;
use App\Models\User;
use Illuminate\Database\Seeder;

class CashOutProofSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $paidTos = [
            'Supplier Operasional', 'Tim Proyek', 'Petty Cash', 'Toko Perlengkapan',
            'Karyawan', 'Toko ATK', 'Vendor Site', 'Instansi Terkait',
            'Bengkel', 'PLN/Utility', 'Safety Supplier', 'Konsultan',
            'Cleaning Service', 'Asuransi', 'Supplier Material',
        ];

        foreach ($paidTos as $i => $paidTo) {
            CashOutProof::updateOrCreate(
                ['bkk_no' => 'BKK-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT)],
                [
                    'cek_no' => 'CEK-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                    'date' => fake()->dateTimeBetween('-6 months', 'now'),
                    'paid_to' => $paidTo,
                    'amount' => rand(100000, 10000000),
                    'description' => 'Pembayaran untuk ' . $paidTo,
                    'director' => 'Zulkarnain,ST.,MT',
                    'finance_head' => 'Kamila,AMK',
                    'template_type' => 'standard',
                    'created_by' => $admin?->id,
                ]
            );
        }
    }
}
