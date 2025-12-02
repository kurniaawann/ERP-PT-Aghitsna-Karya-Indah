<?php

namespace App\Http\Controllers;

/**
 * Controller untuk menampilkan halaman-halaman statis.
 * 
 * Controller ini menangani route untuk halaman-halaman utama
 * yang tidak memerlukan logika kompleks.
 */
class PageController extends Controller
{
    /**
     * Menampilkan halaman dashboard utama.
     * 
     * Dashboard adalah halaman pertama yang dilihat user setelah login.
     * Berisi ringkasan informasi dan statistik sistem.
     */
    public function dashboard()
    {
        return view('pages.dashboard');
    }

    /**
     * Menampilkan halaman item (legacy/tidak digunakan).
     * 
     * Catatan: Method ini mungkin tidak digunakan lagi karena
     * item management sudah dipindah ke ItemController.
     */
    public function item()
    {
        return view('pages.item');
    }
}
