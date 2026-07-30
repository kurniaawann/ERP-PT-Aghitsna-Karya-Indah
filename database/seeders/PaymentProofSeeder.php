<?php
namespace Database\Seeders;
use App\Models\Finance\PaymentProof;
use App\Models\Finance\InvoiceProyek;
use App\Models\Finance\InvoiceAlumunium;
use Illuminate\Database\Seeder;

class PaymentProofSeeder extends Seeder
{
    public function run(): void
    {
        $proyekInvoices = InvoiceProyek::take(5)->get();
        foreach ($proyekInvoices as $i => $inv) {
            PaymentProof::create([
                'module_type' => 'finance',
                'invoice_type' => 'proyek',
                'invoice_number' => $inv->invoice_number,
                'payment_stage' => 1,
                'amount' => (int) ($inv->total_amount * 0.5),
                'file_name' => 'proof_' . ($i + 1) . '.jpg',
                'file_path' => 'proofs/finance/proyek/' . ($i + 1) . '.jpg',
                'mime_type' => 'image/jpeg',
                'file_size' => rand(100000, 3000000),
            ]);
        }

        $aluInvoices = InvoiceAlumunium::take(3)->get();
        foreach ($aluInvoices as $i => $inv) {
            PaymentProof::create([
                'module_type' => 'finance',
                'invoice_type' => 'alumunium',
                'invoice_number' => $inv->invoice_number,
                'payment_stage' => 1,
                'amount' => (int) ($inv->total_amount * 0.6),
                'file_name' => 'proof_alu_' . ($i + 1) . '.jpg',
                'file_path' => 'proofs/finance/alumunium/' . ($i + 1) . '.jpg',
                'mime_type' => 'image/jpeg',
                'file_size' => rand(100000, 3000000),
            ]);
        }
    }
}
