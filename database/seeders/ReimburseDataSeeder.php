<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReimburseDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $data = [];

        $projects = [
            'Proyek Gedung A',
            'Proyek Pabrik B',
            'Proyek Renovasi C',
            'Proyek Kantor D',
            'Proyek Gedung E',
            'Proyek Mall F',
            'Proyek Hotel G',
            'Proyek RS H',
            'Proyek Sekolah I',
            'Proyek Café J',
            'Proyek Showroom K',
            'Proyek Gudang L',
            'Proyek Kantor Cabang M',
            'Proyek Apartemen N',
            'Proyek Perumahan O'
        ];

        $expenses = [
            'Biaya transport dan konsumsi',
            'Pembelian alat kerja',
            'Biaya penginapan',
            'Biaya makan dan minum',
            'Biaya survey lapangan',
            'Biaya konsultasi',
            'Biaya sewa alat',
            'Biaya dokumentasi',
            'Biaya material',
            'Biaya upah kerja',
            'Biaya bensin',
            'Biaya tiket pesawat',
            'Biaya hotel',
            'Biaya tansportasi',
            'Biaya asuransi'
        ];

        for ($i = 1; $i <= 15; $i++) {
            $statusOptions = ['approved', 'draft', 'rejected'];
            $status = $statusOptions[$i % 3];

            $data[] = [
                'reimburse_code' => 'RMB' . str_pad(100 + $i, 3, '0', STR_PAD_LEFT),
                'date' => $now->copy()->subDays($i)->toDateString(),
                'project_name' => $projects[$i - 1],
                'expense_description' => $expenses[$i - 1],
                'total_amount' => rand(300000, 3000000),
                'due_date' => $now->copy()->addDays(rand(1, 10))->toDateString(),
                'status' => $status,
                'notes' => $status === 'rejected' ? 'Dokumen tidak lengkap' : ($status === 'draft' ? 'Menunggu verifikasi' : 'Telah diverifikasi'),
                'status_changed_at' => $status !== 'draft' ? $now->copy()->subDays(max(1, $i - 5))->toDateTimeString() : null,
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ];
        }

        DB::table('reimburses')->upsert(
            $data,
            ['reimburse_code'],
            [
                'date',
                'project_name',
                'expense_description',
                'total_amount',
                'due_date',
                'status',
                'notes',
                'status_changed_at',
                'updated_at',
            ]
        );
    }
}
