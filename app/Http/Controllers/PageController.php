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

        // Get employees who haven't received salary this month
        $employeesWithoutSalary = Employee::whereDoesntHave('payrolls', function ($query) use ($currentMonth, $currentYear) {
            $query->where('period_month', $currentMonth)
                ->where('period_year', $currentYear)
                ->where('status', 'paid');
        })->get();

        // Get items with low stock (quantity <= 5)
        $lowStockItems = Items::where('quantity', '<=', 5)->get();

        return view('pages.dashboard', compact('employeesWithoutSalary', 'lowStockItems'));
    }

    public function item()
    {
        // Return view halaman item (resources/views/pages/item.blade.php)
        // Method ini mungkin tidak digunakan lagi (legacy code)
        // Item management sekarang sudah dipindah ke ItemController di namespace Inventory
        return view('pages.item');
    }
}
