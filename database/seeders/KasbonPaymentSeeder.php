<?php
namespace Database\Seeders;
use App\Models\Sdm\KasbonPayment;
use App\Models\Sdm\Kasbon;
use App\Models\User;
use Illuminate\Database\Seeder;

class KasbonPaymentSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $kasbons = Kasbon::where('payment_status', '!=', 'paid')->take(5)->get();

        foreach ($kasbons as $index => $kasbon) {
            $payAmount = (int) ($kasbon->amount * 0.5);
            KasbonPayment::updateOrCreate(
                ['kasbon_code' => $kasbon->kasbon_code, 'payment_date' => '2026-05-15'],
                [
                    'amount' => $payAmount,
                    'payment_method' => 'manual',
                    'notes' => 'Pembayaran cicilan pertama',
                    'created_by' => $admin?->id,
                ]
            );
        }
    }
}
