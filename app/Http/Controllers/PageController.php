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

        // Get current month and year
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $currentDay = Carbon::now()->day;

        // Check if it's past the 26th of the month (payroll date)
        $isPayrollPeriod = $currentDay >= 26;

        // Get employees who haven't received salary this month
        // Only check after the 26th
        $employeesWithoutSalary = collect([]);
        if ($isPayrollPeriod) {
            $employeesWithoutSalary = Employee::whereDoesntHave('payrolls', function ($query) use ($currentMonth, $currentYear) {
                $query->where('period_month', $currentMonth)
                    ->where('period_year', $currentYear)
                    ->where('status', 'paid');
            })->get();
        }

        // Get items with low stock (quantity <= 5)
        $lowStockItems = Items::where('quantity', '<=', 5)->get();

        return view('pages.dashboard', compact('employeesWithoutSalary', 'lowStockItems', 'isPayrollPeriod'));
    }

    public function item()
    {
        // Return view halaman item (resources/views/pages/item.blade.php)
        // Method ini mungkin tidak digunakan lagi (legacy code)
        // Item management sekarang sudah dipindah ke ItemController di namespace Inventory
        return view('pages.item');
    }
}
