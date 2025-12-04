<?php

namespace App\Http\Controllers;

class PageController extends Controller
{
    public function dashboard()
    {
        // Return view halaman dashboard utama (resources/views/pages/dashboard.blade.php)
        // Dashboard adalah halaman pertama yang dilihat user setelah login
        // Berisi ringkasan informasi, statistik, dan quick access ke fitur-fitur utama sistem ERP
        return view('pages.dashboard');
    }

    public function item()
    {
        // Return view halaman item (resources/views/pages/item.blade.php)
        // Method ini mungkin tidak digunakan lagi (legacy code)
        // Item management sekarang sudah dipindah ke ItemController di namespace Inventory
        return view('pages.item');
    }
}
