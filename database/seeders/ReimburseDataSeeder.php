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

        $data = [
            [
                'reimburse_code' => 'RMB101',
                'date' => $now->copy()->subDays(1)->toDateString(),
                'project_name' => 'Proyek Contoh A',
                'expense_description' => 'Biaya transport dan konsumsi',
                'total_amount' => 450000,
                'due_date' => $now->copy()->addDays(7)->toDateString(),
                'status' => 'approved',
                'notes' => 'Telah diverifikasi',
                'status_changed_at' => $now->toDateTimeString(),
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ],
            [
                'reimburse_code' => 'RMB102',
                'date' => $now->copy()->subDays(3)->toDateString(),
                'project_name' => 'Proyek Contoh B',
                'expense_description' => 'Pembelian alat kerja',
                'total_amount' => 1250000,
                'due_date' => $now->copy()->addDays(5)->toDateString(),
                'status' => 'draft',
                'notes' => null,
                'status_changed_at' => null,
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ],
            [
                'reimburse_code' => 'RMB103',
                'date' => $now->copy()->subDays(6)->toDateString(),
                'project_name' => 'Proyek Contoh C',
                'expense_description' => 'Biaya penginapan',
                'total_amount' => 800000,
                'due_date' => $now->copy()->addDays(2)->toDateString(),
                'status' => 'rejected',
                'notes' => 'Dokumen tidak lengkap',
                'status_changed_at' => $now->copy()->subDays(1)->toDateTimeString(),
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ],
        ];

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
