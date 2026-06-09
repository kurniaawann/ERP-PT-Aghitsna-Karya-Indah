<?php

namespace App\Http\Controllers;

use App\Models\Sdm\Employee;
use App\Models\Sdm\Payroll;
use App\Models\Inventory\Items;
use Carbon\Carbon;

class PageController extends Controller
{
    public function dashboard()
    {
        // Return view halaman dashboard utama (resources/views/pages/dashboard.blade.php)
        // Dashboard adalah halaman pertama yang dilihat user setelah login
        // Berisi ringkasan informasi, statistik, dan quick access ke fitur-fitur utama sistem ERP

        $role = auth()->user()?->role;

        // Get current date info
        $now = Carbon::now();

        $currentMonth = $now->month;
        $currentYear = $now->year;
        $currentDay = $now->dayOfWeek; // 0=Minggu, 1=Senin, ..., 6=Sabtu

        // Tentukan minggu saat ini (1, 2, 3, atau 4)
        $currentWeek = $this->getCurrentWeek($now);

        // Tentukan apakah sedang periode payroll (hari Senin-Minggu minggu tersebut)
        // Periode payroll dimulai setiap hari Senin dan berakhir hari Minggu
        $isPayrollPeriod = true; // Selalu aktif karena payroll mingguan

        // Get employees who haven't received salary from week 1 to current week
        // Cek semua karyawan dan tentukan minggu mana saja yang belum dibayar
        $employeesWithoutSalary = [];

        // staf_gudang hanya melihat Reminder Stok Menipis
        $shouldShowPayrollReminder = $role !== 'staf_gudang';
        $shouldShowStockReminder = true; // semua role masih bisa lihat stok reminder (sesuai checklist hanya membatasi staf_gudang untuk payroll)

        if ($shouldShowPayrollReminder) {
            $allEmployees = Employee::all();

            foreach ($allEmployees as $employee) {
                $unpaidWeeks = [];

                // Cek dari minggu 1 sampai minggu saat ini
                for ($week = 1; $week <= $currentWeek; $week++) {
                    // Cek apakah payroll minggu ini sudah dibayar
                    $isPaid = Payroll::where('employee_id', $employee->employee_code)
                        ->where('period_month', $currentMonth)
                        ->where('period_year', $currentYear)
                        ->where('week_number', $week)
                        ->where('status', 'paid')
                        ->exists();

                    if (!$isPaid) {
                        $weekRange = $this->getWeekDateRange($currentYear, $currentMonth, $week);
                        $unpaidWeeks[] = [
                            'week_number' => $week,
                            'start_date' => $weekRange['start']->format('d M'),
                            'end_date' => $weekRange['end']->format('d M'),
                        ];
                    }
                }

                // Jika ada minggu yang belum dibayar, masukkan ke array
                if (count($unpaidWeeks) > 0) {
                    $employeesWithoutSalary[] = [
                        'employee' => $employee,
                        'unpaid_weeks' => $unpaidWeeks,
                        'total_unpaid_weeks' => count($unpaidWeeks),
                    ];
                }
            }
        }

        // Get items with low stock (quantity <= 5)
        $lowStockItems = $shouldShowStockReminder ? Items::where('quantity', '<=', 5)->get() : collect();


        // Hitung tanggal range minggu ini untuk ditampilkan di view
        $weekRange = $this->getWeekDateRange($currentYear, $currentMonth, $currentWeek);

        return view('pages.dashboard', compact(
            'employeesWithoutSalary',
            'lowStockItems',
            'isPayrollPeriod',
            'currentWeek',
            'weekRange',
            'shouldShowPayrollReminder',
            'shouldShowStockReminder'
        ));

    }

    /**
     * Menentukan minggu ke berapa dalam bulan ini (1, 2, 3, atau 4)
     * Berdasarkan hari Senin pertama di bulan tersebut
     */
    private function getCurrentWeek($date)
    {
        $year = $date->year;
        $month = $date->month;
        $currentDate = $date->copy();

        // Cari Senin pertama di bulan ini
        $firstDayOfMonth = Carbon::create($year, $month, 1);

        if ($firstDayOfMonth->dayOfWeek === 0) {
            // Jika tanggal 1 adalah Minggu, Senin pertama adalah tanggal 2
            $firstMonday = $firstDayOfMonth->copy()->addDay();
        } elseif ($firstDayOfMonth->dayOfWeek === 1) {
            // Jika tanggal 1 adalah Senin
            $firstMonday = $firstDayOfMonth->copy();
        } else {
            // Jika tanggal 1 adalah Selasa-Sabtu, cari Senin berikutnya
            $firstMonday = $firstDayOfMonth->copy()->next(Carbon::MONDAY);
        }

        // Jika tanggal sekarang sebelum Senin pertama, dianggap masih minggu 4 bulan lalu
        if ($currentDate->lt($firstMonday)) {
            return 4; // Default minggu 4
        }

        // Hitung selisih hari dari Senin pertama
        $daysDiff = $currentDate->diffInDays($firstMonday, false);

        // Tentukan minggu berdasarkan selisih hari
        $weekNumber = floor(abs($daysDiff) / 7) + 1;

        // Batasi maksimal minggu 4
        return min($weekNumber, 4);
    }

    /**
     * Menghitung range tanggal untuk minggu tertentu
     * Returns array dengan 'start', 'end', dan 'working_days'
     */
    private function getWeekDateRange($year, $month, $weekNumber)
    {
        $firstDayOfMonth = Carbon::create($year, $month, 1);

        // Cari hari Senin pertama di bulan ini
        if ($firstDayOfMonth->dayOfWeek === 0) {
            $firstMonday = $firstDayOfMonth->copy()->addDay();
        } elseif ($firstDayOfMonth->dayOfWeek === 1) {
            $firstMonday = $firstDayOfMonth->copy();
        } else {
            $firstMonday = $firstDayOfMonth->copy()->next(Carbon::MONDAY);
        }

        // Hitung tanggal mulai berdasarkan minggu
        $startDate = $firstMonday->copy()->addWeeks($weekNumber - 1);

        // Tanggal akhir adalah Minggu (6 hari setelah Senin)
        $endDate = $startDate->copy()->addDays(6);

        // Pastikan endDate tidak melebihi akhir bulan
        $lastDayOfMonth = Carbon::create($year, $month, 1)->endOfMonth();
        if ($endDate->greaterThan($lastDayOfMonth)) {
            $endDate = $lastDayOfMonth;
        }

        return [
            'start' => $startDate,
            'end' => $endDate,
        ];
    }

    public function item()
    {
        // Return view halaman item (resources/views/pages/item.blade.php)
        // Method ini mungkin tidak digunakan lagi (legacy code)
        // Item management sekarang sudah dipindah ke ItemController di namespace Inventory
        return view('pages.item');
    }
}
