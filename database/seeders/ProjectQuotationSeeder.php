<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Administrasi\AluminiumQuotation as ProjectQuotation;
use App\Models\Administrasi\AluminiumQuotationGroup as ProjectQuotationGroup;
use App\Models\Administrasi\AluminiumQuotationItem as ProjectQuotationItem;
use App\Models\Finance\PaymentAccount;

class ProjectQuotationSeeder extends Seeder
{
    public function run(): void
    {
        $paymentAccounts = PaymentAccount::active()->pluck('id')->toArray();
        if (empty($paymentAccounts)) {
            $this->command->warn('Tidak ada payment accounts aktif. Silakan seed payment_accounts terlebih dahulu.');
            return;
        }

        $this->command->info('Seeding 5 Aluminium Quotations...');

        DB::table('aluminium_quotation_items')->delete();
        DB::table('aluminium_quotation_groups')->delete();
        DB::table('aluminium_quotations')->delete();

        $quotations = [
            [
                'quotation_number' => '1/1/ALU/25',
                'sequence_number' => 1,
                'date' => '2025-12-04',
                'subject' => 'Penawaran Harga',
                'recipient' => 'PT. MAJU JAYA',
                'recipient_address' => 'Ditempat',
                'total_amount' => 67339250,
                'amount_in_words' => 'Enam puluh tujuh juta tiga ratus tiga puluh sembilan ribu dua ratus lima puluh rupiah',
                'selected_payment_accounts' => $paymentAccounts,
                'signed_by' => 'Akhmad Khaidir',
                'division' => 'Divisi Alumunium',
                'groups' => [
                    [
                        'name' => 'P.1 Kayu Kamper Samarinda Oven',
                        'subtotal' => 57000000,
                        'items' => [
                            ['5 x 130 x 300', '1', 'unit', 16000000, 16000000],
                            ['5 x 70 x 300', '1', 'unit', 9000000, 9000000],
                            ['Engsel Pivot', '2', 'unit', 4750000, 9500000],
                            ['Slot Tanam Pintu Dekson', '1', 'unit', 1900000, 1900000],
                            ['Kunci Pelor Dekson', '1', 'unit', 1750000, 1750000],
                            ['Handle Kayu Jati', '1', 'unit', 3500000, 3500000],
                            ['Finishing Melamic Semprot', '1', 'set', 3500000, 3500000],
                            ['Jasa Pasang Pintu', '1', 'unit', 2350000, 2350000],
                        ],
                    ],
                    [
                        'name' => 'J.1 Kusen Aluminium Ykk 4 inch + Kaca 8 mm',
                        'subtotal' => 5389000,
                        'items' => [
                            ['Kusen (2 unit)', '13.6', 'm1', 190000, 2584000],
                            ['Kaca Clear 8 mm Asahi + Sealant', '2.2', 'm2', 1275000, 2805000],
                        ],
                    ],
                    [
                        'name' => 'J.2 Kusen Aluminium Ykk 4 inch + Kaca 6 mm',
                        'subtotal' => 4940250,
                        'items' => [
                            ['Kusen (2 unit)', '15.2', 'm1', 190000, 2888000],
                            ['Daun Jendela', '4', 'unit', 1150000, 4600000],
                            ['Kaca 6 mm Clear + Sealant', '5.99', 'm2', 675000, 4043250],
                        ],
                    ],
                ],
            ],
            [
                'quotation_number' => '1/1/ALU/26',
                'sequence_number' => 2,
                'date' => '2026-01-15',
                'subject' => 'Penawaran Harga',
                'recipient' => 'CV. BERKAH JAYA',
                'recipient_address' => 'Depok',
                'total_amount' => 25000000,
                'amount_in_words' => 'Dua puluh lima juta rupiah',
                'selected_payment_accounts' => [$paymentAccounts[0]],
                'signed_by' => 'Akhmad Khaidir',
                'division' => 'Divisi Alumunium',
                'groups' => [
                    [
                        'name' => 'Paket Pintu Alumunium',
                        'subtotal' => 15000000,
                        'items' => [
                            ['Pintu Alumunium 2 x 4 meter', '2', 'unit', 7500000, 15000000],
                        ],
                    ],
                    [
                        'name' => 'Paket Jendela',
                        'subtotal' => 10000000,
                        'items' => [
                            ['Jendela Alumunium + Kaca', '4', 'unit', 2500000, 10000000],
                        ],
                    ],
                ],
            ],
            [
                'quotation_number' => '2/2/ALU/26',
                'sequence_number' => 3,
                'date' => '2026-02-20',
                'subject' => 'Penawaran Harga',
                'recipient' => 'TOKO BANGUNAN SEJAHTERA',
                'recipient_address' => 'Ditempat',
                'total_amount' => 35000000,
                'amount_in_words' => 'Tiga puluh lima juta rupiah',
                'selected_payment_accounts' => $paymentAccounts,
                'signed_by' => 'Akhmad Khaidir',
                'division' => 'Divisi Alumunium',
                'groups' => [
                    [
                        'name' => 'Kusen Aluminium 3 inch',
                        'subtotal' => 20000000,
                        'items' => [
                            ['Kusen Aluminium 3 inch', '25', 'm1', 450000, 11250000],
                            ['Kaca Mati 5 mm', '12', 'm2', 500000, 6000000],
                            ['Sealant', '5', 'unit', 550000, 2750000],
                        ],
                    ],
                    [
                        'name' => 'Daun Pintu Aluminium',
                        'subtotal' => 15000000,
                        'items' => [
                            ['Daun Pintu Aluminium', '3', 'unit', 3500000, 10500000],
                            ['Kunci Handle Set', '3', 'unit', 650000, 1950000],
                            ['Engsel Aluminium', '6', 'unit', 425000, 2550000],
                        ],
                    ],
                ],
            ],
            [
                'quotation_number' => '3/3/ALU/26',
                'sequence_number' => 4,
                'date' => '2026-03-10',
                'subject' => 'Penawaran Harga',
                'recipient' => 'PT. KARYA UTAMA',
                'recipient_address' => 'Bogor',
                'total_amount' => 18500000,
                'amount_in_words' => 'Delapan belas juta lima ratus ribu rupiah',
                'selected_payment_accounts' => [$paymentAccounts[0]],
                'signed_by' => 'Akhmad Khaidir',
                'division' => 'Divisi Alumunium',
                'groups' => [
                    [
                        'name' => 'Partisi Kaca 10 mm',
                        'subtotal' => 18500000,
                        'items' => [
                            ['Partisi Kaca Clear 10 mm', '15', 'm2', 950000, 14250000],
                            ['Profil Aluminium', '30', 'm1', 85000, 2550000],
                            ['Handle Pintu Partisi', '2', 'unit', 850000, 1700000],
                        ],
                    ],
                ],
            ],
            [
                'quotation_number' => '4/4/ALU/26',
                'sequence_number' => 5,
                'date' => '2026-04-05',
                'subject' => 'Penawaran Harga Kanopi',
                'recipient' => 'UD. SUMBER REJEKI',
                'recipient_address' => 'Ditempat',
                'total_amount' => 28750000,
                'amount_in_words' => 'Dua puluh delapan juta tujuh ratus lima puluh ribu rupiah',
                'selected_payment_accounts' => $paymentAccounts,
                'signed_by' => 'Akhmad Khaidir',
                'division' => 'Divisi Alumunium',
                'groups' => [
                    [
                        'name' => 'Kanopi Aluminium',
                        'subtotal' => 28750000,
                        'items' => [
                            ['Rangka Kanopi Aluminium', '1', 'unit', 12500000, 12500000],
                            ['Atap Polycarbonate', '35', 'm2', 350000, 12250000],
                            ['Talang Air', '1', 'set', 2500000, 2500000],
                            ['Jasa Pemasangan', '1', 'unit', 1500000, 1500000],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($quotations as $qData) {
            $groups = $qData['groups'];
            unset($qData['groups']);

            $quotation = ProjectQuotation::create($qData);

            foreach ($groups as $gIdx => $groupData) {
                $items = $groupData['items'];
                unset($groupData['items']);

                $group = ProjectQuotationGroup::create([
                    'quotation_number' => $quotation->quotation_number,
                    'order_number' => $gIdx + 1,
                    'name' => $groupData['name'],
                    'subtotal' => $groupData['subtotal'],
                ]);

                foreach ($items as $iIdx => $item) {
                    ProjectQuotationItem::create([
                        'group_id' => $group->id,
                        'order_number' => $iIdx + 1,
                        'description' => $item[0],
                        'volume' => (string) $item[1],
                        'unit' => $item[2],
                        'unit_price' => (int) $item[3],
                        'total_price' => (int) $item[4],
                    ]);
                }
            }

            $this->command->info("Created: {$quotation->quotation_number} - {$quotation->recipient}");
        }

        $this->command->info('5 Aluminium Quotations seeded successfully!');
    }
}
