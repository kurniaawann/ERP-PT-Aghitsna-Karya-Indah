<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Administrasi\ProjectQuotation;
use App\Models\Administrasi\ProjectQuotationGroup;
use App\Models\Administrasi\ProjectQuotationItem;
use App\Models\Finance\PaymentAccount;

class ProjectQuotationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan ada payment accounts
        $paymentAccounts = PaymentAccount::active()->pluck('id')->toArray();
        if (empty($paymentAccounts)) {
            $this->command->warn('Tidak ada payment accounts aktif. Silakan seed payment_accounts terlebih dahulu.');
            return;
        }

        $this->command->info('🌱 Seeding Project Quotations...');

        // ═══════════════════════════════════════════════════════════════════════════
        // Quotation 1 - Berdasarkan PDF yang diberikan
        // ═══════════════════════════════════════════════════════════════════════════
        $q1 = ProjectQuotation::create([
            'quotation_number' => '1/1/PT.AKI/25',
            'sequence_number' => 1,
            'date' => '2025-12-04',
            'subject' => 'Penawaran Harga',
            'recipient' => 'PT. MAJU JAYA',
            'recipient_address' => 'Ditempat',
            'total_amount' => 345209250,
            'amount_in_words' => 'Tiga ratus empat puluh lima juta dua ratus sembilan ribu dua ratus lima puluh rupiah',
            'selected_payment_accounts' => $paymentAccounts,
            'signed_by' => 'Akhmad Khaidir',
            'division' => 'Divisi Alumunium',
        ]);

        // Group 1: P.1 Kayu Kamper Samarinda Oven
        $g1 = ProjectQuotationGroup::create([
            'quotation_number' => $q1->quotation_number,
            'order_number' => 1,
            'name' => 'P.1 Kayu Kamper Samarinda Oven',
            'subtotal' => 57000000,
        ]);
        $items1 = [
            ['5 x 130 x 300', '1', 'unit', 16000000],
            ['5 x 70 x 300', '1', 'unit', 9000000],
            ['Engsel Pivot', '2', 'unit', 9500000],
            ['Slot Tanam Pintu Dekson', '1', 'unit', 1900000],
            ['Kunci Pelor Dekson', null, null, 1750000],
            ['Handle Kayu Jati', '1', 'unit', 3500000],
            ['Finishing Melamic Semprot', '1', 'set', 3500000],
            ['Jasa Pasang Pintu', '1', 'unit', 2350000],
        ];
        foreach ($items1 as $idx => $item) {
            $vol = $item[1] ? floatval($item[1]) : 1;
            ProjectQuotationItem::create([
                'group_id' => $g1->id,
                'order_number' => $idx + 1,
                'description' => $item[0],
                'volume' => $item[1],
                'unit' => $item[2],
                'unit_price' => $item[3],
                'total_price' => (int) ($vol * $item[3]),
            ]);
        }

        // Group 2: J.1 Kusen Aluminium Ykk 4 inch + Kaca 8 mm
        $g2 = ProjectQuotationGroup::create([
            'quotation_number' => $q1->quotation_number,
            'order_number' => 2,
            'name' => 'J.1 Kusen Aluminium Ykk 4 inch + Kaca 8 mm',
            'subtotal' => 5389000,
        ]);
        $items2 = [
            ['Kusen (2 unit)', '13.6', 'm¹', 190000, 2584000],
            ['Kaca Clear 8 mm Asahi + Sealant', '2.2', 'm²', 1275000, 2805000],
        ];
        foreach ($items2 as $idx => $item) {
            ProjectQuotationItem::create([
                'group_id' => $g2->id,
                'order_number' => $idx + 1,
                'description' => $item[0],
                'volume' => $item[1],
                'unit' => $item[2],
                'unit_price' => $item[3],
                'total_price' => $item[4],
            ]);
        }

        // Group 3: J.2 Kusen Aluminium Ykk 4 inch + Kaca 6 mm
        $g3 = ProjectQuotationGroup::create([
            'quotation_number' => $q1->quotation_number,
            'order_number' => 3,
            'name' => 'J.2 Kusen Aluminium Ykk 4 inch + Kaca 6 mm',
            'subtotal' => 15331250,
        ]);
        $items3 = [
            ['Kusen (4 unit x 3.88 mt)', '15.2', 'm¹', 190000, 6688000],
            ['Daun Jendela', '4', 'unit', 1150000, 4600000],
            ['Kaca 6 mm Clear + Sealant', '5.99', 'm²', 675000, 4043250],
        ];
        foreach ($items3 as $idx => $item) {
            ProjectQuotationItem::create([
                'group_id' => $g3->id,
                'order_number' => $idx + 1,
                'description' => $item[0],
                'volume' => $item[1],
                'unit' => $item[2],
                'unit_price' => $item[3],
                'total_price' => $item[4],
            ]);
        }

        // Group 4: J.3 Kusen Aluminium Ykk 4 inch dan Daun Jendela
        $g4 = ProjectQuotationGroup::create([
            'quotation_number' => $q1->quotation_number,
            'order_number' => 4,
            'name' => 'J.3 Kusen Aluminium Ykk 4 inch dan Daun Jendela',
            'subtotal' => 2062000,
        ]);
        $items4 = [
            ['Kusen', '4.8', 'm¹', 190000, 912000],
            ['Daun Jendela', '1', 'unit', 1150000, 1150000],
        ];
        foreach ($items4 as $idx => $item) {
            ProjectQuotationItem::create([
                'group_id' => $g4->id,
                'order_number' => $idx + 1,
                'description' => $item[0],
                'volume' => $item[1],
                'unit' => $item[2],
                'unit_price' => $item[3],
                'total_price' => $item[4],
            ]);
        }

        // Group 5: J.4 Kusen Aluminium Ykk 4 inch dan (Mati 8 mm + Sealant)
        $g5 = ProjectQuotationGroup::create([
            'quotation_number' => $q1->quotation_number,
            'order_number' => 5,
            'name' => 'J.4 Kusen Aluminium Ykk 4 inch dan (Mati 8 mm + Sealant)',
            'subtotal' => 7898250,
        ]);
        $items5 = [
            ['Kusen', '15.6', 'm¹', 190000, 2964000],
            ['Kaca Clear 8 mm Asahi + Sealant', '3.87', 'm²', 1275000, 4934250],
        ];
        foreach ($items5 as $idx => $item) {
            ProjectQuotationItem::create([
                'group_id' => $g5->id,
                'order_number' => $idx + 1,
                'description' => $item[0],
                'volume' => $item[1],
                'unit' => $item[2],
                'unit_price' => $item[3],
                'total_price' => $item[4],
            ]);
        }

        // Group 6-15 (simplified, hanya ambil beberapa contoh lagi)
        $additionalGroups = [
            [
                'name' => 'J.5 Kusen Aluminium Ykk 4 inch dan Kaca Mati, Daun Jendela',
                'items' => [
                    ['Kusen', '10', 'm¹', 190000, 1976000],
                ],
            ],
            [
                'name' => 'J.6 Kusen Aluminium Ykk dan Kaca Mati 6 mm + Sealant',
                'items' => [
                    ['Kusen', '21.6', 'm¹', 190000, 4104000],
                    ['Kaca Mati 6 mm Asahi + Sealant', '5.12', 'm²', 675000, 5481000],
                ],
            ],
            [
                'name' => 'J.7 Kusen Aluminium Ykk 4 inch dan Daun Jendela + Kaca',
                'items' => [
                    ['Kusen (2 unit)', '46.9', 'm¹', 190000, 8911000],
                    ['Daun Jendela + Kaca 6 mm', '8', 'unit', 1900000, 15200000],
                ],
            ],
            [
                'name' => 'P.2 Kusen Kayu Kamper Samarinda Oven dan Daun Pintu FlyWood',
                'items' => [
                    ['Kusen Kayu Kamper Oven', '5', 'unit', 1567500, 7837500],
                    ['Daun Pintu Double Megatik Flywood', '5', 'unit', 2750000, 13750000],
                    ['Finishing Melamic Semprot', '5', 'unit', 2250000, 11250000],
                    ['Kunci dan Engsel Dekson', null, 'set', 1250000, 6250000],
                    ['Jasa Pasang Pintu', '5', 'unit', 550000, 1750000],
                ],
            ],
            [
                'name' => 'P.3 Kusen + Daun Pintu UPVC CONCH',
                'items' => [
                    ['Kusen + Daun Pintu UPVC CONCH', '5', 'unit', 5500000, 27500000],
                ],
            ],
            [
                'name' => 'P.4 Kusen Pintu dan Daun Pintu Solid Kamper Oven',
                'items' => [
                    ['Kusen', '6.4', 'm¹', 255000, 1632000],
                    ['Daun Pintu Solid 5 x 75 x 240', '2', 'unit', 7500000, 15000000],
                    ['Handle Kayu Kamper', '2', 'pasang', 1750000, 3500000],
                    ['Finishing Melamic Semprot', '1', 'set', 1900000, 1750000],
                    ['Jasa Pasang Pintu', null, null, 700000, 700000],
                    ['Kunci, Engsel, Slot Tanam Dekson', null, null, 2450000, 2450000],
                ],
            ],
        ];

        $orderNum = 6;
        foreach ($additionalGroups as $gData) {
            $subtotal = 0;
            foreach ($gData['items'] as $item) {
                $subtotal += $item[3];
            }

            $group = ProjectQuotationGroup::create([
                'quotation_number' => $q1->quotation_number,
                'order_number' => $orderNum++,
                'name' => $gData['name'],
                'subtotal' => $subtotal,
            ]);

            foreach ($gData['items'] as $idx => $item) {
                ProjectQuotationItem::create([
                    'group_id' => $group->id,
                    'order_number' => $idx + 1,
                    'description' => $item[0],
                    'volume' => $item[1] ?? null,
                    'unit' => $item[2] ?? null,
                    'unit_price' => $item[3],
                    'total_price' => $item[4],
                ]);
            }
        }

        $this->command->info("✅ Created quotation: {$q1->quotation_number}");

        // ═══════════════════════════════════════════════════════════════════════════
        // Quotation 2 - Simple example
        // ═══════════════════════════════════════════════════════════════════════════
        $q2 = ProjectQuotation::create([
            'quotation_number' => '1/1/PT.AKI/26',
            'sequence_number' => 1,
            'date' => now()->format('Y-m-d'),
            'subject' => 'Penawaran Harga',
            'recipient' => 'CV. BERKAH JAYA',
            'recipient_address' => 'Depok',
            'total_amount' => 25000000,
            'amount_in_words' => 'Dua puluh lima juta rupiah',
            'selected_payment_accounts' => [$paymentAccounts[0]],
            'signed_by' => 'Akhmad Khaidir',
            'division' => 'Divisi Alumunium',
        ]);

        $g2_1 = ProjectQuotationGroup::create([
            'quotation_number' => $q2->quotation_number,
            'order_number' => 1,
            'name' => 'Paket Pintu Alumunium',
            'subtotal' => 15000000,
        ]);
        ProjectQuotationItem::create([
            'group_id' => $g2_1->id,
            'order_number' => 1,
            'description' => 'Pintu Alumunium 2 x 4 meter',
            'volume' => '2',
            'unit' => 'unit',
            'unit_price' => 7500000,
            'total_price' => 15000000,
        ]);

        $g2_2 = ProjectQuotationGroup::create([
            'quotation_number' => $q2->quotation_number,
            'order_number' => 2,
            'name' => 'Paket Jendela',
            'subtotal' => 10000000,
        ]);
        ProjectQuotationItem::create([
            'group_id' => $g2_2->id,
            'order_number' => 1,
            'description' => 'Jendela Alumunium + Kaca',
            'volume' => '4',
            'unit' => 'unit',
            'unit_price' => 2500000,
            'total_price' => 10000000,
        ]);

        $this->command->info("✅ Created quotation: {$q2->quotation_number}");
        $this->command->info('🎉 Project Quotation seeding completed!');
    }
}
